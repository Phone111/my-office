<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Support\AuditLogger;
use Modules\Core\Support\RoleLabels;

/**
 * มอบหมายหน้าที่ (Duty Assignment) — จัดการ role ของผู้ใช้รายคน
 * 1 user ถือได้หลาย role (เช่น teacher + leave_officer)
 * เฉพาะ admin/director (กำหนดที่ route middleware)
 */
class UserRoleController extends Controller
{
    /** มอบ role เพิ่มให้ผู้ใช้ (ไม่กระทบ role เดิม) */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $v = $request->validate(['role' => ['required', 'string', Rule::exists('roles', 'name')]]);
        $this->authorizeRoleManagement($request, $user, $v['role']);

        $user->assignRole($v['role']);
        AuditLogger::log('role', $user, 'มอบหน้าที่ "'.RoleLabels::label($v['role'])."\" ให้ {$user->name}");

        return $this->respond($user, "มอบหมายหน้าที่ \"".RoleLabels::label($v['role'])."\" ให้ {$user->name} แล้ว");
    }

    /** ถอด role ออกจากผู้ใช้ */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $v = $request->validate(['role' => ['required', 'string', Rule::exists('roles', 'name')]]);
        $this->authorizeRoleManagement($request, $user, $v['role']);
        $this->guardSelfAdmin($request, $user, $v['role']);

        $user->removeRole($v['role']);
        AuditLogger::log('role', $user, 'ถอดหน้าที่ "'.RoleLabels::label($v['role'])."\" ออกจาก {$user->name}");

        return $this->respond($user, "ถอดหน้าที่ \"".RoleLabels::label($v['role'])."\" ออกจาก {$user->name} แล้ว");
    }

    /** อัปเดต role ทั้งหมดของผู้ใช้ใหม่ */
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $v = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);
        $roles = array_values(array_unique($v['roles']));
        $actor = $request->user();

        if ($actor->hasRole('system_admin')) {
            // ผู้ดูแลระบบ: ตั้งค่า role ได้อิสระ
            $user->syncRoles($roles);
        } else {
            // ผอ. (ไม่ใช่ admin): เฉพาะบุคลากรในหน่วยงานตน + แก้ได้เฉพาะหน้าที่ที่อนุญาต (คงหน้าที่อื่นไว้)
            abort_unless($user->unit_id !== null && (int) $user->unit_id === (int) $actor->unit_id, 403, 'จัดการได้เฉพาะบุคลากรในหน่วยงานของท่าน');
            $keep = array_values(array_diff($user->getRoleNames()->all(), self::DIRECTOR_GRANTABLE));
            $grant = array_values(array_intersect($roles, self::DIRECTOR_GRANTABLE));
            $user->syncRoles(array_values(array_unique(array_merge($keep, $grant))));
        }

        AuditLogger::log('role', $user, "ปรับหน้าที่ของ {$user->name} = ".$user->fresh()->getRoleNames()->implode(', '));

        return $this->respond($user, "อัปเดตหน้าที่ของ {$user->name} เรียบร้อย");
    }

    /** หน้าที่ที่ "ผอ." (ไม่ใช่ admin) มอบ/ถอดได้ — ระดับโรงเรียน ไม่รวมหน้าที่ระดับเขต/admin */
    private const DIRECTOR_GRANTABLE = [
        'head', 'officer', 'staff', 'deputy_director',
        'school_clerk', 'group_clerk', 'leave_officer', 'vehicle_booking_officer',
    ];

    /**
     * จำกัดสิทธิ์การจัดการ role:
     *  - admin: จัดการได้ทุกคน ทุก role
     *  - ผอ. (ไม่ใช่ admin): เฉพาะบุคลากรในหน่วยงานตน + เฉพาะหน้าที่ในรายการที่อนุญาต
     *    (กันยกระดับสิทธิ์ตัวเอง/ข้ามหน่วยงาน)
     */
    private function authorizeRoleManagement(Request $request, User $target, string $role): void
    {
        $actor = $request->user();
        if ($actor->hasRole('system_admin')) {
            return;
        }
        abort_unless($target->unit_id !== null && (int) $target->unit_id === (int) $actor->unit_id, 403, 'จัดการได้เฉพาะบุคลากรในหน่วยงานของท่าน');
        abort_unless(in_array($role, self::DIRECTOR_GRANTABLE, true), 403, 'ท่านไม่มีสิทธิ์มอบหมาย/ถอดหน้าที่นี้');
    }

    /** กันถอด admin ออกจากตัวเอง */
    private function guardSelfAdmin(Request $request, User $user, string $role): void
    {
        if ($role === 'system_admin' && $user->id === $request->user()->id) {
            abort(422, 'ไม่สามารถถอดสิทธิ์ admin ของตนเองได้');
        }
    }

    private function respond(User $user, string $message): JsonResponse
    {
        $names = $user->fresh()->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => $message,
            'user_id' => $user->id,
            'roles' => $names->map(fn ($r) => ['name' => $r, 'label' => RoleLabels::label($r)])->values(),
        ]);
    }
}

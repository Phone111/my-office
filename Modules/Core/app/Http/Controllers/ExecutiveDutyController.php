<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * การปฏิบัติราชการของผู้บริหาร — แต่งตั้งผู้บริหารที่ "ปฏิบัติราชการ" (ผอ.)
 * และผู้ "รักษาราชการแทน" (รองฯ/หัวหน้ากลุ่ม) เพื่อแสดงบนหน้าหลัก
 */
class ExecutiveDutyController extends Controller
{
    /** บทบาทระดับบริหารที่อยู่ในบอร์ดนี้ */
    private const EXEC_ROLES = ['executive', 'head'];

    public function index(): Response
    {
        $users = User::role(self::EXEC_ROLES)
            ->with(['position:id,name', 'roles:id,name'])
            ->orderBy('duty_order')
            ->orderBy('name')
            ->get();

        $rows = $users->map(function (User $u) {
            $isDirector = $u->roles->contains('name', 'director');

            return [
                'id' => $u->id,
                'name' => $u->name,
                'position' => $u->position?->name ?? '—',
                'is_director' => $isDirector,
                'duty_label' => $isDirector ? 'ปฏิบัติราชการ' : 'รักษาราชการแทน',
                'active' => (bool) $u->duty_active,
            ];
        })
            // ผอ. ขึ้นก่อน แล้วตามด้วยลำดับ/ชื่อ
            ->sortByDesc('is_director')
            ->values();

        return Inertia::render('Core::Admin/ExecutiveDuty', [
            'rows' => $rows,
            'activeCount' => $rows->where('active', true)->count(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'active' => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        // อนุญาตเฉพาะผู้ที่มีบทบาทระดับบริหารเท่านั้น (กันติ๊กคนทั่วไปขึ้นบอร์ด)
        abort_unless($user->hasAnyRole(self::EXEC_ROLES), 422, 'ผู้ใช้นี้ไม่ใช่ผู้บริหาร');

        $user->duty_active = $validated['active'];
        $user->save();

        $status = $validated['active'] ? 'แสดงบนหน้าหลัก' : 'ซ่อนจากหน้าหลัก';

        return back()->with('success', "อัปเดต {$user->name} — {$status} เรียบร้อยแล้ว");
    }
}

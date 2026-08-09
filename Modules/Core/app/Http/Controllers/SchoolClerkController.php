<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Unit;

/**
 * ตั้งค่าสารบรรณโรงเรียน — ผอ.ร.ร. แต่งตั้งบุคลากรในโรงเรียนเป็น "สารบรรณโรงเรียน" (role school_clerk)
 * admin/area_admin เลือกโรงเรียนใดก็ได้ · ผอ./รองฯ จัดการเฉพาะโรงเรียนตัวเอง
 */
class SchoolClerkController extends Controller
{
    private function isOverseer(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin']);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $overseer = $this->isOverseer($user);

        $schools = $overseer
            ? Unit::schools()->orderBy('name')->get(['id', 'name'])->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            : collect();

        $unitId = $overseer
            ? (int) ($request->input('unit') ?: ($schools->first()['id'] ?? 0))
            : (int) $user->unit_id;

        $unit = Unit::find($unitId);

        $members = $unitId
            ? User::with(['roles:id,name', 'position:id,name'])->where('unit_id', $unitId)->orderBy('name')->get()
            : collect();

        $rows = $members->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'position' => $u->position?->name,
            'is_clerk' => $u->roles->contains('name', 'school_clerk'),
        ]);

        return Inertia::render('Core::SchoolClerk', [
            'rows' => $rows,
            'unitName' => $unit?->name,
            'schools' => $schools,
            'selectedUnit' => $unitId,
            'canPickSchool' => $overseer,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'acts' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $target = User::findOrFail($v['user_id']);

        // ต้องสังกัดโรงเรียน (ไม่ใช่เขต) และอยู่ในขอบเขตที่จัดการได้
        $targetUnit = Unit::find($target->unit_id);
        abort_unless($targetUnit && $targetUnit->type === Unit::TYPE_SCHOOL, 422, 'ผู้ใช้นี้ไม่ได้สังกัดโรงเรียน');
        $allowed = $this->isOverseer($user) || ($target->unit_id && $target->unit_id === $user->unit_id);
        abort_unless($allowed, 403);

        if ($v['acts']) {
            $target->assignRole('school_clerk');
            $msg = "แต่งตั้ง {$target->name} เป็นสารบรรณโรงเรียนแล้ว";
        } else {
            $target->removeRole('school_clerk');
            $msg = "ถอน {$target->name} จากสารบรรณโรงเรียนแล้ว";
        }

        return back()->with('success', $msg);
    }
}

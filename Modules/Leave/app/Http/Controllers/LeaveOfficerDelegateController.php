<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการผู้ปฏิบัติงานแทน (เจ้าหน้าที่วันลา) — แต่งตั้ง/ถอนบุคลากรให้ทำหน้าที่ "เจ้าหน้าที่การลา"
 * (ให้/ถอน role leave_officer) สำหรับกรณีเจ้าหน้าที่วันลาลา/ไปราชการ
 */
class LeaveOfficerDelegateController extends Controller
{
    public function index(): Response
    {
        $rows = User::with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'is_officer' => $u->roles->contains('name', 'leave_officer'),
            ]);

        return Inertia::render('Leave::LeaveOfficerDelegates', [
            'rows' => $rows,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        // กัน privilege escalation — เฉพาะเลขาฯ/admin ตั้ง-ถอด role ได้
        abort_unless($request->user()->hasAnyRole(['secretary', 'admin']), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'acts' => ['required', 'boolean'],
        ]);

        $target = User::findOrFail($validated['user_id']);

        if ($validated['acts']) {
            $target->assignRole('leave_officer');
            $msg = "แต่งตั้ง {$target->name} เป็นเจ้าหน้าที่การลาเรียบร้อยแล้ว";
        } else {
            $target->removeRole('leave_officer');
            $msg = "ถอน {$target->name} จากหน้าที่เจ้าหน้าที่การลาแล้ว";
        }

        return back()->with('success', $msg);
    }
}

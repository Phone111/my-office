<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * จัดการผู้ปฏิบัติงานแทน — หัวหน้ากลุ่มแต่งตั้งสมาชิกในกลุ่มให้ทำหน้าที่ "เจ้าหน้าที่ธุรการกลุ่ม"
 * (ให้/ถอน role group_clerk ภายในกลุ่มตัวเอง)
 */
class GroupDelegateController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $gid = $user->group_id;

        $members = $gid
            ? User::with('roles:id,name')->where('group_id', $gid)->orderBy('name')->get()
            : collect();

        $rows = $members->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'is_clerk' => $u->roles->contains('name', 'group_clerk'),
        ]);

        return Inertia::render('Saraban::GroupDelegates', [
            'rows' => $rows,
            'groupName' => $user->group?->name,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'acts' => ['required', 'boolean'],
        ]);

        $target = User::findOrFail($validated['user_id']);

        // จัดการได้เฉพาะสมาชิกในกลุ่มเดียวกัน
        abort_unless($target->group_id && $target->group_id === $request->user()->group_id, 403);

        if ($validated['acts']) {
            $target->assignRole('group_clerk');
            $msg = "แต่งตั้ง {$target->name} เป็นเจ้าหน้าที่ธุรการกลุ่มเรียบร้อยแล้ว";
        } else {
            $target->removeRole('group_clerk');
            $msg = "ถอน {$target->name} จากหน้าที่เจ้าหน้าที่ธุรการกลุ่มแล้ว";
        }

        return back()->with('success', $msg);
    }
}

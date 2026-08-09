<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Department;
use Modules\Core\Models\Group;

/**
 * จัดการกลุ่ม — กลุ่มบริหารงาน/กลุ่มงาน (แยกจาก "กลุ่มสาระ" = departments)
 */
class GroupController extends Controller
{
    public function index(): Response
    {
        $groups = Group::with('head:id,name')
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->map(fn (Group $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'level' => $g->level,
                'type' => $g->type,
                'type_label' => Group::TYPES[$g->type] ?? null,
                'code' => $g->code,
                'description' => $g->description,
                'head' => $g->head?->name,
                'head_user_id' => $g->head_user_id,
                'is_active' => $g->is_active,
            ]);

        return Inertia::render('Core::Admin/Groups', [
            'groups' => $groups,
            'types' => Group::TYPES,
            // รายชื่อผู้ใช้สำหรับเลือกหัวหน้ากลุ่ม
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Group $group): \Illuminate\Http\JsonResponse
    {
        return response()->json($group->load('head:id,name'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        // ออกเลขลำดับอัตโนมัติ (ต่อจากลำดับสูงสุด)
        $data['level'] = (int) (Group::max('level') ?? 0) + 1;

        Group::create($data);

        return back()->with('success', 'เพิ่มกลุ่มเรียบร้อยแล้ว');
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $group->update($this->validateData($request, $group->id));

        return back()->with('success', 'แก้ไขกลุ่มเรียบร้อยแล้ว');
    }

    public function destroy(Group $group): RedirectResponse
    {
        if (User::where('group_id', $group->id)->exists()) {
            return back()->with('error', 'ไม่สามารถลบกลุ่มที่มีบุคลากรสังกัดอยู่');
        }
        if (Department::where('group_id', $group->id)->exists()) {
            return back()->with('error', 'ไม่สามารถลบกลุ่มที่มีกลุ่มสาระอยู่ภายใต้');
        }

        $group->delete();

        return back()->with('success', 'ลบกลุ่มเรียบร้อยแล้ว');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name'.($ignoreId ? ",{$ignoreId}" : '')],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_keys(\Modules\Core\Models\Group::TYPES))],
            'code' => ['nullable', 'string', 'max:50', 'unique:groups,code'.($ignoreId ? ",{$ignoreId}" : '')],
            'description' => ['nullable', 'string', 'max:1000'],
            'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);
    }
}

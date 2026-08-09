<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Department;
use Modules\Core\Models\Group;

/**
 * จัดการกลุ่มสาระการเรียนรู้ (departments)
 */
class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::with('group:id,name')
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $d) => [
                'id' => $d->id,
                'sort_order' => $d->sort_order,
                'name' => $d->name,
                'group' => $d->group?->name,
                'group_id' => $d->group_id,
                'type' => $d->type,
                'type_label' => Department::TYPES[$d->type] ?? null,
                'level' => $d->level(),
                'is_active' => $d->is_active,
                'users_count' => $d->users_count,
            ]);

        return Inertia::render('Core::Admin/Departments', [
            'departments' => $departments,
            'groups' => Group::orderBy('level')->orderBy('name')->get(['id', 'name']),
            'types' => Department::TYPES,
        ]);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json($department->load('group:id,name')->loadCount('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        // ออกลำดับอัตโนมัติ (ต่อจากลำดับสูงสุด)
        $data['sort_order'] = (int) (Department::max('sort_order') ?? 0) + 1;

        Department::create($data);

        return back()->with('success', 'เพิ่มกลุ่มสาระเรียบร้อยแล้ว');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validateData($request, $department->id));

        return back()->with('success', 'แก้ไขกลุ่มสาระเรียบร้อยแล้ว');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if (User::where('department_id', $department->id)->exists()) {
            return back()->with('error', 'ไม่สามารถลบกลุ่มสาระที่มีบุคลากรสังกัดอยู่');
        }

        $department->delete();

        return back()->with('success', 'ลบกลุ่มสาระเรียบร้อยแล้ว');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'.($ignoreId ? ",{$ignoreId}" : '')],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Department::TYPES))],
            'is_active' => ['boolean'],
        ]);
    }
}

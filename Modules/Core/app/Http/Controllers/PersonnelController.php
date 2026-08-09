<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Department;
use Modules\Core\Models\Group;
use Modules\Core\Models\Position;
use Modules\Core\Support\RoleLabels;
use Spatie\Permission\Models\Role;

/**
 * จัดการบุคลากร (Users) — สร้าง/แก้ไข/ลบผู้ใช้
 * พร้อมกำหนด ตำแหน่ง / กลุ่มงาน / กลุ่มสาระ / ปฏิบัติหน้าที่ (roles)
 */
class PersonnelController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['department:id,name', 'group:id,name', 'position:id,name', 'roles:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'phone' => $u->phone,
                'position' => $u->position?->name,
                'position_id' => $u->position_id,
                'group' => $u->group?->name,
                'group_id' => $u->group_id,
                'department' => $u->department?->name,
                'department_id' => $u->department_id,
                'roles' => $u->roles->pluck('name'),
                'is_acting_director' => (bool) $u->is_acting_director,
            ]);

        return Inertia::render('Core::Admin/Personnel', [
            'users' => $users,
            'positions' => Position::orderBy('name')->get(['id', 'name']),
            'groups' => Group::orderBy('level')->orderBy('name')->get(['id', 'name']),
            'departments' => Department::orderBy('sort_order')->get(['id', 'name']),
            'roles' => $this->roleOptions(),
            'roleGroups' => RoleLabels::grouped(['system_admin']),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['department:id,name', 'group:id,name', 'position:id,name', 'roles:id,name']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'position' => $user->position?->name,
            'group' => $user->group?->name,
            'department' => $user->department?->name,
            'roles' => $user->roles->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request, true);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'position_id' => $validated['position_id'] ?? null,
            'group_id' => $validated['group_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_acting_director' => $validated['is_acting_director'] ?? false,
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return back()->with('success', "เพิ่มบุคลากร {$user->name} เรียบร้อยแล้ว");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateData($request, false, $user->id);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'position_id' => $validated['position_id'] ?? null,
            'group_id' => $validated['group_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_acting_director' => $validated['is_acting_director'] ?? false,
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // คง role "admin" ไว้เสมอ ถ้าผู้ใช้เป็นผู้ดูแลระบบอยู่แล้ว
        // (หน้านี้ไม่มี admin ใน dropdown — กันไม่ให้สิทธิ์ admin หลุดตอนแก้ไข)
        $roles = $validated['roles'] ?? [];
        if ($user->hasRole('admin')) {
            $roles[] = 'admin';
        }
        $user->syncRoles(array_values(array_unique($roles)));

        return back()->with('success', "แก้ไขข้อมูล {$user->name} เรียบร้อยแล้ว");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตนเองได้');
        }

        $user->delete();

        return back()->with('success', 'ลบบุคลากรเรียบร้อยแล้ว');
    }

    private function validateData(Request $request, bool $creating, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users', 'username')->ignore($ignoreId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:6'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')],
            'is_acting_director' => ['boolean'],
        ]);
    }

    /**
     * รายการ role พร้อมป้ายไทย (สำหรับ dropdown ปฏิบัติหน้าที่/สิทธิ)
     * ไม่รวม "admin" — จัดการแยกที่หน้า "จัดการผู้ดูแลระบบ"
     *
     * @return array<int, array{name: string, label: string}>
     */
    private function roleOptions(): array
    {
        return RoleLabels::options(Role::orderBy('id')->pluck('name'), except: ['system_admin']);
    }
}

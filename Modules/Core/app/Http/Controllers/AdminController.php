<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Support\RoleLabels;
use Spatie\Permission\Models\Role;

/**
 * จัดการ Admin / ผู้ดูแลระบบ — CRUD ผู้ใช้พร้อมกำหนดสิทธิ์ (roles) ของ Spatie
 */
class AdminController extends Controller
{
    /**
     * รายชื่อผู้ดูแลระบบ (ผู้ที่มี role admin) + รายการ role ทั้งหมด
     */
    public function index(): Response
    {
        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'profile_image' => $u->profile_image,
                'roles' => $u->roles->pluck('name'),
            ]);

        return Inertia::render('Core::Admin/Admins', [
            'admins' => $admins,
            'roles' => RoleLabels::options(Role::orderBy('name')->pluck('name')),
        ]);
    }

    /**
     * เพิ่มผู้ดูแลระบบใหม่
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'profile_image' => $request->hasFile('photo')
                ? $request->file('photo')->store('avatars', 'public')
                : null,
        ]);

        // ผู้ดูแลระบบได้สิทธิ์ admin เสมอ
        $user->syncRoles(['system_admin']);

        return back()->with('success', "เพิ่มผู้ดูแลระบบ {$user->name} เรียบร้อยแล้ว");
    }

    /**
     * รายละเอียดผู้ดูแลระบบ (JSON)
     */
    public function show(User $admin): JsonResponse
    {
        return response()->json([
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => $admin->getRoleNames(),
        ]);
    }

    /**
     * แก้ไขผู้ดูแลระบบ + สิทธิ์
     */
    public function update(Request $request, User $admin): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // แก้ไข: username ไม่บังคับ — เว้นว่างไว้เพื่อคงค่าเดิม (รองรับบัญชีเก่าที่ยังไม่มี username)
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($admin->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $admin->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        if (! empty($validated['username'])) {
            $admin->username = $validated['username'];
        }
        if (! empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        }
        if ($request->hasFile('photo')) {
            if ($admin->profile_image) {
                Storage::disk('public')->delete($admin->profile_image);
            }
            $admin->profile_image = $request->file('photo')->store('avatars', 'public');
        }
        $admin->save();

        // คงสิทธิ์ admin ไว้เสมอ
        $admin->syncRoles(['system_admin']);

        return back()->with('success', "แก้ไขผู้ดูแลระบบ {$admin->name} เรียบร้อยแล้ว");
    }

    /**
     * ลบผู้ดูแลระบบ (กันลบตัวเอง และกันลบ admin คนสุดท้าย)
     */
    public function destroy(Request $request, User $admin): RedirectResponse
    {
        if ($admin->id === $request->user()->id) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตนเองได้');
        }

        $adminCount = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
        if ($adminCount <= 1 && $admin->hasRole('admin')) {
            return back()->with('error', 'ต้องมีผู้ดูแลระบบอย่างน้อย 1 คน');
        }

        $admin->delete();

        return back()->with('success', 'ลบผู้ดูแลระบบเรียบร้อยแล้ว');
    }

    /**
     * ลบหลายรายการพร้อมกัน (เลือกจากตาราง)
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = collect($validated['ids'])->reject(fn ($id) => (int) $id === $request->user()->id);

        // ต้องเหลือ admin อย่างน้อย 1 คนเสมอ
        $remainingAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))
            ->whereNotIn('id', $ids)
            ->count();
        if ($remainingAdmins < 1) {
            return back()->with('error', 'ต้องมีผู้ดูแลระบบอย่างน้อย 1 คน');
        }

        $deleted = User::whereIn('id', $ids)->delete();

        return back()->with('success', "ลบผู้ดูแลระบบ {$deleted} รายการเรียบร้อยแล้ว");
    }
}

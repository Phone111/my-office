<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles & Permissions (spatie/laravel-permission)
 * - 16 บทบาท จัดเป็น 6 กลุ่ม
 * - Permissions พื้นฐานของระบบ e-Office + sync เข้ากับ role ที่เหมาะสม
 * - idempotent (รันซ้ำได้ ใช้ firstOrCreate / syncPermissions)
 */
class RoleAndPermissionSeeder extends Seeder
{
    /** role 5 ระดับ */
    private const ROLES = ['system_admin', 'executive', 'head', 'officer', 'staff'];

    /** สิทธิ์เพิ่มของหน้าที่ระดับผู้บริหาร: ผอ. ได้จัดการสิทธิ์ผู้ใช้ (manage_roles), รองผอ. ไม่ได้ */
    private const DUTY_PERMISSIONS = [
        'director' => ['manage_roles'],
        'deputy_director' => [],
    ];

    /** permissions พื้นฐาน */
    private const PERMISSIONS = [
        'view_dashboard',
        'manage_users',
        'manage_roles',
        'manage_documents',
        'approve_documents',
        'register_documents',
        'manage_leaves',
        'approve_leaves',
        'manage_attendance',
        'manage_bookings',
        'manage_vehicles',
        'approve_vehicles',
        'manage_budget',
        'manage_supervision',
        'manage_students',
        'manage_certificates',
        'manage_news',
        'manage_krs',
        'import_data',
        'view_reports',
    ];

    /** map role -> permissions (system_admin = ทั้งหมด กำหนดแยกด้านล่าง) */
    private const ROLE_PERMISSIONS = [
        // ผู้บริหาร (ฐาน) = ชุดของรองผอ. — ผอ. ได้ manage_roles เพิ่มจากหน้าที่ย่อย director
        'executive' => ['approve_documents', 'approve_leaves', 'approve_vehicles', 'manage_news', 'manage_certificates', 'manage_supervision', 'manage_budget', 'view_reports', 'view_dashboard'],
        'head' => ['approve_documents', 'approve_leaves', 'view_reports', 'view_dashboard'],
        'officer' => ['manage_documents', 'register_documents', 'manage_leaves', 'approve_leaves', 'manage_attendance', 'manage_budget', 'manage_supervision', 'manage_students', 'manage_certificates', 'manage_news', 'manage_krs', 'approve_vehicles', 'import_data', 'view_reports', 'view_dashboard'],
        'staff' => ['manage_documents', 'manage_leaves', 'manage_attendance', 'manage_bookings', 'view_dashboard'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) สร้าง permissions พื้นฐาน
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2) สร้าง roles 5 ระดับ + sync permissions
        foreach (self::ROLES as $name) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($name === 'system_admin') {
                $role->syncPermissions(Permission::where('guard_name', 'web')->get());
            } else {
                $role->syncPermissions(self::ROLE_PERMISSIONS[$name] ?? []);
            }
        }

        // 3) หน้าที่ระดับผู้บริหาร (ผอ./รองผอ.) — sync สิทธิ์เพิ่มเฉพาะตำแหน่ง
        foreach (self::DUTY_PERMISSIONS as $name => $perms) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

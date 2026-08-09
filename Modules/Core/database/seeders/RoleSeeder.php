<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ล้าง cache ของ spatie/laravel-permission ก่อน seed
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ระดับบทบาทหลัก 5 ระดับ + หน้าที่ย่อย (duty) ที่มอบเพิ่มให้เจ้าหน้าที่
        $roles = array_merge(
            ['system_admin', 'executive', 'head', 'officer', 'staff'],
            // หน้าที่ย่อย — role จริงที่คุมการเห็นเมนู/เข้าถึงแบบละเอียด (ผอ./รองผอ. = หน้าที่ระดับผู้บริหาร)
            ['director', 'deputy_director', 'secretary', 'saraban', 'school_clerk', 'group_clerk', 'supervisor', 'leave_officer', 'budget_officer', 'vehicle_booking_officer', 'krs_officer'],
        );

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}

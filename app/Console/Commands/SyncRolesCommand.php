<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * แปลงระบบ role เดิม (16) → 6 ระดับ + 9 หน้าที่ย่อย แบบ idempotent
 * ใช้ตอน deploy ขึ้น prod ที่ยังเป็น role ชุดเก่า (รันซ้ำได้ปลอดภัย)
 */
class SyncRolesCommand extends Command
{
    protected $signature = 'roles:sync-new';

    protected $description = 'สร้าง role 5 ระดับ + หน้าที่ย่อย (รวม ผอ./รองผอ.), remap ผู้ใช้จากชื่อเดิม, ลบ role เก่า (idempotent)';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) สร้าง roles + permissions ชุดใหม่ (firstOrCreate — รันซ้ำได้)
        (new \Modules\Core\Database\Seeders\RoleSeeder())->run();
        (new \Modules\Core\Database\Seeders\RoleAndPermissionSeeder())->run();

        $newRoles = array_merge(User::LEVELS, User::DUTIES);

        // ผอ./รองผอ. เดิม (ระดับเดียว) → ระดับ "ผู้บริหาร" + หน้าที่ย่อย ผอ./รองผอ.
        $expand = [
            'school_executive' => ['executive', 'director'],
            'director' => ['executive', 'director'],
            'school_deputy' => ['executive', 'deputy_director'],
            'deputy_director' => ['executive', 'deputy_director'],
        ];

        // 2) remap ผู้ใช้: ชื่อเดิม → ใหม่ และถ้าได้แต่หน้าที่ย่อย (ไม่มีระดับ) → เติม officer
        $count = 0;
        foreach (User::with('roles')->get() as $u) {
            $legacy = $u->roles->pluck('name')->all();
            if (! $legacy) {
                continue;
            }
            $translated = collect($legacy)
                ->flatMap(fn ($r) => $expand[$r] ?? [User::newRoleFor($r)])
                ->unique()->values();
            $hasLevel = $translated->intersect(User::LEVELS)->isNotEmpty();
            $hasDuty = $translated->intersect(User::DUTIES)->isNotEmpty();
            if ($hasDuty && ! $hasLevel) {
                $translated->push('officer');
            }
            $u->syncRoles($translated->all());
            $count++;
        }
        $this->info("remap ผู้ใช้: {$count} คน");

        // 3) ลบ role เก่าที่ไม่ใช่ชุดใหม่
        $deleted = Role::whereNotIn('name', $newRoles)->delete();
        $this->info("ลบ role เก่า: {$deleted}");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('roles คงเหลือ: '.Role::orderBy('id')->pluck('name')->implode(', '));
        $this->info('ผู้ใช้ไม่มี role: '.User::doesntHave('roles')->count());

        return self::SUCCESS;
    }
}

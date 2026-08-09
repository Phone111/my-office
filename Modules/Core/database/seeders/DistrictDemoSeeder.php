<?php

namespace Modules\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\Unit;

/**
 * ข้อมูลสาธิตระบบเขต — โรงเรียนที่ 2 + บัญชีสารบรรณปลายทาง + ผูกหน่วยงานบัญชีเขต
 * (สำหรับทดสอบรับส่งหนังสือข้ามหน่วยงาน)
 */
class DistrictDemoSeeder extends Seeder
{
    public function run(): void
    {
        $area = Unit::area()->first();
        $home = Unit::schools()->orderBy('id')->first();

        // โรงเรียนที่ 2 (ปลายทางทดสอบ)
        $school2 = Unit::firstOrCreate(
            ['name' => 'โรงเรียนสาธิตทดสอบ', 'type' => Unit::TYPE_SCHOOL],
            ['parent_id' => $area?->id, 'code' => 'TEST02', 'is_active' => true],
        );

        // ผูกหน่วยงานให้บัญชีเขต
        if ($u = User::where('username', 'area_admin')->first()) {
            $u->update(['unit_id' => $area?->id]);
        }
        if ($u = User::where('username', 'krs_officer')->first()) {
            $u->update(['unit_id' => $home?->id]);
        }

        // สารบรรณปลายทาง (โรงเรียนที่ 2) — สำหรับทดสอบ "ลงทะเบียนรับ"
        $recv = User::updateOrCreate(
            ['email' => 'saraban2@office.local'],
            [
                'name' => 'นางสมหญิง รับส่ง (สารบรรณ ร.ร.2)',
                'username' => 'saraban2',
                'phone' => '081-666-7777',
                'unit_id' => $school2->id,
                'password' => Hash::make('123456'),
            ],
        );
        $recv->syncRoles(['saraban']);

        // สารบรรณโรงเรียน (school_clerk) — บัญชีทดสอบฝั่งโรงเรียน
        $clerk = User::updateOrCreate(
            ['email' => 'school_clerk@office.local'],
            [
                'name' => 'นางสมศรี ธุรการ (สารบรรณ ร.ร.สาธิต)',
                'username' => 'school_clerk',
                'phone' => '081-888-9999',
                'unit_id' => $school2->id,
                'password' => Hash::make('123456'),
            ],
        );
        $clerk->syncRoles(['school_clerk']);
    }
}

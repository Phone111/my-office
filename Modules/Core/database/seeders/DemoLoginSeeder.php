<?php

namespace Modules\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\Position;

/**
 * บัญชีทดสอบเข้าสู่ระบบ — role ละ 1 บัญชี (รหัสผ่าน 123456)
 * username = ชื่อ role, email = <role>@office.local
 */
class DemoLoginSeeder extends Seeder
{
    public function run(): void
    {
        // username = ชื่อ role, name = ชื่อบุคคล (สมมติ) + (ตำแหน่ง) สำหรับใช้ทดสอบ
        $accounts = [
            ['role' => 'admin', 'name' => 'นายเอกชัย วงศ์ระบบ', 'position' => 'ผู้ดูแลระบบ', 'phone' => '081-111-1111'],
            ['role' => 'director', 'name' => 'นายสมศักดิ์ ศรีสุวรรณ', 'position' => 'ผู้อำนวยการ', 'phone' => '081-234-5678'],
            ['role' => 'deputy_director', 'name' => 'นางสาวสุดารัตน์ ทองดี', 'position' => 'รองผู้อำนวยการ', 'phone' => '089-876-5432'],
            ['role' => 'head_of_department', 'name' => 'นายประสิทธิ์ แก้วมณี', 'position' => 'ผู้ช่วยรองฯ/หัวหน้ากลุ่ม', 'phone' => '086-555-7890'],
            ['role' => 'head_of_subject', 'name' => 'นางมาลัย ใจดีงาม', 'position' => 'หัวหน้ากลุ่มสาระ', 'phone' => '092-345-6789'],
            ['role' => 'teacher', 'name' => 'นายสมชาย รักเรียน', 'position' => 'ครู', 'phone' => '084-567-8901'],
            ['role' => 'group_clerk', 'name' => 'นางสาวกานดา สมบูรณ์', 'position' => 'ธุรการกลุ่ม', 'phone' => '095-678-9012'],
            ['role' => 'saraban', 'name' => 'นางวิภา เลิศสกุล', 'position' => 'เจ้าหน้าที่สารบรรณกลาง', 'phone' => '087-789-0123'],
            ['role' => 'secretary', 'name' => 'นางสาวนภัสสร พงษ์ไพบูลย์', 'position' => 'เลขานุการ', 'phone' => '090-890-1234'],
            ['role' => 'leave_officer', 'name' => 'นายธนากร ศรีโสภา', 'position' => 'เจ้าหน้าที่งานวันลา', 'phone' => '083-901-2345'],
            ['role' => 'budget_officer', 'name' => 'นางพรทิพย์ มั่งมีสุข', 'position' => 'เจ้าหน้าที่แผนงานและงบประมาณ', 'phone' => '094-012-3456'],
            ['role' => 'vehicle_booking_officer', 'name' => 'นายอนุชา ยานยนต์', 'position' => 'เจ้าหน้าที่รับจองรถ', 'phone' => '088-123-4567'],
            // ระบบเขต (AMSS++)
            ['role' => 'area_admin', 'name' => 'นางอารยา เขตบริหาร', 'position' => 'ผู้ดูแลระบบเขต', 'phone' => '081-222-3333'],
            ['role' => 'krs_officer', 'name' => 'นายคำรับ รองราชการ', 'position' => 'จนท.คำรับรองปฏิบัติราชการ', 'phone' => '081-444-5555'],
        ];

        $execRoles = ['admin', 'director', 'deputy_director', 'head_of_department', 'head_of_subject'];

        foreach ($accounts as $a) {
            // ผูกตำแหน่ง (สร้างให้ถ้ายังไม่มี)
            $type = $a['role'] === 'teacher'
                ? Position::TYPE_ACADEMIC
                : (in_array($a['role'], $execRoles, true) ? Position::TYPE_EXECUTIVE : Position::TYPE_STAFF);
            $position = Position::firstOrCreate(['name' => $a['position']], ['type' => $type, 'is_active' => true]);

            $user = User::updateOrCreate(
                ['email' => $a['role'].'@office.local'],
                [
                    'name' => $a['name'].' ('.$a['position'].')',
                    'username' => $a['role'],
                    'phone' => $a['phone'],
                    'position_id' => $position->id,
                    'password' => Hash::make('123456'),
                ],
            );
            $user->syncRoles([$a['role']]);
        }
    }
}

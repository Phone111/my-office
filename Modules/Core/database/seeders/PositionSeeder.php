<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Position;

/**
 * ตำแหน่งเริ่มต้นของสถานศึกษา (อ้างอิงระบบเดิม)
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'ผู้ช่วยผู้อำนวยการ', 'type' => Position::TYPE_EXECUTIVE],
            ['name' => 'ผู้อำนวยการ', 'type' => Position::TYPE_EXECUTIVE],
            ['name' => 'รองผู้อำนวยการ', 'type' => Position::TYPE_EXECUTIVE],
            ['name' => 'หัวหน้างาน', 'type' => Position::TYPE_EXECUTIVE],
            ['name' => 'ครู', 'type' => Position::TYPE_ACADEMIC],
            ['name' => 'ครู ช่วยราชการ', 'type' => Position::TYPE_ACADEMIC],
            ['name' => 'ครูผู้ช่วย', 'type' => Position::TYPE_ACADEMIC],
            ['name' => 'ครูพี่เลี้ยง', 'type' => Position::TYPE_ACADEMIC],
            ['name' => 'คนขับรถ', 'type' => Position::TYPE_STAFF],
            ['name' => 'คนสวน', 'type' => Position::TYPE_STAFF],
            ['name' => 'ครูอัตราจ้าง', 'type' => Position::TYPE_ACADEMIC],
            ['name' => 'เจ้าหน้าที่ธุรการ', 'type' => Position::TYPE_STAFF],
            ['name' => 'เจ้าหน้าที่สำนักงาน', 'type' => Position::TYPE_STAFF],
            ['name' => 'นักการภารโรง', 'type' => Position::TYPE_STAFF],
            ['name' => 'พนักงานราชการ', 'type' => Position::TYPE_STAFF],
            ['name' => 'แม่บ้าน', 'type' => Position::TYPE_STAFF],
            ['name' => 'ยาม', 'type' => Position::TYPE_STAFF],
            ['name' => 'ลูกจ้างชั่วคราว', 'type' => Position::TYPE_STAFF],
            ['name' => 'ลูกจ้างประจำ', 'type' => Position::TYPE_STAFF],
            ['name' => 'วิทยากรภาษาอังกฤษ', 'type' => Position::TYPE_ACADEMIC],
        ];

        foreach ($positions as $i => $p) {
            Position::firstOrCreate(
                ['name' => $p['name']],
                ['type' => $p['type'], 'is_active' => true, 'sort_order' => $i + 1],
            );
        }
    }
}

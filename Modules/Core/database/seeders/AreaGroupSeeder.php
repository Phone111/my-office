<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Group;
use Modules\Core\Models\Unit;

/**
 * กลุ่มงานของสำนักงานเขตพื้นที่การศึกษา (สพป./สพม.) — ตามโครงสร้างมาตรฐาน
 */
class AreaGroupSeeder extends Seeder
{
    public function run(): void
    {
        $area = Unit::area()->first();
        if (! $area) {
            return;
        }

        $groups = [
            'กลุ่มอำนวยการ',
            'กลุ่มบริหารงานบุคคล',
            'กลุ่มนโยบายและแผน',
            'กลุ่มส่งเสริมการจัดการศึกษา',
            'กลุ่มนิเทศ ติดตามและประเมินผลการจัดการศึกษา',
            'กลุ่มบริหารงานการเงินและสินทรัพย์',
            'กลุ่มส่งเสริมการศึกษาทางไกล เทคโนโลยีสารสนเทศและการสื่อสาร (DLICT)',
            'กลุ่มพัฒนาครูและบุคลากรทางการศึกษา',
            'กลุ่มกฎหมายและคดี',
            'กลุ่มส่งเสริมการศึกษาเอกชน',
            'หน่วยตรวจสอบภายใน',
        ];

        foreach ($groups as $i => $name) {
            Group::firstOrCreate(
                ['unit_id' => $area->id, 'name' => $name],
                ['type' => Group::TYPE_OFFICE, 'level' => $i + 1, 'is_active' => true],
            );
        }
    }
}

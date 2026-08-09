<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\SupervisionRound;
use Modules\Core\Models\SupervisionStandard;

/**
 * ข้อมูลตั้งต้นกรอบการนิเทศ — มาตรฐาน/ตัวชี้วัด (อิงคู่มือ AMSS++ หน้า 25-26) + รอบปัจจุบัน
 */
class SupervisionFrameworkSeeder extends Seeder
{
    public function run(): void
    {
        $standards = [
            ['256001', 'ด้านผู้เรียน', [
                'ผู้เรียนมีคุณลักษณะอันพึงประสงค์ตามที่สถานศึกษากำหนด',
                'ผู้เรียนมีความรู้และทักษะตามหลักสูตร',
            ]],
            ['256002', 'ด้านการจัดกระบวนการเรียนรู้', [
                'ครูจัดการเรียนรู้ที่เน้นผู้เรียนเป็นสำคัญ (Active Learning)',
                'ครูใช้สื่อ เทคโนโลยี และแหล่งเรียนรู้',
                'ครูวัดและประเมินผลตามสภาพจริง',
            ]],
            ['256003', 'ด้านการบริหาร', [
                'มีหลักสูตรสถานศึกษาและแผนพัฒนาคุณภาพการศึกษา',
                'การบริหารแบบมีส่วนร่วม',
            ]],
            ['256004', 'ด้านการประกันคุณภาพภายใน', [
                'มีระบบประกันคุณภาพภายในที่ดำเนินงานต่อเนื่อง',
            ]],
            ['256005', 'ด้านสภาพแวดล้อมที่ส่งเสริมให้ผู้เรียนพัฒนาเต็มศักยภาพ', [
                'สภาพแวดล้อมและบรรยากาศเอื้อต่อการเรียนรู้',
            ]],
            ['256006', 'ด้านการดำเนินงานเพื่อสัมฤทธิผลตามนโยบาย สพฐ.', [
                'ดำเนินงานตามนโยบายและจุดเน้นของ สพฐ.',
            ]],
        ];

        foreach ($standards as $si => [$code, $name, $indicators]) {
            $standard = SupervisionStandard::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'sort' => $si, 'is_active' => true],
            );
            foreach ($indicators as $ii => $indName) {
                $standard->indicators()->firstOrCreate(
                    ['name' => $indName],
                    ['weight' => 1, 'sort' => $ii, 'is_active' => true],
                );
            }
        }

        // รอบปัจจุบัน (ถ้ายังไม่มีรอบที่ตั้งเป็นปัจจุบัน)
        if (! SupervisionRound::where('is_current', true)->exists()) {
            SupervisionRound::firstOrCreate(
                ['name' => 'ภาคเรียนที่ 1 ปีการศึกษา 2569 ครั้งที่ 1'],
                ['academic_year' => 2569, 'semester' => 1, 'is_current' => true, 'is_active' => true],
            );
        }
    }
}

<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\EvaluationCriteria;
use Modules\Core\Models\EvaluationRound;

/**
 * ข้อมูลตั้งต้นการประเมินผลปฏิบัติงาน (แนว ว.PA: 2 องค์ประกอบ) + รอบปัจจุบัน
 */
class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            ['การประเมินประสิทธิภาพและประสิทธิผลการปฏิบัติงาน', 70, 0],
            ['การประเมินการรักษาวินัย คุณธรรม จริยธรรม และจรรยาบรรณวิชาชีพ', 30, 1],
        ];
        foreach ($criteria as [$name, $max, $sort]) {
            EvaluationCriteria::firstOrCreate(['name' => $name], ['max_score' => $max, 'sort' => $sort, 'is_active' => true]);
        }

        if (! EvaluationRound::where('is_current', true)->exists()) {
            EvaluationRound::firstOrCreate(
                ['name' => 'ครั้งที่ 1 ปีงบประมาณ 2569 (1 ต.ค. 68 - 31 มี.ค. 69)'],
                ['fiscal_year' => 2569, 'period' => 1, 'is_current' => true, 'is_active' => true],
            );
        }
    }
}

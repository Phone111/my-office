<?php

namespace Modules\Leave\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Leave\Models\LeaveBalance;
use Modules\Leave\Models\LeaveType;

class LeaveDatabaseSeeder extends Seeder
{
    /**
     * ประเภทการลา + ยอดวันลาเริ่มต้นของผู้ใช้ตัวอย่าง
     */
    public function run(): void
    {
        $types = [
            ['code' => 'sick', 'name' => 'ลาป่วย', 'default_days' => 30],
            ['code' => 'personal', 'name' => 'ลากิจส่วนตัว', 'default_days' => 10],
            ['code' => 'maternity', 'name' => 'ลาคลอดบุตร', 'default_days' => 90],
            ['code' => 'official', 'name' => 'ขออนุญาตไปราชการ', 'default_days' => 0],
        ];

        foreach ($types as $t) {
            LeaveType::updateOrCreate(['code' => $t['code']], $t);
        }

        // ตั้งยอดวันลาปีปัจจุบันให้ผู้ใช้ตัวอย่าง (เฉพาะประเภทที่มีโควต้า) — ปีงบประมาณ พ.ศ.
        $year = now()->month >= 10 ? now()->year + 544 : now()->year + 543;
        $users = User::whereIn('email', [
            'teacher@example.com', 'head@example.com', 'director@example.com', 'admin@example.com',
        ])->get();

        $quotaTypes = LeaveType::where('default_days', '>', 0)->get();

        foreach ($users as $user) {
            foreach ($quotaTypes as $type) {
                LeaveBalance::updateOrCreate(
                    ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year],
                    ['entitled_days' => $type->default_days],
                );
            }
        }
    }
}

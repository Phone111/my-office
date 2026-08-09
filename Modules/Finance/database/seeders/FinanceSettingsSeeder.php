<?php

namespace Modules\Finance\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceMaster;
use Modules\Finance\Models\FinanceOfficer;

/**
 * ข้อมูลตัวอย่างหน้า "ตั้งค่าระบบการเงิน" (อิงคู่มือ AMSS การเงินฯ ส่วน 1)
 * รันซ้ำได้ ไม่สร้างข้อมูลซ้ำ (firstOrCreate)
 *   php artisan db:seed --class="Modules\Finance\Database\Seeders\FinanceSettingsSeeder"
 */
class FinanceSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2569;

        // 1.2 ปีงบประมาณ — ตั้ง 2569 เป็นปีปัจจุบัน
        FinanceFiscalYear::query()->update(['is_current' => false]);
        FinanceFiscalYear::updateOrCreate(['year' => $year], ['is_current' => true]);

        // 1.3 แผนงาน
        $this->masters('plan', $year, [
            ['1', 'แผนงานพื้นฐานด้านการพัฒนาและเสริมสร้างศักยภาพคน'],
            ['2', 'แผนงานยุทธศาสตร์พัฒนาการศึกษาเพื่อความยั่งยืน'],
            ['3', 'แผนงานบูรณาการยกระดับคุณภาพการศึกษา'],
            ['4', 'แผนงานสนับสนุนการจัดการศึกษาขั้นพื้นฐาน 15 ปี'],
        ]);

        // 1.4 ผลผลิต/โครงการ
        $this->masters('project', $year, [
            ['2000404001000000', 'ผู้จบการศึกษาภาคบังคับ'],
            ['2000404002000000', 'ผู้จบการศึกษาก่อนประถมศึกษา'],
            ['2000417702709003', 'พัฒนาคุณภาพและมาตรฐานการศึกษา'],
        ]);

        // 1.5 กิจกรรมหลัก
        $this->masters('activity', $year, [
            ['20004340000000', 'การจัดการศึกษาประถมศึกษาสำหรับโรงเรียนปกติ'],
            ['200043400G2557', 'การจัดการศึกษามัธยมศึกษาตอนต้นสำหรับโรงเรียนปกติ'],
            ['200043400G2559', 'พัฒนาผู้นำธรรมชาติ'],
        ]);

        // 1.6 แหล่งของเงิน
        $this->masters('fund_source', $year, [
            ['5511210', 'ค่าตอบแทน/งบส่วนราชการ'],
            ['5511220', 'ค่าใช้สอย/งบส่วนราชการ'],
            ['5511230', 'ค่าวัสดุ/งบส่วนราชการ'],
            ['5511310', 'ค่าครุภัณฑ์/งบส่วนราชการ'],
            ['5526000', 'เงินอุดหนุน/นอกงบประมาณ'],
        ]);

        // 1.7 งบรายจ่าย (main_type = รหัสงบหลัก 1-7)
        $this->mastersMain('expense_category', [
            ['110', 'เงินเดือน', '1'],
            ['120', 'ค่าจ้างประจำ', '1'],
            ['210', 'ค่าตอบแทน', '2'],
            ['220', 'ค่าใช้สอย', '2'],
            ['230', 'ค่าวัสดุ', '2'],
            ['310', 'ค่าครุภัณฑ์', '3'],
            ['320', 'ค่าที่ดินและสิ่งก่อสร้าง', '3'],
            ['410', 'เงินอุดหนุนทั่วไป', '4'],
            ['510', 'รายจ่ายอื่น', '5'],
            ['610', 'ค่ารักษาพยาบาล', '6'],
        ]);

        // 1.9 ประเภท(ย่อย)ของเงิน — นอกงบ(main 1) + รายได้แผ่นดิน(main 3)
        $this->mastersMain('money_type', [
            ['110', 'เงินประกันสัญญา', '1'],
            ['120', 'เงินอาหารกลางวัน', '1'],
            ['130', 'เงินรายได้สถานศึกษา', '1'],
            ['310', 'รายได้ค่าปรับอื่น', '3'],
            ['320', 'รายได้ค่าธรรมเนียมอื่น', '3'],
            ['330', 'รายได้ค่าขายของเบ็ดเตล็ด', '3'],
            ['340', 'รายได้เบ็ดเตล็ดอื่น', '3'],
            ['350', 'รายได้เงินเหลือจ่ายปีเก่าส่งคืน', '3'],
            ['360', 'รายได้กรมธนารักษ์', '3'],
        ]);

        // 1.1 เจ้าหน้าที่การเงิน — มอบสิทธิ์ให้ จนท.งบประมาณ + ผู้ดูแลระบบ
        $fullRights = [
            'can_approve' => true, 'fund_allocation' => true, 'can_withdraw' => true,
            'file_petition' => true, 'budget_money' => true, 'nonbudget_money' => true,
            'state_revenue' => true, 'advance_money' => true, 'pay_money' => true, 'view_reports' => true,
        ];
        $users = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['budget_officer', 'system_admin', 'admin']))
            ->take(3)->get();
        foreach ($users as $u) {
            FinanceOfficer::updateOrCreate(['user_id' => $u->id], $fullRights);
        }

        $this->command?->info('Finance settings sample data seeded (year '.$year.', officers: '.$users->count().').');
    }

    /** master ที่ผูกปีงบประมาณ */
    private function masters(string $type, int $year, array $rows): void
    {
        foreach ($rows as $i => [$code, $name]) {
            FinanceMaster::firstOrCreate(
                ['type' => $type, 'fiscal_year' => $year, 'code' => $code],
                ['name' => $name, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }
    }

    /** master ที่มีกลุ่มหลัก (งบรายจ่าย/ประเภทเงิน) — ใช้ได้ทุกปี */
    private function mastersMain(string $type, array $rows): void
    {
        foreach ($rows as $i => [$code, $name, $main]) {
            FinanceMaster::firstOrCreate(
                ['type' => $type, 'code' => $code],
                ['name' => $name, 'main_type' => $main, 'fiscal_year' => null, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }
    }
}

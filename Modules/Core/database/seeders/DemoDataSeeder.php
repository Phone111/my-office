<?php

namespace Modules\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Announcement\Models\News;
use Modules\Attendance\Models\Attendance;
use Modules\Executive\Models\ProjectBudget;
use Modules\Executive\Models\StaffAward;
use Modules\Executive\Models\StaffTraining;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveType;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentCounter;

/**
 * ข้อมูลตัวอย่าง (สมมติ) สำหรับให้หน้ารายงาน/ทะเบียนของผู้บริหารมีข้อมูลแสดง
 * ปลอดภัย: seed เฉพาะตารางที่ยังว่าง/มีน้อย (รันซ้ำไม่เพิ่มซ้ำเกิน)
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::orderBy('id')->get()->keyBy('username');
        $by = fn (string $u) => $users[$u] ?? $users->first();
        $admin = $by('admin');
        $today = Carbon::today();
        $be = $today->year + 543; // พ.ศ.

        // ===== 1) ลงเวลาวันนี้ =====
        if (Attendance::whereDate('date', $today)->count() < 3) {
            $checkin = [
                ['director', '07:48', 'present'],
                ['deputy_director', '07:55', 'present'],
                ['secretary', '08:05', 'present'],
                ['head_of_department', '08:12', 'present'],
                ['head_of_subject', '08:20', 'present'],
                ['teacher', '08:41', 'late'],
                ['saraban', '08:02', 'present'],
                ['budget_officer', '08:48', 'late'],
                ['vehicle_booking_officer', '07:59', 'present'],
            ];
            foreach ($checkin as [$uname, $time, $status]) {
                Attendance::firstOrCreate(
                    ['user_id' => $by($uname)->id, 'date' => $today->toDateString()],
                    ['check_in_time' => $time, 'status' => $status],
                );
            }
        }

        // ===== 2) ประเภทการลา + ใบลา =====
        // reuse ประเภทเดิมถ้ามี (จับคู่ด้วยชื่อหรือรหัส) มิฉะนั้นสร้างใหม่ด้วยรหัสที่ไม่ชน
        $resolveType = function (string $name, string $code) {
            $t = LeaveType::where('name', $name)->orWhere('code', $code)->first();
            if ($t) {
                return $t;
            }
            $safeCode = LeaveType::where('code', $code)->exists() ? $code.'_demo' : $code;

            return LeaveType::create(['name' => $name, 'code' => $safeCode, 'default_days' => 0, 'is_active' => true]);
        };

        $types = [
            'sick' => $resolveType('ลาป่วย', 'sick'),
            'personal' => $resolveType('ลากิจส่วนตัว', 'personal'),
            'vacation' => $resolveType('ลาพักผ่อน', 'vacation'),
            // ไปราชการ: ใช้ประเภทที่ชื่อมีคำว่า "ราชการ" ถ้ามี (ให้ทะเบียนไปราชการกรองเจอ)
            'official' => LeaveType::where('name', 'like', '%ราชการ%')->first() ?? $resolveType('ไปราชการ', 'official'),
        ];

        if (LeaveRequest::count() < 3) {
            $leaves = [
                ['teacher', 'official', $today->copy()->subDay(), $today->copy()->addDay(), 3, 'อบรมเชิงปฏิบัติการพัฒนาหลักสูตร ณ สพฐ.', 'approved'],
                ['secretary', 'official', $today, $today, 1, 'ประชุมผู้บริหารระดับเขตพื้นที่', 'approved'],
                ['group_clerk', 'sick', $today->copy()->subDays(2), $today->copy()->subDays(2), 1, 'ป่วยเป็นไข้หวัด', 'approved'],
                ['head_of_subject', 'vacation', $today->copy()->addDays(5), $today->copy()->addDays(6), 2, 'ลาพักผ่อนประจำปี', 'approved'],
                ['saraban', 'personal', $today->copy()->addDays(3), $today->copy()->addDays(3), 1, 'ไปทำธุระที่อำเภอ', 'pending'],
            ];
            foreach ($leaves as [$uname, $code, $start, $end, $days, $reason, $status]) {
                LeaveRequest::create([
                    'user_id' => $by($uname)->id,
                    'leave_type_id' => $types[$code]->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'total_days' => $days,
                    'reason' => $reason,
                    'status' => $status,
                ]);
            }
        }

        // ===== 3) เอกสารสารบรรณ + เลขทะเบียน =====
        if (Document::count() < 3) {
            $docs = [
                ['memo', 'บค', 'ขออนุมัติจัดกิจกรรมวันสุนทรภู่', 'approved'],
                ['memo', 'บค', 'รายงานผลการอบรมครู', 'approved'],
                ['incoming', 'รับ', 'หนังสือเชิญประชุมจาก สพป.', 'approved'],
                ['incoming', 'รับ', 'แจ้งจัดสรรงบประมาณประจำปี', 'approved'],
                ['outgoing', 'ส่ง', 'ส่งรายงานข้อมูลนักเรียนรายบุคคล', 'approved'],
                ['order', 'คส', 'คำสั่งแต่งตั้งคณะกรรมการดำเนินงานกีฬาสี', 'approved'],
                ['order', 'คส', 'คำสั่งมอบหมายเวรรักษาการณ์ประจำเดือน', 'approved'],
                ['general', 'ทป', 'เอกสารทั่วไป - ประชาสัมพันธ์ทุนการศึกษา', 'pending'],
            ];
            $seq = ['บค' => 0, 'รับ' => 0, 'ส่ง' => 0, 'คส' => 0, 'ทป' => 0];
            foreach ($docs as $i => [$cat, $prefix, $title, $status]) {
                $seq[$prefix]++;
                Document::create([
                    'category' => $cat,
                    'title' => $title,
                    'document_number' => sprintf('%s %04d/%d', $prefix, $seq[$prefix], $be),
                    'number_issued_at' => $today->copy()->subDays(8 - $i),
                    'content' => 'เอกสารตัวอย่างสำหรับทดสอบระบบ',
                    'status' => $status,
                    'creator_id' => $by('saraban')->id,
                ]);
            }
        }

        // ===== 4) ตัวนับเลขทะเบียน =====
        foreach (['memo' => 2, 'incoming' => 2, 'outgoing' => 1, 'order' => 2, 'general' => 1, 'certificate' => 5] as $book => $last) {
            DocumentCounter::firstOrCreate(['book' => $book, 'year' => $be], ['last_no' => $last]);
        }

        // ===== 5) ข่าว / ประกาศ =====
        if (News::count() < 3) {
            $news = [
                ['ประกาศรับสมัครนักเรียนใหม่ ปีการศึกษา '.$be, 'ข่าวรับสมัครงาน', 'งานวิชาการ'],
                ['ขอเชิญร่วมงานวันไหว้ครู ประจำปี '.$be, 'ข่าวกิจกรรม', 'งานกิจการนักเรียน'],
                ['ประกาศผลการสอบคัดเลือกครูอัตราจ้าง', 'ข่าวประชาสัมพันธ์', 'งานบุคคล'],
                ['ประกาศจัดซื้อจัดจ้างครุภัณฑ์คอมพิวเตอร์', 'ข่าวจัดซื้อจัดจ้าง', 'งานพัสดุ'],
            ];
            foreach ($news as $i => [$title, $cat, $author]) {
                News::create([
                    'title' => $title,
                    'category' => $cat,
                    'excerpt' => 'รายละเอียดย่อของ'.$title,
                    'content' => 'เนื้อหาประกาศตัวอย่างสำหรับทดสอบระบบ',
                    'author' => $author,
                    'allow_comments' => false,
                    'creator_id' => $admin->id,
                    'created_at' => $today->copy()->subDays($i * 2),
                ]);
            }
        }

        // ===== 6) งบประมาณรายโครงการ =====
        if (ProjectBudget::count() < 1) {
            $budgets = [
                ['โครงการพัฒนาการอ่านออกเขียนได้', 500000, 320000],
                ['โครงการส่งเสริมคุณธรรมจริยธรรม', 200000, 200000],
                ['โครงการจัดซื้อสื่อการเรียนการสอน', 350000, 90000],
                ['โครงการกีฬาสีภายในโรงเรียน', 150000, 120000],
            ];
            foreach ($budgets as [$name, $alloc, $disb]) {
                ProjectBudget::create([
                    'project_name' => $name,
                    'fiscal_year' => (string) $be,
                    'allocated_amount' => $alloc,
                    'disbursed_amount' => $disb,
                    'created_by' => $admin->id,
                ]);
            }
        }

        // ===== 7) การอบรมบุคลากร =====
        if (StaffTraining::count() < 1) {
            $trainings = [
                ['นายสมชาย รักเรียน', 'การจัดการเรียนรู้เชิงรุก (Active Learning)', 'สพฐ.', 12, 'โรงแรมเซ็นทรา'],
                ['นางมาลัย ใจดีงาม', 'การวัดและประเมินผลตามสภาพจริง', 'มหาวิทยาลัยราชภัฏ', 6, 'ออนไลน์'],
                ['นางสาวกานดา สมบูรณ์', 'ระบบสารบรรณอิเล็กทรอนิกส์', 'สำนักงานเขตพื้นที่', 6, 'ห้องประชุมเขต'],
                ['นายประสิทธิ์ แก้วมณี', 'ภาวะผู้นำทางวิชาการ', 'สถาบันพัฒนาครู', 18, 'จังหวัดเชียงใหม่'],
            ];
            foreach ($trainings as $i => [$staff, $course, $org, $hours, $loc]) {
                StaffTraining::create([
                    'staff_name' => $staff,
                    'course_name' => $course,
                    'organizer' => $org,
                    'start_date' => $today->copy()->subDays(20 + $i * 7)->toDateString(),
                    'end_date' => $today->copy()->subDays(19 + $i * 7)->toDateString(),
                    'hours' => $hours,
                    'location' => $loc,
                    'created_by' => $admin->id,
                ]);
            }
        }

        // ===== 8) รางวัลบุคลากร =====
        if (StaffAward::count() < 1) {
            $awards = [
                ['นายสมชาย รักเรียน', 'ครูดีเด่น', 'เขตพื้นที่', 'สพป.'],
                ['นางมาลัย ใจดีงาม', 'ครูผู้สอนดีเด่นกลุ่มสาระวิทยาศาสตร์', 'จังหวัด', 'จังหวัด'],
                ['นายประสิทธิ์ แก้วมณี', 'รางวัลหนึ่งแสนครูดี', 'ชาติ', 'คุรุสภา'],
            ];
            foreach ($awards as $i => [$staff, $award, $level, $bywhom]) {
                StaffAward::create([
                    'staff_name' => $staff,
                    'award_name' => $award,
                    'level' => $level,
                    'awarded_by' => $bywhom,
                    'awarded_date' => $today->copy()->subDays(30 + $i * 15)->toDateString(),
                    'created_by' => $admin->id,
                ]);
            }
        }
    }
}

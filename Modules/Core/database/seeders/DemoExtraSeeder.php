<?php

namespace Modules\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\MeetingRoom;
use Modules\Booking\Models\Vehicle;
use Modules\Core\Models\DevelopmentPlan;
use Modules\Core\Models\Group;
use Modules\Executive\Models\ExecutiveEvent;
use Modules\Saraban\Models\Certificate;
use Modules\Saraban\Models\Circular;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentRoute;

/**
 * เติมข้อมูลตัวอย่างส่วนที่ยังว่าง ให้หน้าต่างๆ ของแต่ละ role มีของแสดง
 * (ปลอดภัย: seed เฉพาะตารางที่ยังว่าง)
 */
class DemoExtraSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all()->keyBy('username');
        $by = fn (string $u) => $users[$u] ?? $users->first();
        $admin = $by('admin');
        $director = $by('director');
        $today = Carbon::today();
        $be = $today->year + 543;

        // ===== 1) ปฏิทินผู้บริหาร =====
        if (ExecutiveEvent::count() === 0) {
            $events = [
                ['ประชุมผู้บริหารสถานศึกษา', 'ห้องประชุมใหญ่', 1, 9, 12],
                ['รับการนิเทศติดตามจากเขตพื้นที่', 'ห้องโสตทัศนศึกษา', 3, 13, 16],
                ['พิธีไหว้ครู ประจำปี '.$be, 'หอประชุมโรงเรียน', 5, 8, 11],
                ['ประชุมคณะกรรมการสถานศึกษา', 'ห้องประชุมเล็ก', 8, 13, 15],
                ['กิจกรรมกีฬาสีภายใน', 'สนามกีฬาโรงเรียน', 12, 8, 16],
            ];
            foreach ($events as [$title, $loc, $offset, $sh, $eh]) {
                $d = $today->copy()->addDays($offset);
                ExecutiveEvent::create([
                    'title' => $title,
                    'description' => 'กิจกรรมตามปฏิทินปฏิบัติงานผู้บริหาร',
                    'location' => $loc,
                    'start_at' => $d->copy()->setTime($sh, 0),
                    'end_at' => $d->copy()->setTime($eh, 0),
                    'all_day' => false,
                    'created_by' => $director->id,
                ]);
            }
        }

        // ===== 2) การจองทรัพยากร =====
        if (Booking::count() === 0) {
            $v1 = Vehicle::first();
            $v2 = Vehicle::skip(1)->first() ?? $v1;
            $r1 = MeetingRoom::first();
            $r2 = MeetingRoom::skip(1)->first() ?? $r1;

            $bookings = [
                [Vehicle::class, $v1?->id, 'teacher', 1, 8, 16, 'นำนักเรียนไปแข่งขันทักษะวิชาการ'],
                [Vehicle::class, $v2?->id, 'vehicle_booking_officer', 2, 9, 12, 'รับ-ส่งเอกสารราชการที่เขตพื้นที่'],
                [MeetingRoom::class, $r1?->id, 'secretary', 1, 13, 15, 'ประชุมเตรียมงานวันสำคัญ'],
                [MeetingRoom::class, $r2?->id, 'head_of_subject', 3, 9, 12, 'อบรมเชิงปฏิบัติการกลุ่มสาระ'],
            ];
            foreach ($bookings as [$type, $id, $uname, $offset, $sh, $eh, $purpose]) {
                if (! $id) {
                    continue;
                }
                $d = $today->copy()->addDays($offset);
                Booking::create([
                    'bookable_type' => $type,
                    'bookable_id' => $id,
                    'user_id' => $by($uname)->id,
                    'start_at' => $d->copy()->setTime($sh, 0),
                    'end_at' => $d->copy()->setTime($eh, 0),
                    'purpose' => $purpose,
                    'status' => Booking::STATUS_BOOKED,
                ]);
            }
        }

        // ===== 3) ID Plan ของบุคลากร =====
        if (DevelopmentPlan::count() === 0) {
            $plans = [
                ['teacher', 'พัฒนาการจัดการเรียนรู้เชิงรุก และยกระดับผลสัมฤทธิ์วิชาวิทยาศาสตร์'],
                ['head_of_subject', 'พัฒนาหลักสูตรกลุ่มสาระ และนิเทศการสอนเพื่อนครู'],
                ['group_clerk', 'พัฒนาทักษะงานธุรการและระบบสารบรรณอิเล็กทรอนิกส์'],
            ];
            foreach ($plans as [$uname, $goals]) {
                DevelopmentPlan::create([
                    'user_id' => $by($uname)->id,
                    'academic_year' => (string) $be,
                    'goals' => $goals,
                ]);
            }
        }

        // ===== 4) หนังสือราชการภายใน (เวียน) =====
        if (Circular::count() === 0) {
            Circular::create([
                'title' => 'แจ้งกำหนดการประชุมประจำเดือน',
                'content' => 'ขอเชิญข้าราชการครูและบุคลากรทุกท่านเข้าร่วมประชุมประจำเดือน ในวันศุกร์ที่จะถึงนี้ เวลา 15.30 น. ณ ห้องประชุมใหญ่',
                'sender_id' => $director->id,
                'sender_group_id' => Group::where('name', 'like', '%บริหาร%')->value('id'),
                'target_users' => User::whereIn('username', ['teacher', 'head_of_subject', 'group_clerk', 'saraban', 'secretary'])->pluck('id')->all(),
                'attachments' => [],
            ]);
        }

        // ===== 6) ทะเบียนเกียรติบัตร =====
        if (Certificate::count() === 0) {
            $saraban = $by('saraban');
            $certs = [
                ['นายสมชาย รักเรียน', 'ครูผู้สอนดีเด่น กลุ่มสาระการเรียนรู้วิทยาศาสตร์'],
                ['นางมาลัย ใจดีงาม', 'ผ่านการอบรมเชิงปฏิบัติการพัฒนาหลักสูตรสถานศึกษา'],
                ['นางสาวกานดา สมบูรณ์', 'บุคลากรดีเด่นด้านงานธุรการและสารบรรณ'],
                ['นายประสิทธิ์ แก้วมณี', 'ผู้บริหารสถานศึกษาดีเด่น ประจำปีการศึกษา '.$be],
            ];
            foreach ($certs as $i => [$name, $title]) {
                Certificate::create([
                    'certificate_number' => sprintf('กบ %04d/%d', $i + 1, $be),
                    'title' => $title,
                    'recipient_name' => $name,
                    'issued_date' => $today->copy()->subDays(($i + 1) * 7)->toDateString(),
                    'issuer_id' => $saraban->id,
                ]);
            }
        }

        // ===== 5) เอกสารรอ ผอ. อนุมัติ (เอกสารรอดำเนินการ) =====
        if (! DocumentRoute::where('approver_id', $director->id)->where('status', DocumentRoute::STATUS_PENDING)->exists()) {
            $pending = [
                ['teacher', 'บันทึกขออนุมัติจัดกิจกรรมเข้าค่ายวิชาการ'],
                ['group_clerk', 'บันทึกขออนุมัติใช้รถยนต์ราชการไปราชการ'],
            ];
            foreach ($pending as $i => [$uname, $title]) {
                $doc = Document::create([
                    'category' => Document::CATEGORY_MEMO,
                    'title' => $title,
                    'document_number' => sprintf('บค %04d/%d', 900 + $i, $be),
                    'number_issued_at' => $today,
                    'content' => 'รายละเอียดตามบันทึกข้อความที่แนบ',
                    'status' => Document::STATUS_PENDING,
                    'creator_id' => $by($uname)->id,
                ]);
                DocumentRoute::create([
                    'document_id' => $doc->id,
                    'step_order' => 1,
                    'approver_id' => $director->id,
                    'status' => DocumentRoute::STATUS_PENDING,
                ]);
            }
        }
    }
}

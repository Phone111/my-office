<?php

namespace Modules\Saraban\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\ApprovalFlow;
use Modules\Core\Models\Department;
use Modules\Saraban\Models\Certificate;

class SarabanDatabaseSeeder extends Seeder
{
    /**
     * ข้อมูลตัวอย่างสำหรับเดินเส้นทางอนุมัติ:
     * กลุ่มงาน 1 กลุ่ม + เส้นทาง 2 ขั้น (หัวหน้ากลุ่มงาน -> ผู้อำนวยการ)
     * พร้อมผู้ใช้ครู / หัวหน้ากลุ่มงาน / ผู้อำนวยการ
     */
    public function run(): void
    {
        $department = Department::firstOrCreate(
            ['code' => 'SCI'],
            ['name' => 'กลุ่มสาระการเรียนรู้วิทยาศาสตร์']
        );

        // เส้นทางอนุมัติ: ขั้น 1 หัวหน้ากลุ่มงาน, ขั้น 2 ผู้อำนวยการ
        ApprovalFlow::updateOrCreate(
            ['department_id' => $department->id, 'step_order' => 1],
            ['approver_role_name' => 'head_of_department']
        );
        ApprovalFlow::updateOrCreate(
            ['department_id' => $department->id, 'step_order' => 2],
            ['approver_role_name' => 'director']
        );

        // ครู (ผู้ส่งเอกสาร) — สังกัดกลุ่มงานวิทยาศาสตร์
        $teacher = User::updateOrCreate(
            ['email' => 'teacher@example.com'],
            ['name' => 'ครูสมชาย', 'password' => Hash::make('password'), 'department_id' => $department->id]
        );
        $teacher->syncRoles(['teacher']);

        // หัวหน้ากลุ่มงาน (ผู้อนุมัติขั้น 1)
        $head = User::updateOrCreate(
            ['email' => 'head@example.com'],
            ['name' => 'หัวหน้ากลุ่มสาระวิทย์', 'password' => Hash::make('password'), 'department_id' => $department->id]
        );
        $head->syncRoles(['head_of_department']);

        // ผู้อำนวยการ (ผู้อนุมัติขั้น 2) — ระดับโรงเรียน
        $director = User::updateOrCreate(
            ['email' => 'director@example.com'],
            ['name' => 'ผู้อำนวยการโรงเรียน', 'password' => Hash::make('password')]
        );
        $director->syncRoles(['director']);

        // เจ้าหน้าที่สารบรรณ — ออกเลขทะเบียน/เลขเกียรติบัตร
        $saraban = User::updateOrCreate(
            ['email' => 'saraban@example.com'],
            ['name' => 'เจ้าหน้าที่สารบรรณ', 'password' => Hash::make('password'), 'department_id' => $department->id]
        );
        $saraban->syncRoles(['saraban']);

        // ตัวอย่างทะเบียนเลขเกียรติบัตร
        $year = (int) now()->year + 543;
        $samples = [
            ['ครูสมชาย ใจดี', 'อบรมเชิงปฏิบัติการการจัดการเรียนรู้เชิงรุก (Active Learning)'],
            ['เด็กหญิงมานี รักเรียน', 'การแข่งขันตอบปัญหาวิทยาศาสตร์ ระดับเขตพื้นที่'],
        ];

        foreach ($samples as $index => [$recipient, $title]) {
            Certificate::firstOrCreate(
                ['certificate_number' => sprintf('%04d/%d', $index + 1, $year)],
                [
                    'title' => $title,
                    'recipient_name' => $recipient,
                    'issued_date' => now()->toDateString(),
                    'issuer_id' => $saraban->id,
                ]
            );
        }

        // ตั้งตัวนับเลขเกียรติบัตรให้ต่อจากตัวอย่างที่ seed ไว้
        \Modules\Saraban\Models\DocumentCounter::updateOrCreate(
            ['book' => 'certificate', 'year' => $year],
            ['last_no' => count($samples)]
        );
    }
}

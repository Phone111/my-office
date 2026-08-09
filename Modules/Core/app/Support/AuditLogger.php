<?php

namespace Modules\Core\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\AuditLog;

/**
 * ตัวช่วยบันทึก Audit Log — บันทึกว่าใครทำอะไรกับข้อมูลสำคัญ
 */
class AuditLogger
{
    /** ฟิลด์ที่ไม่ต้องบันทึกการเปลี่ยนแปลง (ลด noise) */
    private const IGNORE = ['updated_at', 'created_at', 'remember_token', 'password', 'last_no'];

    /** ป้ายชื่อฟิลด์ (ไทย) สำหรับสรุปการแก้ไข */
    private const FIELD_LABELS = [
        'name' => 'ชื่อ', 'username' => 'ชื่อผู้ใช้', 'email' => 'อีเมล', 'phone' => 'เบอร์โทร',
        'unit_id' => 'หน่วยงาน', 'group_id' => 'กลุ่ม', 'department_id' => 'กลุ่มสาระ', 'position_id' => 'ตำแหน่ง',
        'profile_image' => 'รูปโปรไฟล์', 'citizen_id' => 'เลขบัตรประชาชน', 'birthdate' => 'วันเกิด', 'gender' => 'เพศ',
        'code' => 'รหัส', 'address' => 'ที่อยู่', 'book_prefix' => 'คำนำหน้าเลขหนังสือ', 'is_active' => 'สถานะใช้งาน',
        'license_plate' => 'ทะเบียนรถ', 'seats' => 'จำนวนที่นั่ง', 'title' => 'เรื่อง', 'score' => 'คะแนน',
        'status' => 'สถานะ', 'standing' => 'วิทยฐานะ', 'education_level' => 'วุฒิการศึกษา', 'appointed_date' => 'วันบรรจุ',
    ];

    public static function log(string $action, ?Model $subject = null, ?string $description = null): void
    {
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'ระบบ',
            'action' => $action,
            'auditable_type' => $subject ? class_basename($subject) : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description ? mb_substr($description, 0, 500) : null,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /** สรุปฟิลด์ที่เปลี่ยน (สำหรับ event updated) — แปลงเป็นชื่อไทย */
    public static function changedFields(Model $model): string
    {
        $fields = array_diff(array_keys($model->getChanges()), self::IGNORE);

        return collect($fields)->map(fn ($f) => self::FIELD_LABELS[$f] ?? $f)->implode(', ');
    }
}

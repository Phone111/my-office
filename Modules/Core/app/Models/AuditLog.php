<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บันทึกการใช้งาน 1 รายการ
 */
class AuditLog extends Model
{
    public $timestamps = false; // ใช้ created_at อย่างเดียว

    protected $fillable = [
        'user_id', 'user_name', 'action', 'auditable_type', 'auditable_id', 'description', 'ip', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    /** ป้ายชื่อการกระทำ (ไทย) */
    public const ACTION_LABELS = [
        'created' => 'เพิ่ม',
        'updated' => 'แก้ไข',
        'deleted' => 'ลบ',
        'destroy' => 'ทำลายหนังสือ',
        'restore' => 'กู้คืน',
        'role' => 'มอบ/ถอดสิทธิ์',
    ];

    /** ป้ายชื่อชนิดข้อมูล (ไทย) */
    public const TYPE_LABELS = [
        'User' => 'บัญชีผู้ใช้',
        'Document' => 'หนังสือ',
        'PersonnelProfile' => 'ทะเบียน ก.พ.7',
        'AreaCertificate' => 'เกียรติบัตร',
        'Evaluation' => 'ผลประเมิน',
        'Unit' => 'หน่วยงาน/โรงเรียน',
        'Vehicle' => 'รถยนต์',
    ];

    public static function typeLabel(?string $t): string
    {
        return self::TYPE_LABELS[$t] ?? $t ?? '—';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function actionLabel(string $a): string
    {
        return self::ACTION_LABELS[$a] ?? $a;
    }
}

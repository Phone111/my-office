<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * การนิเทศการศึกษา — เขต (ศึกษานิเทศก์) ออกนิเทศโรงเรียนในสังกัด
 * วงจร: วางแผน/บันทึก → นิเทศแล้ว (สรุปผล+ข้อเสนอแนะ) → โรงเรียนรับทราบ/ตอบกลับ
 */
class Supervision extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';        // วางแผน/รอนิเทศ
    public const STATUS_COMPLETED = 'completed';     // นิเทศแล้ว รอโรงเรียนรับทราบ
    public const STATUS_ACKNOWLEDGED = 'acknowledged'; // โรงเรียนรับทราบแล้ว

    public const STATUSES = [
        self::STATUS_PLANNED => 'วางแผน/รอนิเทศ',
        self::STATUS_COMPLETED => 'นิเทศแล้ว (รอโรงเรียนรับทราบ)',
        self::STATUS_ACKNOWLEDGED => 'โรงเรียนรับทราบแล้ว',
    ];

    /** ด้านการนิเทศ (4 งานบริหาร + วิชาการ) */
    public const ASPECTS = [
        'academic' => 'ด้านวิชาการ/การจัดการเรียนการสอน',
        'budget' => 'ด้านงบประมาณ',
        'personnel' => 'ด้านบริหารงานบุคคล',
        'general' => 'ด้านบริหารทั่วไป',
    ];

    public const RATINGS = [
        'excellent' => 'ดีมาก',
        'good' => 'ดี',
        'fair' => 'พอใช้',
        'improve' => 'ควรปรับปรุง',
    ];

    /** ระดับคุณภาพ/ปริมาณรายตัวชี้วัด (1-5) ตามคู่มือ AMSS */
    public const QUALITY = [
        1 => 'น้อยที่สุด',
        2 => 'น้อย',
        3 => 'ปานกลาง',
        4 => 'มาก',
        5 => 'มากที่สุด',
    ];

    protected $fillable = [
        'area_unit_id', 'school_unit_id', 'round_id', 'supervisor_id', 'visit_date', 'aspect', 'topic',
        'objective', 'findings', 'recommendations', 'rating', 'attachments', 'status',
        'school_response', 'acknowledged_at', 'acknowledged_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'attachments' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'area_unit_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'school_unit_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(SupervisionRound::class, 'round_id');
    }

    public function scores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupervisionScore::class);
    }
}

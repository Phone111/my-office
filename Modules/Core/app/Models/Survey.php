<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * แบบสอบถาม (Survey) — เขต/ผู้บริหารสร้าง เผยแพร่ให้ตอบ แล้วสรุปผล
 */
class Survey extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'ฉบับร่าง',
        self::STATUS_OPEN => 'เปิดให้ตอบ',
        self::STATUS_CLOSED => 'ปิดรับคำตอบ',
    ];

    public const TYPES = [
        'rating' => 'มาตรประมาณค่า (1-5)',
        'choice' => 'ตัวเลือก',
        'text' => 'ข้อความ/ความคิดเห็น',
    ];

    protected $fillable = [
        'unit_id', 'title', 'description', 'status', 'anonymous', 'opens_at', 'closes_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'anonymous' => 'boolean',
            'opens_at' => 'date',
            'closes_at' => 'date',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}

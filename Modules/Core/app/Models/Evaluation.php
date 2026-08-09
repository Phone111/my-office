<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_EVALUATED = 'evaluated';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUSES = [
        self::STATUS_DRAFT => 'ฉบับร่าง',
        self::STATUS_EVALUATED => 'ประเมินแล้ว (รอรับทราบ)',
        self::STATUS_ACKNOWLEDGED => 'ผู้รับการประเมินรับทราบแล้ว',
    ];

    protected $fillable = [
        'round_id', 'evaluee_id', 'evaluator_id', 'unit_id', 'total_score', 'percent', 'grade',
        'strengths', 'improvements', 'evaluator_comment', 'evaluee_note', 'status', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['total_score' => 'decimal:2', 'percent' => 'decimal:2', 'acknowledged_at' => 'datetime'];
    }

    /** ระดับผลจากร้อยละ (ตามเกณฑ์ราชการ) */
    public static function gradeFor(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'ดีเด่น',
            $percent >= 80 => 'ดีมาก',
            $percent >= 70 => 'ดี',
            $percent >= 60 => 'พอใช้',
            default => 'ต้องปรับปรุง',
        };
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(EvaluationRound::class, 'round_id');
    }

    public function evaluee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluee_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}

<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ว.PA — ข้อตกลงในการพัฒนางาน
 */
class PaAgreement extends Model
{
    protected $fillable = [
        'user_id', 'fiscal_year', 'position_type',
        'challenge_issue', 'challenge_problem', 'challenge_method',
        'challenge_outcome_quant', 'challenge_outcome_qual',
        'status', 'approver_id', 'approver_note', 'submitted_at', 'approved_at',
        'score', 'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    /** ด้านตามมาตรฐานตำแหน่ง (ครู) */
    public const ASPECTS = [
        1 => 'ด้านการจัดการเรียนรู้',
        2 => 'ด้านการส่งเสริมและสนับสนุนการจัดการเรียนรู้',
        3 => 'ด้านการพัฒนาตนเองและวิชาชีพ',
    ];

    public const STATUSES = [
        'draft' => 'ร่าง',
        'submitted' => 'เสนอ ผอ.',
        'approved' => 'ผอ.เห็นชอบแล้ว',
        'evaluated' => 'ประเมินแล้ว',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(PaTask::class, 'agreement_id')->orderBy('aspect')->orderBy('sort');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}

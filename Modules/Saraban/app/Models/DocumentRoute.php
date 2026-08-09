<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\ApprovalStep;

// use Modules\Saraban\Database\Factories\DocumentRouteFactory;

class DocumentRoute extends Model implements ApprovalStep
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'document_id',
        'step_order',
        'approver_id',
        'status',
        'comment',
        'acted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    /**
     * เอกสารที่ขั้นนี้สังกัด
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * ผู้อนุมัติของขั้นนี้
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * รายการแม่ (ApprovalStep contract)
     */
    public function approvable(): BelongsTo
    {
        return $this->document();
    }
}

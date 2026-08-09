<?php

namespace Modules\Leave\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\ApprovalStep;

// use Modules\Leave\Database\Factories\LeaveRequestRouteFactory;

class LeaveRequestRoute extends Model implements ApprovalStep
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'leave_request_id',
        'step_order',
        'approver_id',
        'status',
        'comment',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * รายการแม่ (ApprovalStep contract)
     */
    public function approvable(): BelongsTo
    {
        return $this->leaveRequest();
    }
}

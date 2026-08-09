<?php

namespace Modules\Leave\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\ApprovalStep;

class OfficialTripRoute extends Model implements ApprovalStep
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'official_trip_id', 'step_order', 'approver_id', 'status', 'comment', 'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    public function officialTrip(): BelongsTo
    {
        return $this->belongsTo(OfficialTrip::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvable(): BelongsTo
    {
        return $this->officialTrip();
    }
}

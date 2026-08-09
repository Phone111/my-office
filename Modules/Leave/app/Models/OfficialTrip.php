<?php

namespace Modules\Leave\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Contracts\Approvable;

class OfficialTrip extends Model implements Approvable
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const VEHICLES = [
        'official_car' => 'รถยนต์ราชการ',
        'private_car' => 'รถยนต์ส่วนตัว',
        'plane' => 'เครื่องบิน',
        'hired_car' => 'รถรับจ้าง',
        'other' => 'อื่น ๆ',
    ];

    protected $fillable = [
        'user_id', 'title', 'companions', 'purpose', 'destination', 'reference',
        'depart_at', 'return_at', 'vehicle_type', 'vehicle_plate', 'vehicle_other',
        'vehicle_id', 'vehicle_booking_id',
        'budget_source', 'document_number', 'attachments', 'status',
    ];

    protected function casts(): array
    {
        return [
            'depart_at' => 'datetime',
            'return_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(OfficialTripRoute::class)->orderBy('step_order');
    }

    public function currentRoute(): HasOne
    {
        return $this->hasOne(OfficialTripRoute::class)->where('status', OfficialTripRoute::STATUS_PENDING);
    }

    // ===== Approvable =====
    public function approvalRoutes(): HasMany
    {
        return $this->routes();
    }

    public function approvalCreator(): ?User
    {
        return $this->user;
    }

    public function setApprovalStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    public function approvalSubject(): string
    {
        return 'ขออนุมัติไปราชการ: '.$this->title;
    }

    public function approvalLink(): string
    {
        return route('official-trips.show', $this->id);
    }
}

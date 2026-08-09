<?php

namespace Modules\Leave\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Contracts\Approvable;

// use Modules\Leave\Database\Factories\LeaveRequestFactory;

class LeaveRequest extends Model implements Approvable
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'written_at',
        'written_date',
        'contact_address',
        'phone',
        'file_path',
        'status',
        'handover_to',
        'handover_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'written_date' => 'date',
            'total_days' => 'float',
            'handover_accepted_at' => 'datetime',
        ];
    }

    /** ผู้รับมอบงาน (ปฏิบัติหน้าที่แทน) */
    public function handoverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_to');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(LeaveRequestRoute::class)->orderBy('step_order');
    }

    public function currentRoute(): HasOne
    {
        return $this->hasOne(LeaveRequestRoute::class)
            ->where('status', LeaveRequestRoute::STATUS_PENDING);
    }

    // ===== Approvable contract =====

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

        // เมื่ออนุมัติครบ ให้ตัดวันลาออกจากยอดสะสม
        if ($status === self::STATUS_APPROVED) {
            $this->deductBalance();
        }
    }

    public function approvalSubject(): string
    {
        return 'ใบลา: '.($this->leaveType?->name ?? '').' '
            .$this->start_date->format('d/m/Y').' - '.$this->end_date->format('d/m/Y');
    }

    public function approvalLink(): string
    {
        return route('leave.requests.show', $this->id);
    }

    /**
     * ปีของยอดวันลา = ปีงบประมาณ พ.ศ. (1 ต.ค.–30 ก.ย.) — ตรงกับการเช็คตอนเสนอแฟ้มและทะเบียนวันลาสะสม
     */
    public function balanceYear(): int
    {
        $endYear = $this->start_date->month >= 10 ? $this->start_date->year + 1 : $this->start_date->year;

        return $endYear + 543;
    }

    /**
     * ตัดวันลาออกจากยอดสะสมของปีนั้น
     */
    private function deductBalance(): void
    {
        $year = $this->balanceYear();

        $balance = LeaveBalance::firstOrCreate(
            [
                'user_id' => $this->user_id,
                'leave_type_id' => $this->leave_type_id,
                'year' => $year,
            ],
            ['entitled_days' => $this->leaveType?->default_days ?? 0],
        );

        $balance->increment('used_days', $this->total_days);
    }

    /**
     * คืนวันลากลับยอดสะสม (ใช้เมื่อยกเลิกใบลาที่อนุมัติไปแล้ว)
     */
    public function restoreBalance(): void
    {
        $year = $this->balanceYear();

        $balance = LeaveBalance::where('user_id', $this->user_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            $balance->decrement('used_days', min($this->total_days, (float) $balance->used_days));
        }
    }
}

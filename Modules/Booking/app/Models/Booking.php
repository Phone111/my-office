<?php

namespace Modules\Booking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// use Modules\Booking\Database\Factories\BookingFactory;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_BOOKED = 'booked';
    public const STATUS_CANCELLED = 'cancelled';

    // สถานะ workflow ขอใช้รถ (ห้องประชุม = booked ตรง ไม่ผ่าน workflow)
    public const STATUS_PENDING = 'pending';       // รอเสนอแฟ้ม (ผู้ขอเพิ่งบันทึก)
    public const STATUS_SUBMITTED = 'submitted';   // เสนอเจ้าหน้าที่จัดรถ (เจ้าหน้าที่ตรวจสอบทะเบียนรถ)
    public const STATUS_ASSIGNED = 'assigned';     // จัดรถแล้ว รอผู้บริหารอนุมัติ
    public const STATUS_REJECTED = 'rejected';     // จัดรถไม่ได้/ไม่อนุมัติ

    public const FLOW_LABELS = [
        self::STATUS_PENDING => 'รอเสนอแฟ้ม',
        self::STATUS_SUBMITTED => 'เจ้าหน้าที่ตรวจสอบทะเบียนรถ',
        self::STATUS_ASSIGNED => 'รอผู้บริหารอนุมัติ',
        self::STATUS_BOOKED => 'อนุมัติแล้ว',
        self::STATUS_REJECTED => 'ไม่อนุมัติ/จัดรถไม่ได้',
        self::STATUS_CANCELLED => 'ยกเลิก',
    ];

    /** ป้ายกำกับแหล่งน้ำมันเชื้อเพลิง */
    public const FUEL_LABELS = [
        'central' => 'ส่วนกลาง',
        'project' => 'โครงการ',
        'user' => 'ผู้ใช้',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'bookable_type',
        'bookable_id',
        'user_id',
        'start_at',
        'end_at',
        'purpose',
        'status',
        'division',
        'companions',
        'destination',
        'passengers',
        'fuel_source',
        'fuel_station',
        'fuel_liters',
        'fuel_amount',
        'fuel_note',
        'fuel_filled_at',
        'attendees',
        'file_path',
        'written_date',
        'driver_name',
        'officer_id',
        'officer_comment',
        'approver_id',
        'approver_comment',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'written_date' => 'date',
            'passengers' => 'integer',
            'attendees' => 'integer',
            'fuel_liters' => 'decimal:2',
            'fuel_amount' => 'decimal:2',
            'fuel_filled_at' => 'datetime',
        ];
    }

    /**
     * ทรัพยากรที่ถูกจอง (รถ/ห้องประชุม)
     */
    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * ผู้จอง
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * เฉพาะการจองที่ยังใช้งาน (ไม่ถูกยกเลิก)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    /**
     * ตรวจว่ามีการจองที่ช่วงเวลาทับซ้อนกันหรือไม่
     *
     * ทับซ้อนเมื่อ: ที่มีอยู่.start < ใหม่.end  AND  ที่มีอยู่.end > ใหม่.start
     */
    public static function hasConflict(
        string $bookableType,
        int $bookableId,
        string $startAt,
        string $endAt,
        ?int $ignoreId = null
    ): bool {
        return self::query()
            ->active()
            ->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}

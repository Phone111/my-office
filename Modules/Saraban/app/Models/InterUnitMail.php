<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Unit;

/**
 * หนังสือราชการระหว่างหน่วยงาน — เขต ↔ โรงเรียน / โรงเรียน ↔ โรงเรียน
 */
class InterUnitMail extends Model
{
    use HasFactory;

    public const STATUS_SENT = 'sent';                      // ส่งแล้ว รอผู้รับลงทะเบียนรับ
    public const STATUS_RECEIVED = 'received';               // สารบรรณกลางลงทะเบียนรับแล้ว
    public const STATUS_ASSIGNED_GROUP = 'assigned_group';   // สารบรรณกลางมอบกลุ่มงานแล้ว รอสารบรรณกลุ่มมอบบุคคล
    public const STATUS_FORWARDED = 'forwarded';             // มอบให้บุคคลแล้ว

    public const PRIORITIES = [
        'normal' => 'ปกติ',
        'urgent' => 'ด่วน',
        'very_urgent' => 'ด่วนมาก',
        'most_urgent' => 'ด่วนที่สุด',
    ];

    protected $fillable = [
        'from_unit_id', 'to_unit_id', 'to_group_id', 'sender_id', 'tracking_no', 'number', 'send_seq', 'doc_date', 'subject',
        'detail', 'reference', 'priority', 'confidential', 'attachments', 'status',
        'receive_number', 'received_at', 'received_by', 'assigned_to', 'assigned_group_at', 'forwarded_at',
    ];

    public const STATUS_LABELS = [
        self::STATUS_SENT => 'ส่งแล้ว รอลงทะเบียนรับ',
        self::STATUS_RECEIVED => 'ลงทะเบียนรับแล้ว',
        self::STATUS_ASSIGNED_GROUP => 'มอบกลุ่มงานแล้ว รอสารบรรณกลุ่ม',
        self::STATUS_FORWARDED => 'มอบหมายให้บุคลากรแล้ว',
    ];

    protected function casts(): array
    {
        return [
            'doc_date' => 'date',
            'confidential' => 'boolean',
            'attachments' => 'array',
            'received_at' => 'datetime',
            'forwarded_at' => 'datetime',
            'assigned_group_at' => 'datetime',
        ];
    }

    public function toGroup(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Group::class, 'to_group_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

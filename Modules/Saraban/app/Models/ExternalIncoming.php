<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Group;
use Modules\Core\Models\Unit;

/**
 * หนังสือรับจากหน่วยงานภายนอก (เหนือเขต) — สพฐ. / ศธจ. / จังหวัด ฯลฯ
 */
class ExternalIncoming extends Model
{
    use HasFactory;

    protected $table = 'external_incoming_mails';

    public const STATUS_RECEIVED = 'received';   // ลงทะเบียนรับแล้ว รอมอบ
    public const STATUS_ASSIGNED = 'assigned';   // มอบกลุ่ม/บุคคลแล้ว

    /** หน่วยงานต้นทางมาตรฐาน */
    public const SOURCES = [
        'obec' => 'สพฐ.',
        'pesao' => 'ศธจ.',
        'province' => 'จังหวัด',
        'other_area' => 'สพป./สพม. อื่น',
        'other' => 'อื่น ๆ',
    ];

    public const PRIORITIES = [
        'normal' => 'ปกติ',
        'urgent' => 'ด่วน',
        'very_urgent' => 'ด่วนมาก',
        'most_urgent' => 'ด่วนที่สุด',
    ];

    protected $fillable = [
        'unit_id', 'source_type', 'source_name', 'number', 'doc_date', 'subject', 'detail',
        'priority', 'confidential', 'attachments', 'receive_no', 'receive_year',
        'received_at', 'received_by', 'assigned_group_id', 'assigned_to', 'note', 'status',
    ];

    protected function casts(): array
    {
        return [
            'doc_date' => 'date',
            'confidential' => 'boolean',
            'attachments' => 'array',
            'received_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'assigned_group_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}

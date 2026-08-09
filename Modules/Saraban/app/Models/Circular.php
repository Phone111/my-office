<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Group;

/**
 * หนังสือเวียนภายใน — broadcast ถึงกลุ่มงาน/กลุ่มสาระ (ไม่เข้า workflow อนุมัติ)
 */
class Circular extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'sender_id',
        'sender_group_id',
        'target_users',
        'attachments',
        'read_by',
        'filed_by',
        'is_meeting',
        'meeting_at',
        'meeting_place',
        'responses',
    ];

    /** สถานะการตอบรับเข้าประชุม */
    public const RSVP = [
        'accept' => 'เข้าร่วม',
        'decline' => 'ไม่เข้าร่วม',
        'delegate' => 'มอบผู้แทน',
    ];

    protected function casts(): array
    {
        return [
            'target_users' => 'array',
            'attachments' => 'array',
            'read_by' => 'array',
            'filed_by' => 'array',
            'is_meeting' => 'boolean',
            'meeting_at' => 'datetime',
            'responses' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function senderGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'sender_group_id');
    }
}

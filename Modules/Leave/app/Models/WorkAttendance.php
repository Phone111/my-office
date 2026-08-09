<?php

namespace Modules\Leave\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * การมาปฏิบัติราชการรายวันของบุคลากร 1 คน/1 วัน
 */
class WorkAttendance extends Model
{
    protected $fillable = ['user_id', 'work_date', 'status', 'note', 'recorded_by'];

    protected $casts = ['work_date' => 'date'];

    /** สถานะการปฏิบัติราชการ (รหัส => ป้ายชื่อ) */
    public const STATUSES = [
        'present' => 'มา',
        'trip' => 'ไปราชการ',
        'sick' => 'ลาป่วย',
        'personal' => 'ลากิจ',
        'maternity' => 'ลาคลอด',
        'other_leave' => 'ลาอื่นๆ',
        'late' => 'มาสาย',
        'absent' => 'ไม่มา',
    ];

    /** อักษรย่อสำหรับตารางรอบเดือน */
    public const ABBR = [
        'present' => 'มา',
        'trip' => 'ร',
        'sick' => 'ป',
        'personal' => 'ก',
        'maternity' => 'ค',
        'other_leave' => 'อ',
        'late' => 'ส',
        'absent' => '✗',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function label(?string $status): string
    {
        return self::STATUSES[$status] ?? '—';
    }
}

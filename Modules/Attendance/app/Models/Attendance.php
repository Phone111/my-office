<?php

namespace Modules\Attendance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Attendance\Database\Factories\AttendanceFactory;

class Attendance extends Model
{
    use HasFactory;

    /** สถานะการลงเวลา/ไม่ลงเวลา */
    public const STATUS_LABELS = [
        'present' => 'ปกติ',
        'late' => 'มาสาย',
        'leave' => 'ลา',
        'official' => 'ไปราชการ',
        'absent' => 'ขาด',
        'forgot' => 'ลืมลงเวลา',
    ];

    /** สถานะที่ "ลงเวลาแล้ว" (มาทำงานจริง) */
    public const CLOCKED_STATUSES = ['present', 'late'];

    /** สถานะสำหรับเจ้าหน้าที่บันทึกผู้ไม่ลงเวลา */
    public const ABSENCE_STATUSES = ['leave', 'official', 'absent', 'forgot'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'note',
        'recorded_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * เจ้าของการลงเวลา
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // protected static function newFactory(): AttendanceFactory
    // {
    //     // return AttendanceFactory::new();
    // }
}

<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ทะเบียนประวัติบุคลากร (ก.พ.7)
 */
class PersonnelProfile extends Model
{
    protected $fillable = [
        'user_id', 'citizen_id', 'birthdate', 'gender', 'appointed_date',
        'education_level', 'education_major', 'academic_standing',
        'academic_standing_date', 'rank', 'address', 'note', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'appointed_date' => 'date',
            'academic_standing_date' => 'date',
        ];
    }

    /** วิทยฐานะมาตรฐาน (ก.ค.ศ.) */
    public const STANDINGS = [
        'ครูผู้ช่วย',
        'ไม่มีวิทยฐานะ',
        'ชำนาญการ',
        'ชำนาญการพิเศษ',
        'เชี่ยวชาญ',
        'เชี่ยวชาญพิเศษ',
    ];

    public const EDUCATION_LEVELS = ['ต่ำกว่าปริญญาตรี', 'ปริญญาตรี', 'ปริญญาโท', 'ปริญญาเอก'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ประชากรวัยเรียน (ระบบสิทธิและโอกาสทางการศึกษา)
 */
class SchoolAgeChild extends Model
{
    use HasFactory;

    public const AGE_GROUPS = ['3-5' => 'อนุบาล (3-5 ปี)', '6-11' => 'ประถม (6-11 ปี)', '12-14' => 'ม.ต้น (12-14 ปี)'];

    /** สาเหตุไม่เข้าเรียน (อิงคู่มือ น.31) */
    public const REASONS = [
        'not_found' => 'ไม่พบตัวตนในพื้นที่',
        'dead' => 'เสียชีวิต',
        'disabled' => 'พิการ/เจ็บป่วยรุนแรง',
        'moved' => 'ย้ายออกนอกเขตพื้นที่',
        'poor' => 'ขาดแคลน ยากจนมาก',
        'married' => 'สมรส',
        'legal' => 'อยู่ในสถานพินิจ/ถูกดำเนินคดี',
        'unwilling' => 'ไม่ต้องการมาเรียน',
        'other' => 'สาเหตุอื่น ๆ',
    ];

    protected $fillable = [
        'unit_id', 'citizen_id', 'prename', 'name', 'surname', 'birthdate', 'age_group',
        'address', 'tambon', 'amphoe', 'province', 'service_school_id',
        'enrolled', 'enroll_school', 'non_enroll_reason', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'enrolled' => 'boolean'];
    }

    public function serviceSchool(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'service_school_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ผลสัมฤทธิ์ระดับชาติ (O-NET / NT / RT) — คะแนนเฉลี่ยรายโรงเรียน
 */
class AchievementResult extends Model
{
    protected $fillable = ['unit_id', 'test_type', 'academic_year', 'grade', 'subject', 'score', 'created_by'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    /** ประเภทการทดสอบ + ชั้นที่สอบ */
    public const TEST_TYPES = [
        'onet' => 'O-NET',
        'nt' => 'NT',
        'rt' => 'RT',
        'last' => 'ข้อสอบกลาง (LAST)',
    ];

    public const GRADES = [
        'onet' => ['ป.6', 'ม.3', 'ม.6'],
        'nt' => ['ป.3'],
        'rt' => ['ป.1'],
        'last' => ['ป.2', 'ป.4', 'ป.5', 'ม.1', 'ม.2'],
    ];

    /** วิชา/สมรรถนะ ของแต่ละประเภท (คีย์ => ป้ายไทย) */
    public const SUBJECTS = [
        'onet' => [
            'thai' => 'ภาษาไทย',
            'math' => 'คณิตศาสตร์',
            'science' => 'วิทยาศาสตร์',
            'english' => 'ภาษาอังกฤษ',
        ],
        'nt' => [
            'lang' => 'ด้านภาษาไทย',
            'calc' => 'ด้านคณิตศาสตร์',
        ],
        'rt' => [
            'aloud' => 'การอ่านออกเสียง',
            'comprehension' => 'การอ่านรู้เรื่อง',
        ],
        'last' => [
            'thai' => 'ภาษาไทย',
            'math' => 'คณิตศาสตร์',
            'science' => 'วิทยาศาสตร์',
            'english' => 'ภาษาอังกฤษ',
        ],
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

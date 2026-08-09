<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ทะเบียนนักเรียนรายคน (ระบบข้อมูลนักเรียน)
 */
class Student extends Model
{
    use HasFactory;

    public const GRADES = ['อ.1', 'อ.2', 'อ.3', 'ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6', 'ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6'];

    public const GENDERS = ['M' => 'ชาย', 'F' => 'หญิง'];

    public const STATUSES = [
        'studying' => 'กำลังเรียน',
        'graduated' => 'จบการศึกษา',
        'resigned' => 'ลาออก',
        'moved' => 'ย้าย',
    ];

    /** ประเภทความพิการ 9 ประเภท (ตาม สศศ.) — นักเรียนพิการเรียนรวม */
    public const DISABILITY_TYPES = [
        'บกพร่องทางการเห็น',
        'บกพร่องทางการได้ยิน',
        'บกพร่องทางสติปัญญา',
        'บกพร่องทางร่างกาย/สุขภาพ',
        'บกพร่องทางการเรียนรู้ (LD)',
        'บกพร่องทางการพูดและภาษา',
        'บกพร่องทางพฤติกรรม/อารมณ์',
        'ออทิสติก',
        'พิการซ้อน',
    ];

    protected $fillable = [
        'unit_id', 'student_code', 'citizen_id', 'prename', 'name', 'surname',
        'gender', 'birthdate', 'grade', 'room', 'status', 'disability', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

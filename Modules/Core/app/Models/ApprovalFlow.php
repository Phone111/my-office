<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * เส้นทางอนุมัติอัตโนมัติ กำหนดตามกลุ่มงาน (department)
 * เป็น config กลางที่ใช้ร่วมกันทุกโมดูล (Saraban, Leave, ...)
 */
class ApprovalFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'step_order',
        'approver_role_name',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * ดึงรายชื่อ role ตามลำดับขั้นของกลุ่มงานหนึ่ง (ใช้ป้อนให้ ApprovalWorkflowService)
     *
     * @return array<int, string>
     */
    public static function roleStepsFor(int $departmentId): array
    {
        return static::where('department_id', $departmentId)
            ->orderBy('step_order')
            ->pluck('approver_role_name')
            ->all();
    }
}

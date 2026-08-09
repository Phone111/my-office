<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancePetition extends Model
{
    protected $fillable = [
        'fiscal_year', 'type', 'petition_no', 'doc_no', 'allocation_id', 'plan_id',
        'project_id', 'activity_id', 'expense_category_id', 'title', 'amount', 'tax',
        'net', 'cancelled', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'net' => 'decimal:2',
            'cancelled' => 'boolean',
        ];
    }

    public const TYPES = [
        'treasury' => 'ขอเบิกเงินคงคลัง',
        'carryover' => 'เงินกันเหลื่อมปี',
    ];

    /** เลขที่ฎีกาถัดไป (กรณีไม่ระบุเอง) ต่อปีงบประมาณ */
    public static function nextNo(int $year): int
    {
        return (int) static::where('fiscal_year', $year)->max('id') + 1;
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(FinanceAllocation::class, 'allocation_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'project_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'expense_category_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(FinanceWithdrawal::class, 'petition_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

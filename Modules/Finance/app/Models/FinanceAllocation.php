<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAllocation extends Model
{
    protected $fillable = [
        'fiscal_year', 'voucher_no', 'doc_no', 'doc_date', 'allocation_ref',
        'plan_id', 'project_id', 'activity_id', 'activity_extra', 'fund_source_id',
        'account_code', 'expense_category_id', 'title', 'detail', 'amount',
        'received_at', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'voucher_no' => 'integer',
            'doc_date' => 'date',
            'received_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** เลขที่ใบงวดถัดไปของปีงบประมาณ */
    public static function nextVoucherNo(int $year): int
    {
        return (int) static::where('fiscal_year', $year)->max('voucher_no') + 1;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'plan_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'project_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'activity_id');
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'fund_source_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

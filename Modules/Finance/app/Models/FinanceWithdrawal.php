<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceWithdrawal extends Model
{
    protected $fillable = [
        'fiscal_year', 'doc_no', 'kind', 'title', 'project_id', 'activity_id',
        'expense_category_id', 'amount', 'borrower', 'petition_id', 'settled_at',
        'doc_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'settled_at' => 'date',
            'doc_date' => 'date',
        ];
    }

    public const KINDS = [
        'withdraw' => 'ขอเบิก',
        'borrow_budget' => 'ขอยืมเงินงบประมาณ',
        'borrow_nonbudget' => 'ขอยืมเงินนอกงบประมาณ',
        'borrow_advance' => 'ขอยืมเงินทดรองราชการ',
    ];

    public function isBorrow(): bool
    {
        return $this->kind !== 'withdraw';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'project_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'expense_category_id');
    }

    public function petition(): BelongsTo
    {
        return $this->belongsTo(FinancePetition::class, 'petition_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

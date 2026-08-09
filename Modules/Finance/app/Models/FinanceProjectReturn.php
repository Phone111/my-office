<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceProjectReturn extends Model
{
    protected $fillable = [
        'fiscal_year', 'doc_no', 'title', 'project_id', 'activity_id',
        'expense_category_id', 'amount', 'return_date', 'receipt_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'return_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'project_id');
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

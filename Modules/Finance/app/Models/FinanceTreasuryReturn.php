<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTreasuryReturn extends Model
{
    protected $fillable = [
        'fiscal_year', 'doc_no', 'allocation_id', 'plan_id', 'project_id', 'activity_id',
        'expense_category_id', 'title', 'amount', 'to_payment', 'return_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'to_payment' => 'boolean',
            'return_date' => 'date',
        ];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(FinanceAllocation::class, 'allocation_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(FinanceMaster::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

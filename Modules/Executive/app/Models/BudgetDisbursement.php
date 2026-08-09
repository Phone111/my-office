<?php

namespace Modules\Executive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetDisbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_budget_id', 'disburse_date', 'amount', 'description', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'disburse_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class, 'project_budget_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

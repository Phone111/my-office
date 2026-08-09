<?php

namespace Modules\Executive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Executive\Database\Factories\ProjectBudgetFactory;

class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name',
        'fiscal_year',
        'project_date',
        'allocated_amount',
        'disbursed_amount',
        'note',
        'file_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'project_date' => 'date',
            'allocated_amount' => 'decimal:2',
            'disbursed_amount' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(BudgetDisbursement::class)->latest('disburse_date');
    }

    /** ปรับยอดเบิกจ่ายรวม = ผลรวมรายการเบิกจ่าย */
    public function syncDisbursed(): void
    {
        $this->disbursed_amount = $this->disbursements()->sum('amount');
        $this->save();
    }

    /**
     * คงเหลือ (จัดสรร - เบิกจ่าย)
     */
    public function remaining(): float
    {
        return (float) $this->allocated_amount - (float) $this->disbursed_amount;
    }

    /**
     * ร้อยละการเบิกจ่าย
     */
    public function percentDisbursed(): float
    {
        $allocated = (float) $this->allocated_amount;

        return $allocated > 0 ? round((float) $this->disbursed_amount / $allocated * 100, 1) : 0.0;
    }
}

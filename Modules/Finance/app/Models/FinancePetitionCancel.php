<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePetitionCancel extends Model
{
    protected $fillable = [
        'fiscal_year', 'petition_id', 'petition_no', 'ref_doc', 'reason',
        'cancel_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'cancel_date' => 'date',
        ];
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

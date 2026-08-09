<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * งานตามมาตรฐานตำแหน่ง (ส่วนที่ 1 ของ ว.PA)
 */
class PaTask extends Model
{
    protected $fillable = ['agreement_id', 'aspect', 'task', 'expected_outcome', 'sort'];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(PaAgreement::class, 'agreement_id');
    }
}

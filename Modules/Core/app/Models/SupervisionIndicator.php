<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ตัวชี้วัดของมาตรฐานการจัดการศึกษา
 */
class SupervisionIndicator extends Model
{
    use HasFactory;

    protected $fillable = ['standard_id', 'name', 'weight', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(SupervisionStandard::class, 'standard_id');
    }
}

<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * มาตรฐานการจัดการศึกษา (สำหรับการนิเทศ)
 */
class SupervisionStandard extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(SupervisionIndicator::class, 'standard_id')->orderBy('sort');
    }
}

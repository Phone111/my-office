<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationRound extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'fiscal_year', 'period', 'is_current', 'is_active'];

    protected function casts(): array
    {
        return ['is_current' => 'boolean', 'is_active' => 'boolean'];
    }
}

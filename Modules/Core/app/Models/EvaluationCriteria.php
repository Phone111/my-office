<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriteria extends Model
{
    use HasFactory;

    protected $table = 'evaluation_criteria';

    protected $fillable = ['name', 'max_score', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['max_score' => 'decimal:2', 'is_active' => 'boolean'];
    }
}

<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * รอบการนิเทศ (รายภาคเรียน)
 */
class SupervisionRound extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'academic_year', 'semester', 'is_current', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

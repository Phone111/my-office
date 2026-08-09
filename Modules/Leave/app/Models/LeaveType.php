<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\Leave\Database\Factories\LeaveTypeFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'default_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

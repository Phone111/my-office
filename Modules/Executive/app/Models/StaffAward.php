<?php

namespace Modules\Executive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Executive\Database\Factories\StaffAwardFactory;

class StaffAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_name',
        'award_name',
        'level',
        'awarded_by',
        'awarded_date',
        'note',
        'file_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'awarded_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

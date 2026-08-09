<?php

namespace Modules\Executive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Executive\Database\Factories\ExecutiveEventFactory;

class ExecutiveEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'executive_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'all_day',
        'audience',
        'time_text',
        'days',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
            'audience' => 'array',
            'days' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }
}

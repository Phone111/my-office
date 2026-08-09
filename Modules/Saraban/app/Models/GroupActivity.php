<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Group;

class GroupActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id', 'activity_date', 'time_text', 'days', 'title', 'detail', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'days' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

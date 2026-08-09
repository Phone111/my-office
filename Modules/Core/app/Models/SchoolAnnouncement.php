<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAnnouncement extends Model
{
    protected $fillable = [
        'unit_id',
        'number',
        'year',
        'title',
        'announced_date',
        'attachments',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'announced_date' => 'date',
            'attachments' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

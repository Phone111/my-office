<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'title',
        'content',
        'file_path',
        'attachments',
        'read_at',
        'filed_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'filed_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}

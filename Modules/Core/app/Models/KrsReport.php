<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * การส่งรายงานตัวชี้วัดตามรอบ (6/9/12 เดือน)
 */
class KrsReport extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'indicator_id', 'round', 'reporter_id', 'file_path', 'note',
        'status', 'submitted_at', 'received_at', 'received_by',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'submitted_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KrsIndicator::class, 'indicator_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}

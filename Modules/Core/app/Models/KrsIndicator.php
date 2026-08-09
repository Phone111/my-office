<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ตัวชี้วัดตามคำรับรองปฏิบัติราชการ (KRS / ARS)
 */
class KrsIndicator extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'krs' => 'KRS',
        'ars1' => 'ARS กลยุทธ์ที่ 1',
        'ars2' => 'ARS กลยุทธ์ที่ 2',
        'ars3' => 'ARS กลยุทธ์ที่ 3',
        'ars4' => 'ARS กลยุทธ์ที่ 4',
    ];

    public const ROUNDS = [6, 9, 12];

    protected $fillable = [
        'year', 'category', 'code', 'name', 'reporter_id', 'receiver_id', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(KrsReport::class, 'indicator_id');
    }
}

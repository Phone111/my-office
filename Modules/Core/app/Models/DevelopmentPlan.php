<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * แผนพัฒนาตนเอง / ID Plan ของบุคลากร
 */
class DevelopmentPlan extends Model
{
    protected $fillable = [
        'user_id',
        'academic_year',
        'goals',
        'file_path',
    ];

    /**
     * เจ้าของแผน
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

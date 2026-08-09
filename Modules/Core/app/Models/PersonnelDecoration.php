<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * เครื่องราชอิสริยาภรณ์ของบุคลากร
 */
class PersonnelDecoration extends Model
{
    protected $fillable = ['user_id', 'name', 'year'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * กิจกรรมทั่วไป 1 รายการ (ปฏิทินกิจกรรมรวม)
 */
class Activity extends Model
{
    protected $fillable = [
        'title', 'location', 'start_at', 'end_at', 'all_day', 'detail',
        'group_id', 'unit_id', 'visibility', 'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    public const VISIBILITIES = [
        'all' => 'ทุกคนในหน่วยงาน',
        'group' => 'เฉพาะกลุ่ม',
        'private' => 'ส่วนตัว',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}

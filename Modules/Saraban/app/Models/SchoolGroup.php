<?php

namespace Modules\Saraban\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Models\Unit;

/**
 * กลุ่มโรงเรียน (กลุ่มสถานศึกษา) — รวมโรงเรียนหลายแห่งเพื่อส่งหนังสือทีเดียว (AMSS ส่วน 16)
 */
class SchoolGroup extends Model
{
    protected $fillable = ['unit_id', 'name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** เขตเจ้าของกลุ่ม */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /** โรงเรียนสมาชิกในกลุ่ม */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'school_group_members', 'school_group_id', 'unit_id');
    }
}

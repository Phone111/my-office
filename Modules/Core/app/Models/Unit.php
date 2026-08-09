<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * หน่วยงาน — สำนักงานเขต (area) และโรงเรียนในสังกัด (school)
 */
class Unit extends Model
{
    use HasFactory;

    public const TYPE_AREA = 'area';
    public const TYPE_SCHOOL = 'school';

    protected $fillable = [
        'name', 'code', 'book_prefix', 'type', 'parent_id', 'address', 'phone', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** เขตที่สังกัด (สำหรับโรงเรียน) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    /** โรงเรียนในสังกัด (สำหรับเขต) */
    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeArea($q)
    {
        return $q->where('type', self::TYPE_AREA);
    }

    public function scopeSchools($q)
    {
        return $q->where('type', self::TYPE_SCHOOL);
    }
}

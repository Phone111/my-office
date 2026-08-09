<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Core\Database\Factories\DepartmentFactory;

class Department extends Model
{
    use HasFactory;

    /** สิทธิการใช้ของกลุ่มสาระ */
    public const TYPE_EXECUTIVE = 'executive';
    public const TYPE_DEPARTMENT = 'department';

    public const TYPES = [
        self::TYPE_EXECUTIVE => 'กลุ่มผู้บริหาร',
        self::TYPE_DEPARTMENT => 'กลุ่มสาระ',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'group_id',
        'type',
        'sort_order',
        'code',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * กลุ่ม/ฝ่ายที่สังกัด (กลุ่มภาระกิจ)
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * level จากสิทธิการใช้ (ผู้บริหาร = 2, กลุ่มสาระ = 1)
     */
    public function level(): int
    {
        return $this->type === self::TYPE_EXECUTIVE ? 2 : 1;
    }

    /**
     * บุคลากรที่สังกัดกลุ่มงานนี้
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // protected static function newFactory(): DepartmentFactory
    // {
    //     // return DepartmentFactory::new();
    // }
}

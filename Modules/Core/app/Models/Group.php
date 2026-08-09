<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Core\Database\Factories\GroupFactory;

class Group extends Model
{
    use HasFactory;

    /** สิทธิการใช้ของกลุ่ม */
    public const TYPE_EXECUTIVE = 'executive';
    public const TYPE_OFFICE = 'office';

    public const TYPES = [
        self::TYPE_EXECUTIVE => 'กลุ่มผู้บริหาร',
        self::TYPE_OFFICE => 'กลุ่มงานสำนักงาน',
    ];

    protected $fillable = [
        'unit_id',
        'name',
        'level',
        'type',
        'code',
        'description',
        'head_user_id',
        'is_active',
    ];

    /** หน่วยงานที่สังกัด (เขต/โรงเรียน) */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * หัวหน้ากลุ่ม
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }
}

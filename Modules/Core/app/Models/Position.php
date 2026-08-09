<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Core\Database\Factories\PositionFactory;

class Position extends Model
{
    use HasFactory;

    /** ประเภทตำแหน่ง */
    public const TYPE_EXECUTIVE = 'executive'; // บริหาร
    public const TYPE_ACADEMIC = 'academic';   // วิชาการ
    public const TYPE_STAFF = 'staff';         // พนักงาน, ลูกจ้าง

    /** ป้ายชื่อประเภท (ไทย) */
    public const TYPES = [
        self::TYPE_EXECUTIVE => 'บริหาร',
        self::TYPE_ACADEMIC => 'วิชาการ',
        self::TYPE_STAFF => 'พนักงาน, ลูกจ้าง',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'code',
        'level',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** ป้ายชื่อประเภทแบบไทย */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? '—';
    }

    /**
     * บุคลากรที่ดำรงตำแหน่งนี้
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // protected static function newFactory(): PositionFactory
    // {
    //     // return PositionFactory::new();
    // }
}

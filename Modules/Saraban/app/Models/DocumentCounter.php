<?php

namespace Modules\Saraban\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ตัวนับเลขทะเบียนกลาง (ต่อเล่มทะเบียน + ปี)
 * จัดการการเพิ่มค่าผ่าน NumberRegisterService เท่านั้น
 */
class DocumentCounter extends Model
{
    protected $fillable = [
        'book',
        'year',
        'last_no',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_no' => 'integer',
        ];
    }
}

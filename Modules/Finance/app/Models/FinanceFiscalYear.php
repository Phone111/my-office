<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceFiscalYear extends Model
{
    protected $fillable = ['year', 'is_current'];

    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    /** ปีงบประมาณปัจจุบัน (พ.ศ.) — ถ้าไม่ตั้งไว้ ใช้ปีงบจากวันที่ระบบ */
    public static function current(): int
    {
        $row = static::where('is_current', true)->first();
        if ($row) {
            return (int) $row->year;
        }

        $t = \Illuminate\Support\Carbon::today();

        return ($t->month >= 10 ? $t->year + 1 : $t->year) + 543;
    }
}

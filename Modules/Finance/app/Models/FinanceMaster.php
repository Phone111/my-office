<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinanceMaster extends Model
{
    protected $fillable = [
        'type', 'fiscal_year', 'code', 'name', 'main_type', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** ชนิด master ที่รองรับ + ป้ายไทย */
    public const TYPES = [
        'plan' => 'แผนงาน',
        'project' => 'ผลผลิต/โครงการ',
        'activity' => 'กิจกรรมหลัก',
        'fund_source' => 'แหล่งของเงิน',
        'expense_category' => 'งบรายจ่าย',
        'money_type' => 'ประเภท(ย่อย)ของเงิน',
    ];

    /** ชนิดที่ผูกกับปีงบประมาณ (ต้องเลือกปี) */
    public const YEAR_BOUND = ['plan', 'project', 'activity', 'fund_source'];

    /** งบรายจ่ายหลัก 7 ประเภท (AMSS 1.7) */
    public const EXPENSE_MAIN = [
        '1' => 'งบบุคลากร',
        '2' => 'งบดำเนินงาน',
        '3' => 'งบลงทุน',
        '4' => 'งบเงินอุดหนุน',
        '5' => 'งบรายจ่ายอื่น',
        '6' => 'งบกลาง',
        '7' => 'งบอื่น ๆ',
    ];

    /** ประเภทหลักของเงิน 3 ประเภท (AMSS 1.8 — แก้ไขไม่ได้) */
    public const MONEY_MAIN = [
        '1' => 'เงินนอกงบประมาณ',
        '2' => 'เงินงบประมาณ',
        '3' => 'เงินรายได้แผ่นดิน',
    ];

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeForYear(Builder $q, ?int $year): Builder
    {
        return $q->where(function ($w) use ($year) {
            $w->whereNull('fiscal_year');
            if ($year) {
                $w->orWhere('fiscal_year', $year);
            }
        });
    }
}

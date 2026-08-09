<?php

namespace Modules\Finance\Support;

use Modules\Finance\Models\FinanceMaster;

trait FinanceHelpers
{
    private array $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    protected function thai($d): ?string
    {
        return $d ? $d->day.' '.$this->months[$d->month].' '.($d->year + 543) : null;
    }

    /** ตัวเลือก master ตามชนิด สำหรับปีงบประมาณ (รูปแบบ {id,label}) */
    protected function masterOptions(int $year, array $types): array
    {
        $out = [];
        foreach ($types as $type) {
            $q = FinanceMaster::ofType($type)->where('is_active', true);
            if (in_array($type, FinanceMaster::YEAR_BOUND, true)) {
                $q->forYear($year);
            }
            $out[$type] = $q->orderBy('sort_order')->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn ($m) => ['id' => $m->id, 'label' => trim(($m->code ? $m->code.' ' : '').$m->name)])
                ->values();
        }

        return $out;
    }
}

<?php

namespace Modules\Saraban\Services;

use Illuminate\Support\Facades\DB;
use Modules\Saraban\Models\DocumentCounter;
use Modules\Saraban\Models\SarabanSetting;

/**
 * บริการออกเลขทะเบียนรันอัตโนมัติ (เลขเอกสาร / เลขเกียรติบัตร)
 * เลขรันแยกตาม "เล่มทะเบียน" (book) และปี พ.ศ. — เริ่มนับ 1 ใหม่ทุกปี
 */
class NumberRegisterService
{
    /**
     * ออกเลขถัดไปแบบจัดรูปแล้ว เช่น "รับ 0007/2569"
     */
    public function issue(string $book, string $prefix = ''): string
    {
        $year = $this->buddhistYear();
        $no = $this->next($book, $year);

        return trim(sprintf('%s %04d/%d', $prefix, $no, $year));
    }

    /**
     * เพิ่มตัวนับของเล่มทะเบียนแบบล็อกแถว กันการชนกันเมื่อออกเลขพร้อมกัน
     */
    public function next(string $book, int $year): int
    {
        return DB::transaction(function () use ($book, $year) {
            $counter = DocumentCounter::where('book', $book)
                ->where('year', $year)
                ->lockForUpdate()
                ->first()
                ?? DocumentCounter::create(['book' => $book, 'year' => $year, 'last_no' => 0]);

            $counter->increment('last_no');

            return $counter->last_no;
        });
    }

    /**
     * เลขรันถัดไปแบบล็อกแถว (กันชนเมื่อออกเลขพร้อมกัน) — ครั้งแรกตั้งต้นจากเลขสูงสุดเดิม
     * ใช้กับทะเบียนที่เดินเลขแยกตาม "เล่ม" ใด ๆ (เช่น เลขรับ ต่อหน่วยงาน/ปี)
     *
     * @param  \Closure():int  $seedMax  คืนค่าเลขสูงสุดเดิม (กันชนข้อมูลที่มีอยู่ก่อนมีตัวนับ)
     */
    public function nextScoped(string $book, int $year, \Closure $seedMax): int
    {
        return DB::transaction(function () use ($book, $year, $seedMax) {
            $counter = DocumentCounter::where('book', $book)->where('year', $year)->lockForUpdate()->first();
            if (! $counter) {
                $counter = DocumentCounter::create(['book' => $book, 'year' => $year, 'last_no' => (int) $seedMax()]);
            }
            $counter->increment('last_no');

            return (int) $counter->last_no;
        });
    }

    /**
     * ปีสารบรรณที่ใช้เดินเลข (พ.ศ.) — ใช้ปีที่ตั้งไว้ ถ้าไม่ได้ตั้งใช้ปีตามระบบ
     */
    public function buddhistYear(): int
    {
        $active = SarabanSetting::get('active_year');

        return $active ? (int) $active : ((int) now()->year + 543);
    }
}

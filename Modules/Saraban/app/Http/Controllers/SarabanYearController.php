<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentCounter;
use Modules\Saraban\Models\SarabanSetting;

/**
 * จัดการปีสารบรรณ — ตั้งปี พ.ศ. ที่ใช้เดินเลขทะเบียน + เปิดปีใหม่ + ดูเลขล่าสุดแต่ละเล่มแยกปี
 */
class SarabanYearController extends Controller
{
    private function books(): array
    {
        return array_merge(Document::CATEGORIES, [
            'certificate' => 'เลขเกียรติบัตร',
            'announcement' => 'ประกาศ',
        ]);
    }

    public function index(): Response
    {
        $systemYear = (int) now()->year + 543;
        $activeYear = (int) (SarabanSetting::get('active_year') ?: $systemYear);
        $isCustom = SarabanSetting::get('active_year') !== null;

        $books = $this->books();
        $byYear = DocumentCounter::orderByDesc('year')->orderBy('book')->get()
            ->groupBy('year')
            ->map(fn ($g, $year) => [
                'year' => (int) $year,
                'books' => $g->map(fn (DocumentCounter $c) => [
                    'label' => $books[$c->book] ?? $c->book,
                    'last_no' => $c->last_no,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Saraban::Admin/SarabanYear', [
            'activeYear' => $activeYear,
            'systemYear' => $systemYear,
            'isCustom' => $isCustom,
            'byYear' => $byYear,
        ]);
    }

    /** ตั้งปีสารบรรณที่ใช้เดินเลข */
    public function setYear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
        ]);

        SarabanSetting::put('active_year', $validated['year']);

        return back()->with('success', "ตั้งปีสารบรรณเป็น พ.ศ. {$validated['year']} เรียบร้อยแล้ว เลขทะเบียนใหม่จะเดินตามปีนี้");
    }

    /** ใช้ปีตามระบบ (ล้างค่าที่ตั้งเอง) */
    public function useSystemYear(): RedirectResponse
    {
        SarabanSetting::query()->where('key', 'active_year')->delete();

        return back()->with('success', 'กลับมาใช้ปีสารบรรณตามปีปัจจุบันของระบบแล้ว');
    }
}

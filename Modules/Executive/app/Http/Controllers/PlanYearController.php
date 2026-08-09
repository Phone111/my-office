<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\ProjectBudget;
use Modules\Saraban\Models\SarabanSetting;

/**
 * จัดการปีจัดทำแผน — ตั้งปีงบประมาณที่ใช้วางแผน/ทำโครงการ (ค่าเริ่มต้นของฟอร์มเพิ่มโครงการ)
 */
class PlanYearController extends Controller
{
    /** ปีงบประมาณ พ.ศ. ตามระบบ (1 ต.ค.–30 ก.ย.) */
    private function systemYear(): int
    {
        $t = Carbon::today();

        return ($t->month >= 10 ? $t->year + 1 : $t->year) + 543;
    }

    public function index(): Response
    {
        $system = $this->systemYear();
        $set = SarabanSetting::get('plan_year');

        // สรุปจำนวนโครงการ/งบ แยกปี
        $byYear = ProjectBudget::selectRaw('fiscal_year, COUNT(*) as projects, SUM(allocated_amount) as allocated, SUM(disbursed_amount) as disbursed')
            ->groupBy('fiscal_year')
            ->orderByDesc('fiscal_year')
            ->get()
            ->map(fn ($r) => [
                'year' => $r->fiscal_year,
                'projects' => (int) $r->projects,
                'allocated' => (float) $r->allocated,
                'disbursed' => (float) $r->disbursed,
            ]);

        return Inertia::render('Executive::PlanYear', [
            'activeYear' => $set ? (int) $set : $system,
            'systemYear' => $system,
            'isCustom' => (bool) $set,
            'byYear' => $byYear,
        ]);
    }

    public function setYear(Request $request): RedirectResponse
    {
        $v = $request->validate(['year' => ['required', 'integer', 'min:2500', 'max:2700']]);
        SarabanSetting::put('plan_year', (string) $v['year']);

        return back()->with('success', 'ตั้งปีจัดทำแผนเป็น พ.ศ. '.$v['year'].' แล้ว');
    }

    public function useSystem(): RedirectResponse
    {
        SarabanSetting::query()->where('key', 'plan_year')->delete();

        return back()->with('success', 'ใช้ปีจัดทำแผนตามระบบแล้ว');
    }
}

<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceMaster;
use Modules\Finance\Models\FinanceOfficer;

/**
 * ตั้งค่าระบบการเงิน (AMSS การเงินฯ ส่วนที่ 1)
 * รวม: เจ้าหน้าที่การเงิน, ปีงบประมาณ, แผนงาน, ผลผลิต/โครงการ, กิจกรรมหลัก,
 *      แหล่งของเงิน, งบรายจ่าย, ประเภทของเงิน
 */
class FinanceSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $masters = FinanceMaster::query()
            ->forYear($year)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->groupBy('type')
            ->map(fn ($g) => $g->map(fn (FinanceMaster $m) => [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'main_type' => $m->main_type,
                'fiscal_year' => $m->fiscal_year,
                'is_active' => $m->is_active,
            ])->values());

        $officers = FinanceOfficer::with('user:id,name')->get()->map(function (FinanceOfficer $o) {
            $row = ['id' => $o->id, 'user_id' => $o->user_id, 'name' => $o->user?->name];
            foreach (array_keys(FinanceOfficer::RIGHTS) as $k) {
                $row[$k] = (bool) $o->$k;
            }

            return $row;
        });

        return Inertia::render('Finance::Settings', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'currentYear' => FinanceFiscalYear::current(),
            'masters' => $masters,
            'officers' => $officers,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'meta' => [
                'types' => FinanceMaster::TYPES,
                'yearBound' => FinanceMaster::YEAR_BOUND,
                'expenseMain' => FinanceMaster::EXPENSE_MAIN,
                'moneyMain' => FinanceMaster::MONEY_MAIN,
                'rights' => FinanceOfficer::RIGHTS,
            ],
        ]);
    }

    // ===== ปีงบประมาณ =====
    public function addYear(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'year' => ['required', 'integer', 'min:2500', 'max:2700', Rule::unique('finance_fiscal_years', 'year')],
            'is_current' => ['boolean'],
        ]);
        if ($request->boolean('is_current')) {
            FinanceFiscalYear::query()->update(['is_current' => false]);
        }
        FinanceFiscalYear::create(['year' => $v['year'], 'is_current' => $request->boolean('is_current')]);

        return back()->with('success', 'เพิ่มปีงบประมาณเรียบร้อยแล้ว');
    }

    public function setCurrentYear(FinanceFiscalYear $fiscalYear): RedirectResponse
    {
        FinanceFiscalYear::query()->update(['is_current' => false]);
        $fiscalYear->update(['is_current' => true]);

        return back()->with('success', 'กำหนดปีทำงานปัจจุบันเรียบร้อยแล้ว');
    }

    public function deleteYear(FinanceFiscalYear $fiscalYear): RedirectResponse
    {
        $fiscalYear->delete();

        return back()->with('success', 'ลบปีงบประมาณแล้ว');
    }

    // ===== เจ้าหน้าที่การเงิน =====
    public function saveOfficer(Request $request): RedirectResponse
    {
        $rights = array_keys(FinanceOfficer::RIGHTS);
        $v = $request->validate(array_merge(
            ['user_id' => ['required', 'exists:users,id']],
            array_fill_keys($rights, ['boolean']),
        ));

        $data = ['user_id' => $v['user_id']];
        foreach ($rights as $k) {
            $data[$k] = $request->boolean($k);
        }
        FinanceOfficer::updateOrCreate(['user_id' => $v['user_id']], $data);

        return back()->with('success', 'บันทึกสิทธิ์เจ้าหน้าที่การเงินเรียบร้อยแล้ว');
    }

    public function deleteOfficer(FinanceOfficer $officer): RedirectResponse
    {
        $officer->delete();

        return back()->with('success', 'ลบเจ้าหน้าที่การเงินแล้ว');
    }

    // ===== master data (แผนงาน/ผลผลิต/กิจกรรม/แหล่งเงิน/งบรายจ่าย/ประเภทเงิน) =====
    public function storeMaster(Request $request): RedirectResponse
    {
        $data = $this->validateMaster($request);
        FinanceMaster::create($data);

        return back()->with('success', 'เพิ่มข้อมูลเรียบร้อยแล้ว');
    }

    public function updateMaster(Request $request, FinanceMaster $master): RedirectResponse
    {
        $data = $this->validateMaster($request, $master->type);
        $master->update($data);

        return back()->with('success', 'แก้ไขข้อมูลเรียบร้อยแล้ว');
    }

    public function destroyMaster(FinanceMaster $master): RedirectResponse
    {
        $master->delete();

        return back()->with('success', 'ลบข้อมูลแล้ว');
    }

    private function validateMaster(Request $request, ?string $fixedType = null): array
    {
        $type = $fixedType ?: $request->input('type');
        $request->merge(['type' => $type]);

        $v = $request->validate([
            'type' => ['required', Rule::in(array_keys(FinanceMaster::TYPES))],
            'code' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'main_type' => ['nullable', 'string', 'max:30'],
            'fiscal_year' => ['nullable', 'integer', 'min:2500', 'max:2700'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // ชนิดที่ผูกปี ต้องมีปีงบประมาณ
        if (in_array($type, FinanceMaster::YEAR_BOUND, true) && empty($v['fiscal_year'])) {
            $v['fiscal_year'] = FinanceFiscalYear::current();
        }
        // ชนิดไม่ผูกปี (งบรายจ่าย/ประเภทเงิน) ใช้ได้ทุกปี
        if (! in_array($type, FinanceMaster::YEAR_BOUND, true)) {
            $v['fiscal_year'] = null;
        }
        $v['is_active'] = $request->boolean('is_active', true);
        $v['sort_order'] = $v['sort_order'] ?? 0;

        return $v;
    }
}

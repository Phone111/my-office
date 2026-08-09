<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceMaster;
use Modules\Finance\Models\FinanceStatusChange;
use Modules\Finance\Support\FinanceHelpers;

/**
 * เปลี่ยนแปลงสถานะเงิน (AMSS การเงินฯ ส่วนที่ 5)
 */
class StatusChangeController extends Controller
{
    use FinanceHelpers;

    private function assertClass(string $class): void
    {
        abort_unless(array_key_exists($class, FinanceStatusChange::CLASSES), 404);
    }

    public function index(Request $request, string $class): Response
    {
        $this->assertClass($class);
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinanceStatusChange::with('moneyType')
            ->where('money_class', $class)->where('fiscal_year', $year)
            ->latest('id')->get()
            ->map(fn (FinanceStatusChange $s) => [
                'id' => $s->id,
                'doc_no' => $s->doc_no,
                'title' => $s->title,
                'money_type_id' => $s->money_type_id,
                'money_type' => $s->moneyType?->name,
                'nature' => $s->nature,
                'nature_label' => $s->nature ? (FinanceStatusChange::NATURES[$s->nature] ?? $s->nature) : null,
                'amount' => (float) $s->amount,
                'change_date' => $s->change_date?->format('Y-m-d'),
                'change_date_thai' => $this->thai($s->change_date),
            ]);

        $moneyTypes = [];
        if (isset(FinanceStatusChange::CLASS_MAIN[$class])) {
            $moneyTypes = FinanceMaster::ofType('money_type')->where('main_type', FinanceStatusChange::CLASS_MAIN[$class])
                ->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])
                ->map(fn ($m) => ['id' => $m->id, 'label' => trim(($m->code ? $m->code.' ' : '').$m->name)]);
        }

        return Inertia::render('Finance::StatusChanges', [
            'class' => $class,
            'classLabel' => FinanceStatusChange::CLASSES[$class],
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'natures' => FinanceStatusChange::NATURES,
            'usesMoneyType' => isset(FinanceStatusChange::CLASS_MAIN[$class]),
            'moneyTypes' => $moneyTypes,
        ]);
    }

    public function store(Request $request, string $class): RedirectResponse
    {
        $this->assertClass($class);
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $rules = [
            'doc_no' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'nature' => ['nullable', Rule::in(array_keys(FinanceStatusChange::NATURES))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'change_date' => ['nullable', 'date'],
        ];
        $rules['money_type_id'] = isset(FinanceStatusChange::CLASS_MAIN[$class])
            ? ['required', 'exists:finance_masters,id'] : ['nullable'];
        $v = $request->validate($rules);
        $v['money_class'] = $class;
        $v['fiscal_year'] = $year;
        $v['change_date'] = $v['change_date'] ?? now()->toDateString();
        $v['created_by'] = $request->user()->id;
        FinanceStatusChange::create($v);

        return back()->with('success', 'บันทึกเปลี่ยนสถานะเงินเรียบร้อยแล้ว');
    }

    public function destroy(FinanceStatusChange $statusChange): RedirectResponse
    {
        $statusChange->delete();

        return back()->with('success', 'ลบรายการเปลี่ยนสถานะเงินแล้ว');
    }
}

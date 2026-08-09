<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceAllocation;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceMaster;
use Modules\Finance\Models\FinancePayment;
use Modules\Finance\Models\FinancePetition;
use Modules\Finance\Models\FinanceReceipt;
use Modules\Finance\Models\FinanceWithdrawal;
use Modules\Finance\Support\FinanceHelpers;

/**
 * รายงาน (AMSS การเงินฯ ส่วนที่ 7) — 11 รายงาน
 */
class ReportController extends Controller
{
    use FinanceHelpers;

    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $allocs = FinanceAllocation::with(['plan', 'project', 'expenseCategory'])->where('fiscal_year', $year)->orderBy('voucher_no')->get();
        $receipts = FinanceReceipt::with('moneyType')->where('fiscal_year', $year)->get();
        $withdrawals = FinanceWithdrawal::with('project')->where('fiscal_year', $year)->get();
        $petitions = FinancePetition::with(['project', 'expenseCategory'])->where('fiscal_year', $year)->get();
        $payments = FinancePayment::with(['moneyType', 'expenseCategory'])->where('fiscal_year', $year)->get();
        $paid = $payments->where('paid', true);

        // 7.1 / 7.3 รายงานการจัดสรร / ทะเบียนเงินงวด
        $allocationReport = $allocs->map(fn ($a) => [
            'voucher_no' => $a->voucher_no,
            'doc_no' => $a->doc_no,
            'date' => $this->thai($a->doc_date),
            'plan' => $a->plan?->name,
            'project' => $a->project?->name,
            'expense' => $a->expenseCategory?->name,
            'title' => $a->title,
            'amount' => (float) $a->amount,
        ])->values();

        // 7.2 ใช้จ่ายจำแนกตามโครงการ (รวมทั้งฝั่งฎีกาและฝั่งก่อหนี้)
        $petByProj = $petitions->groupBy(fn ($p) => $p->project?->name ?? '(ไม่ระบุโครงการ)')
            ->map(fn ($g) => ['petitioned' => (float) $g->sum('amount'), 'net' => (float) $g->sum('net')]);
        $comByProj = $withdrawals->groupBy(fn ($w) => $w->project?->name ?? '(ไม่ระบุโครงการ)')
            ->map(fn ($g) => (float) $g->sum('amount'));
        $projectNames = $petByProj->keys()->merge($comByProj->keys())->unique();
        $projectReport = $projectNames->map(fn ($name) => [
            'name' => $name,
            'committed' => (float) ($comByProj[$name] ?? 0),
            'petitioned' => (float) ($petByProj[$name]['petitioned'] ?? 0),
            'net' => (float) ($petByProj[$name]['net'] ?? 0),
        ])->values();

        // 7.4 / 7.5 ใช้จ่ายจำแนกตามงบรายจ่าย/ประเภทรายการจ่าย
        $totalPaid = (float) $paid->sum('amount');
        $byExpense = $paid->groupBy('expense_category_id')->map(function ($g) use ($totalPaid) {
            $first = $g->first();
            $sum = (float) $g->sum('amount');

            return [
                'name' => $first->expenseCategory?->name ?? '(ไม่ระบุงบรายจ่าย)',
                'amount' => $sum,
                'percent' => $totalPaid > 0 ? round($sum / $totalPaid * 100, 2) : 0,
            ];
        })->values();

        // 7.6 เงินคงเหลือประจำวัน (ต่อประเภทเงิน)
        $balanceByClass = collect(['budget' => 'เงินงบประมาณ', 'nonbudget' => 'เงินนอกงบประมาณ', 'state_revenue' => 'เงินรายได้แผ่นดิน'])
            ->map(function ($label, $cls) use ($receipts, $paid) {
                $in = (float) $receipts->where('money_class', $cls)->sum('amount');
                $out = (float) $paid->where('money_class', $cls)->sum('amount');

                return ['class' => $label, 'received' => $in, 'paid' => $out, 'balance' => $in - $out];
            })->values();

        // 7.7 สมุดเงินสด (เงินงบประมาณ) — ไทม์ไลน์ รับ/จ่าย + ยอดสะสม
        $cashRows = collect();
        foreach ($receipts->where('money_class', 'budget') as $r) {
            $cashRows->push(['date' => $r->receive_date, 'title' => $r->title, 'in' => (float) $r->amount, 'out' => 0.0]);
        }
        foreach ($paid->where('money_class', 'budget') as $p) {
            $cashRows->push(['date' => $p->paid_at ?? $p->order_date, 'title' => $p->title, 'in' => 0.0, 'out' => (float) $p->amount]);
        }
        $running = 0;
        $cashBook = $cashRows->sortBy('date')->values()->map(function ($row) use (&$running) {
            $running += $row['in'] - $row['out'];

            return [
                'date' => $this->thai($row['date'] instanceof \Carbon\Carbon ? $row['date'] : \Illuminate\Support\Carbon::parse($row['date'])),
                'title' => $row['title'],
                'in' => $row['in'],
                'out' => $row['out'],
                'balance' => round($running, 2),
            ];
        });

        // 7.8 รายงานเงินงบประมาณ
        $budgetIn = (float) $receipts->where('money_class', 'budget')->sum('amount');
        $budgetOut = (float) $paid->where('money_class', 'budget')->sum('amount');

        // 7.9 / 7.10 เงินนอกงบ / รายได้แผ่นดิน (ต่อประเภทเงิน)
        $byMoneyType = function (string $cls) use ($receipts, $paid) {
            $rIn = $receipts->where('money_class', $cls)->groupBy('money_type_id');
            $pOut = $paid->where('money_class', $cls)->groupBy('money_type_id');
            $ids = $rIn->keys()->merge($pOut->keys())->unique();

            return $ids->map(function ($id) use ($rIn, $pOut) {
                $name = optional(FinanceMaster::find($id))->name ?? '(ไม่ระบุประเภท)';
                $in = (float) ($rIn[$id] ?? collect())->sum('amount');
                $out = (float) ($pOut[$id] ?? collect())->sum('amount');

                return ['name' => $name, 'received' => $in, 'paid' => $out, 'balance' => $in - $out];
            })->values();
        };

        // 7.11 ลูกหนี้เงินยืม (ยังไม่ส่งใช้)
        $loans = $withdrawals->filter(fn ($w) => $w->isBorrow() && ! $w->settled_at)->map(fn ($w) => [
            'kind' => FinanceWithdrawal::KINDS[$w->kind] ?? $w->kind,
            'borrower' => $w->borrower,
            'title' => $w->title,
            'amount' => (float) $w->amount,
        ])->values();

        return Inertia::render('Finance::Reports', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'allocationReport' => $allocationReport,
            'allocationTotal' => (float) $allocs->sum('amount'),
            'projectReport' => $projectReport,
            'byExpense' => $byExpense,
            'totalPaid' => $totalPaid,
            'balanceByClass' => $balanceByClass,
            'cashBook' => $cashBook,
            'budget' => ['received' => $budgetIn, 'paid' => $budgetOut, 'balance' => $budgetIn - $budgetOut],
            'nonbudget' => $byMoneyType('nonbudget'),
            'stateRevenue' => $byMoneyType('state_revenue'),
            'loans' => $loans,
            'loanTotal' => (float) $loans->sum('amount'),
        ]);
    }
}

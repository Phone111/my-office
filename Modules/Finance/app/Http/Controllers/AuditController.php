<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceAllocation;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinancePayment;
use Modules\Finance\Models\FinancePetition;
use Modules\Finance\Models\FinanceTreasuryReturn;
use Modules\Finance\Models\FinanceWithdrawal;

/**
 * ตรวจสอบ (AMSS การเงินฯ ส่วนที่ 6) — 9 รายการ
 */
class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $allocs = FinanceAllocation::where('fiscal_year', $year)->orderBy('voucher_no')->get();
        $petitions = FinancePetition::where('fiscal_year', $year)->get();
        $payments = FinancePayment::where('fiscal_year', $year)->get();
        $withdrawals = FinanceWithdrawal::with(['expenseCategory', 'petition'])->where('fiscal_year', $year)->get();
        $treasuryReturns = FinanceTreasuryReturn::where('fiscal_year', $year)->get();

        $petByAlloc = $petitions->groupBy('allocation_id');
        $retByAlloc = $treasuryReturns->groupBy('allocation_id');

        // 6.1 + 6.2 ตรวจสอบการจัดสรร/เงินประจำงวด (ต่อใบงวด)
        $allocationCheck = $allocs->map(function (FinanceAllocation $a) use ($petByAlloc, $retByAlloc) {
            $petitioned = (float) ($petByAlloc[$a->id] ?? collect())->sum('amount');
            $returned = (float) ($retByAlloc[$a->id] ?? collect())->sum('amount');
            $allocated = (float) $a->amount;
            $remaining = $allocated - $petitioned + $returned;

            return [
                'voucher_no' => $a->voucher_no,
                'title' => $a->title,
                'allocated' => $allocated,
                'petitioned' => $petitioned,
                'returned' => $returned,
                'remaining' => $remaining,
                'percent' => $allocated > 0 ? round($petitioned / $allocated * 100, 2) : 0,
                'complete' => abs($remaining) < 0.01,
            ];
        })->values();

        // 6.3 ตรวจสอบจ่ายเงินประเภทหลัก
        $payMain = $payments->whereIn('money_class', ['budget', 'nonbudget', 'state_revenue'])->map(fn ($p) => [
            'class' => FinancePayment::CLASSES[$p->money_class] ?? $p->money_class,
            'title' => $p->title,
            'amount' => (float) $p->amount,
            'approval' => FinancePayment::STATUS[$p->approval_status] ?? $p->approval_status,
            'approval_status' => $p->approval_status,
            'paid' => $p->paid,
        ])->values();

        // 6.4 ตรวจสอบจ่ายเงินทดรองราชการ
        $payAdvance = $payments->where('money_class', 'advance')->map(fn ($p) => [
            'title' => $p->title,
            'amount' => (float) $p->amount,
            'approval' => FinancePayment::STATUS[$p->approval_status] ?? $p->approval_status,
            'approval_status' => $p->approval_status,
            'paid' => $p->paid,
            'is_return' => $p->is_advance_return,
        ])->values();

        // 6.5 เลขที่ฎีกาที่ไม่มีในระบบ (ช่องว่างของเลขฎีกาแบบตัวเลข)
        $nums = $petitions->pluck('petition_no')->filter(fn ($n) => is_numeric($n))->map(fn ($n) => (int) $n)->sort()->values();
        $missing = [];
        if ($nums->count()) {
            for ($i = 1; $i <= $nums->max(); $i++) {
                if (! $nums->contains($i)) {
                    $missing[] = $i;
                }
            }
        }

        // 6.6 ฎีกากับการตัดโครงการจำแนกตามใบงวด
        $petitionByVoucher = $petByAlloc->map(function ($group, $allocId) use ($allocs) {
            $a = $allocs->firstWhere('id', $allocId);

            return [
                'voucher_no' => $a?->voucher_no ?? '—',
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
                'net' => (float) $group->sum('net'),
            ];
        })->values();

        // 6.7 ฎีกากับการอ้างอิงการขอเบิกจำแนกตามฎีกา (เทียบเงินวางเบิก vs ก่อหนี้)
        $linkedByPetition = $withdrawals->whereNotNull('petition_id')->groupBy('petition_id');
        $petitionVsWithdrawal = $petitions->map(function (FinancePetition $p) use ($linkedByPetition) {
            $committed = (float) ($linkedByPetition[$p->id] ?? collect())->sum('amount');

            return [
                'petition_no' => $p->petition_no,
                'title' => $p->title,
                'petition_amount' => (float) $p->amount,
                'committed' => $committed,
                'diff' => round((float) $p->amount - $committed, 2),
            ];
        })->values();

        // 6.8 รายการขอเบิกที่ยังไม่ได้วางฎีกา
        $noPetition = $withdrawals->whereNull('petition_id')->map(fn ($w) => [
            'doc_no' => $w->doc_no,
            'title' => $w->title,
            'kind' => FinanceWithdrawal::KINDS[$w->kind] ?? $w->kind,
            'amount' => (float) $w->amount,
        ])->values();

        // 6.9 รายการขอเบิกที่วางฎีกาผิด (งบรายจ่ายไม่ตรงกับฎีกา)
        $wrongVoucher = $withdrawals->whereNotNull('petition_id')->filter(function (FinanceWithdrawal $w) {
            return $w->petition && $w->petition->expense_category_id
                && $w->expense_category_id
                && $w->expense_category_id !== $w->petition->expense_category_id;
        })->map(fn ($w) => [
            'title' => $w->title,
            'petition_no' => $w->petition?->petition_no,
            'withdrawal_expense' => $w->expenseCategory?->name,
            'amount' => (float) $w->amount,
        ])->values();

        return Inertia::render('Finance::Audit', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'allocationCheck' => $allocationCheck,
            'payMain' => $payMain,
            'payAdvance' => $payAdvance,
            'missing' => $missing,
            'petitionByVoucher' => $petitionByVoucher,
            'petitionVsWithdrawal' => $petitionVsWithdrawal,
            'noPetition' => $noPetition,
            'wrongVoucher' => $wrongVoucher,
        ]);
    }
}

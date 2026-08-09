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
use Modules\Finance\Models\FinancePayment;
use Modules\Finance\Models\FinancePetition;
use Modules\Finance\Models\FinanceWithdrawal;
use Modules\Finance\Support\FinanceHelpers;

/**
 * ทะเบียนจ่าย (AMSS การเงินฯ ส่วนที่ 4)
 *   สั่งจ่าย (index/store) → อนุมัติจ่าย (approvals/approve) → จ่ายเงิน (payouts/pay)
 */
class PaymentController extends Controller
{
    use FinanceHelpers;

    private function assertClass(string $class): void
    {
        abort_unless(array_key_exists($class, FinancePayment::CLASSES), 404);
    }

    private function rowMap(FinancePayment $p): array
    {
        return [
            'id' => $p->id,
            'money_class' => $p->money_class,
            'class_label' => FinancePayment::CLASSES[$p->money_class] ?? $p->money_class,
            'doc_no' => $p->doc_no,
            'petition_id' => $p->petition_id,
            'petition_no' => $p->petition?->petition_no,
            'withdrawal_id' => $p->withdrawal_id,
            'money_type_id' => $p->money_type_id,
            'money_type' => $p->moneyType?->name,
            'expense_category_id' => $p->expense_category_id,
            'expense_category' => $p->expenseCategory?->name,
            'title' => $p->title,
            'amount' => (float) $p->amount,
            'payee' => $p->payee,
            'is_advance_return' => $p->is_advance_return,
            'approval_status' => $p->approval_status,
            'approval_label' => FinancePayment::STATUS[$p->approval_status] ?? $p->approval_status,
            'approve_note' => $p->approve_note,
            'approver' => $p->approver?->name,
            'paid' => $p->paid,
            'paid_at' => $this->thai($p->paid_at),
            'order_date' => $p->order_date?->format('Y-m-d'),
            'order_date_thai' => $this->thai($p->order_date),
        ];
    }

    // ===== สั่งจ่าย (4.1–4.4) =====
    public function index(Request $request, string $class): Response
    {
        $this->assertClass($class);
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinancePayment::with(['petition', 'moneyType', 'expenseCategory', 'approver'])
            ->where('money_class', $class)->where('fiscal_year', $year)
            ->latest('id')->get()->map(fn ($p) => $this->rowMap($p));

        $moneyTypes = [];
        if (isset(FinancePayment::CLASS_MAIN[$class])) {
            $moneyTypes = FinanceMaster::ofType('money_type')->where('main_type', FinancePayment::CLASS_MAIN[$class])
                ->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])
                ->map(fn ($m) => ['id' => $m->id, 'label' => trim(($m->code ? $m->code.' ' : '').$m->name)]);
        }

        return Inertia::render('Finance::Payments', [
            'class' => $class,
            'classLabel' => FinancePayment::CLASSES[$class],
            'isAdvance' => $class === 'advance',
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'usesMoneyType' => isset(FinancePayment::CLASS_MAIN[$class]),
            'moneyTypes' => $moneyTypes,
            'expenseCategories' => $this->masterOptions($year, ['expense_category'])['expense_category'],
            'petitions' => FinancePetition::where('fiscal_year', $year)->where('cancelled', false)
                ->orderBy('id')->get(['id', 'petition_no', 'title'])
                ->map(fn ($p) => ['id' => $p->id, 'label' => 'ฎีกา '.($p->petition_no ?: $p->id).' — '.$p->title]),
            'withdrawals' => FinanceWithdrawal::where('fiscal_year', $year)->orderBy('id')
                ->get(['id', 'title', 'doc_no'])
                ->map(fn ($w) => ['id' => $w->id, 'label' => ($w->doc_no ? '['.$w->doc_no.'] ' : '').$w->title]),
        ]);
    }

    public function store(Request $request, string $class): RedirectResponse
    {
        $this->assertClass($class);
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request, $class);
        $v['money_class'] = $class;
        $v['fiscal_year'] = $year;
        $v['approval_status'] = 'pending';
        $v['is_advance_return'] = $class === 'advance' ? $request->boolean('is_advance_return') : false;
        $v['order_date'] = $v['order_date'] ?? now()->toDateString();
        $v['created_by'] = $request->user()->id;
        FinancePayment::create($v);

        return back()->with('success', 'บันทึกสั่งจ่ายเรียบร้อยแล้ว (รอการอนุมัติจ่าย)');
    }

    public function update(Request $request, FinancePayment $payment): RedirectResponse
    {
        $payment->update($this->validateData($request, $payment->money_class));

        return back()->with('success', 'แก้ไขรายการสั่งจ่ายเรียบร้อยแล้ว');
    }

    public function destroy(FinancePayment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('success', 'ลบรายการสั่งจ่ายแล้ว');
    }

    // ===== อนุมัติจ่าย (4.5–4.6) =====
    public function approvals(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());
        $rows = FinancePayment::with(['petition', 'expenseCategory', 'approver'])
            ->where('fiscal_year', $year)->latest('id')->get()->map(fn ($p) => $this->rowMap($p));

        return Inertia::render('Finance::PaymentApprovals', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'pendingTotal' => (float) $rows->where('approval_status', 'pending')->sum('amount'),
        ]);
    }

    public function approve(Request $request, FinancePayment $payment): RedirectResponse
    {
        $v = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'approve_note' => ['nullable', 'string', 'max:255'],
        ]);
        $payment->update([
            'approval_status' => $v['decision'],
            'approve_note' => $v['approve_note'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            // หากเปลี่ยนเป็นไม่อนุมัติ ให้ยกเลิกสถานะจ่ายด้วย
            'paid' => $v['decision'] === 'approved' ? $payment->paid : false,
        ]);

        return back()->with('success', $v['decision'] === 'approved' ? 'อนุมัติสั่งจ่ายเรียบร้อยแล้ว' : 'บันทึกไม่อนุมัติแล้ว');
    }

    // ===== จ่ายเงิน (4.7–4.8) =====
    public function payouts(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());
        $rows = FinancePayment::with(['petition', 'expenseCategory', 'payer'])
            ->where('fiscal_year', $year)->where('approval_status', 'approved')
            ->latest('id')->get()->map(fn ($p) => $this->rowMap($p));

        return Inertia::render('Finance::PaymentPayouts', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'unpaidTotal' => (float) $rows->where('paid', false)->sum('amount'),
            'paidTotal' => (float) $rows->where('paid', true)->sum('amount'),
        ]);
    }

    public function pay(Request $request, FinancePayment $payment): RedirectResponse
    {
        abort_unless($payment->approval_status === 'approved', 422, 'ต้องผ่านการอนุมัติจ่ายก่อน');
        $payment->update([
            'paid' => ! $payment->paid,
            'paid_at' => $payment->paid ? null : now(),
            'paid_by' => $payment->paid ? null : $request->user()->id,
        ]);

        return back()->with('success', $payment->paid ? 'บันทึกจ่ายเงินเรียบร้อยแล้ว' : 'ยกเลิกสถานะจ่ายเงิน');
    }

    private function validateData(Request $request, string $class): array
    {
        $rules = [
            'doc_no' => ['nullable', 'string', 'max:255'],
            'withdrawal_id' => ['nullable', 'exists:finance_withdrawals,id'],
            'petition_id' => ['nullable', 'exists:finance_petitions,id'],
            'expense_category_id' => ['nullable', 'exists:finance_masters,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'payee' => ['nullable', 'string', 'max:255'],
            'order_date' => ['nullable', 'date'],
            'is_advance_return' => ['boolean'],
        ];
        $rules['money_type_id'] = isset(FinancePayment::CLASS_MAIN[$class])
            ? ['required', 'exists:finance_masters,id'] : ['nullable'];

        return $request->validate($rules);
    }
}

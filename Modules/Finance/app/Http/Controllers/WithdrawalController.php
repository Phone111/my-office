<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceWithdrawal;
use Modules\Finance\Support\FinanceHelpers;

/**
 * ทะเบียนขอเบิก/ขอยืมโครงการ (AMSS 3.1) — ก่อหนี้ผูกพัน/ตัดยอดโครงการ
 */
class WithdrawalController extends Controller
{
    use FinanceHelpers;

    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinanceWithdrawal::with(['project', 'expenseCategory', 'petition'])
            ->where('fiscal_year', $year)
            ->latest('id')->get()
            ->map(fn (FinanceWithdrawal $w) => [
                'id' => $w->id,
                'doc_no' => $w->doc_no,
                'kind' => $w->kind,
                'kind_label' => FinanceWithdrawal::KINDS[$w->kind] ?? $w->kind,
                'is_borrow' => $w->isBorrow(),
                'title' => $w->title,
                'project_id' => $w->project_id,
                'activity_id' => $w->activity_id,
                'expense_category_id' => $w->expense_category_id,
                'project' => $w->project?->name,
                'expense_category' => $w->expenseCategory?->name,
                'amount' => (float) $w->amount,
                'borrower' => $w->borrower,
                'petition_id' => $w->petition_id,
                'petition_no' => $w->petition?->petition_no,
                'settled' => (bool) $w->settled_at,
            ]);

        return Inertia::render('Finance::Withdrawals', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'kinds' => FinanceWithdrawal::KINDS,
            'options' => $this->masterOptions($year, ['project', 'activity', 'expense_category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request);
        $v['fiscal_year'] = $year;
        $v['created_by'] = $request->user()->id;
        FinanceWithdrawal::create($v);

        return back()->with('success', 'บันทึกขอเบิก/ขอยืมเรียบร้อยแล้ว (ก่อหนี้ผูกพัน)');
    }

    public function update(Request $request, FinanceWithdrawal $withdrawal): RedirectResponse
    {
        $withdrawal->update($this->validateData($request));

        return back()->with('success', 'แก้ไขรายการขอเบิก/ขอยืมเรียบร้อยแล้ว');
    }

    public function destroy(FinanceWithdrawal $withdrawal): RedirectResponse
    {
        $withdrawal->delete();

        return back()->with('success', 'ลบรายการขอเบิก/ขอยืมแล้ว');
    }

    /** ส่งใช้เงินยืม — เปลี่ยนสถานะลูกหนี้เงินยืมเป็นชำระแล้ว */
    public function settle(FinanceWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->isBorrow(), 422);
        $withdrawal->update(['settled_at' => $withdrawal->settled_at ? null : now()->toDateString()]);

        return back()->with('success', $withdrawal->settled_at ? 'บันทึกส่งใช้เงินยืมแล้ว' : 'ยกเลิกสถานะส่งใช้เงินยืม');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'doc_no' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(FinanceWithdrawal::KINDS))],
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:finance_masters,id'],
            'activity_id' => ['nullable', 'exists:finance_masters,id'],
            'expense_category_id' => ['nullable', 'exists:finance_masters,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'borrower' => ['nullable', 'string', 'max:255'],
            'doc_date' => ['nullable', 'date'],
        ]);
    }
}

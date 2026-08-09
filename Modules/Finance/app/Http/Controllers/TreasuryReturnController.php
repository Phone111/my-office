<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceAllocation;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceTreasuryReturn;
use Modules\Finance\Support\FinanceHelpers;

/**
 * ทะเบียนคืนเงินคงคลัง (AMSS 3.4)
 * คืนยอดเข้าใบงวด และ (ถ้าเลือก) บันทึกในทะเบียนสั่งจ่ายฯ เพื่อรอส่งคืนคลัง
 */
class TreasuryReturnController extends Controller
{
    use FinanceHelpers;

    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinanceTreasuryReturn::with(['allocation', 'project'])
            ->where('fiscal_year', $year)->latest('id')->get()
            ->map(fn (FinanceTreasuryReturn $r) => [
                'id' => $r->id,
                'doc_no' => $r->doc_no,
                'allocation_id' => $r->allocation_id,
                'voucher_no' => $r->allocation?->voucher_no,
                'plan_id' => $r->plan_id,
                'project_id' => $r->project_id,
                'activity_id' => $r->activity_id,
                'expense_category_id' => $r->expense_category_id,
                'project' => $r->project?->name,
                'title' => $r->title,
                'amount' => (float) $r->amount,
                'to_payment' => $r->to_payment,
                'return_date' => $r->return_date?->format('Y-m-d'),
                'return_date_thai' => $this->thai($r->return_date),
            ]);

        return Inertia::render('Finance::TreasuryReturns', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'options' => $this->masterOptions($year, ['plan', 'project', 'activity', 'expense_category']),
            'allocations' => FinanceAllocation::where('fiscal_year', $year)->orderBy('voucher_no')
                ->get(['id', 'voucher_no', 'title'])
                ->map(fn ($a) => ['id' => $a->id, 'label' => 'ใบงวด '.$a->voucher_no.' — '.$a->title]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request);
        $v['fiscal_year'] = $year;
        $v['to_payment'] = $request->boolean('to_payment', true);
        $v['return_date'] = $v['return_date'] ?? now()->toDateString();
        $v['created_by'] = $request->user()->id;
        FinanceTreasuryReturn::create($v);

        return back()->with('success', 'บันทึกคืนเงินคงคลังเรียบร้อยแล้ว');
    }

    public function destroy(FinanceTreasuryReturn $treasuryReturn): RedirectResponse
    {
        $treasuryReturn->delete();

        return back()->with('success', 'ลบรายการคืนเงินคงคลังแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'doc_no' => ['nullable', 'string', 'max:255'],
            'allocation_id' => ['nullable', 'exists:finance_allocations,id'],
            'plan_id' => ['nullable', 'exists:finance_masters,id'],
            'project_id' => ['nullable', 'exists:finance_masters,id'],
            'activity_id' => ['nullable', 'exists:finance_masters,id'],
            'expense_category_id' => ['nullable', 'exists:finance_masters,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'to_payment' => ['boolean'],
            'return_date' => ['nullable', 'date'],
        ]);
    }
}

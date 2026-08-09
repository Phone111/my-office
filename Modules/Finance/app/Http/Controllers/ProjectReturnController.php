<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceProjectReturn;
use Modules\Finance\Models\FinanceReceipt;
use Modules\Finance\Support\FinanceHelpers;

/**
 * ทะเบียนคืนเงินโครงการ (AMSS 3.2)
 * คืนยอดเข้าโครงการ + เข้าทะเบียนรับเงินงบประมาณอัตโนมัติ
 */
class ProjectReturnController extends Controller
{
    use FinanceHelpers;

    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinanceProjectReturn::with(['project', 'expenseCategory'])
            ->where('fiscal_year', $year)->latest('id')->get()
            ->map(fn (FinanceProjectReturn $r) => [
                'id' => $r->id,
                'doc_no' => $r->doc_no,
                'title' => $r->title,
                'project_id' => $r->project_id,
                'activity_id' => $r->activity_id,
                'expense_category_id' => $r->expense_category_id,
                'project' => $r->project?->name,
                'expense_category' => $r->expenseCategory?->name,
                'amount' => (float) $r->amount,
                'return_date' => $r->return_date?->format('Y-m-d'),
                'return_date_thai' => $this->thai($r->return_date),
            ]);

        return Inertia::render('Finance::ProjectReturns', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'options' => $this->masterOptions($year, ['project', 'activity', 'expense_category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request);

        DB::transaction(function () use ($request, $year, $v) {
            $receipt = FinanceReceipt::create([
                'fiscal_year' => $year,
                'money_class' => 'budget',
                'doc_no' => $v['doc_no'] ?? null,
                'title' => 'คืนเงินโครงการ — '.$v['title'],
                'nature' => 'bank',
                'amount' => $v['amount'],
                'receive_date' => $v['return_date'] ?? now()->toDateString(),
                'created_by' => $request->user()->id,
            ]);

            FinanceProjectReturn::create([
                'fiscal_year' => $year,
                'doc_no' => $v['doc_no'] ?? null,
                'title' => $v['title'],
                'project_id' => $v['project_id'] ?? null,
                'activity_id' => $v['activity_id'] ?? null,
                'expense_category_id' => $v['expense_category_id'] ?? null,
                'amount' => $v['amount'],
                'return_date' => $v['return_date'] ?? now()->toDateString(),
                'receipt_id' => $receipt->id,
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'บันทึกคืนเงินโครงการเรียบร้อยแล้ว (รับเงินเข้าทะเบียนรับงบฯ อัตโนมัติ)');
    }

    public function destroy(FinanceProjectReturn $projectReturn): RedirectResponse
    {
        DB::transaction(function () use ($projectReturn) {
            if ($projectReturn->receipt_id) {
                FinanceReceipt::where('id', $projectReturn->receipt_id)->delete();
            }
            $projectReturn->delete();
        });

        return back()->with('success', 'ลบรายการคืนเงินโครงการแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'doc_no' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:finance_masters,id'],
            'activity_id' => ['nullable', 'exists:finance_masters,id'],
            'expense_category_id' => ['nullable', 'exists:finance_masters,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'return_date' => ['nullable', 'date'],
        ]);
    }
}

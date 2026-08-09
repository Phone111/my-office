<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceAllocation;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceMaster;

/**
 * ทะเบียนการจัดสรรงบประมาณ / เงินงวด (AMSS การเงินฯ 2.1)
 */
class AllocationController extends Controller
{
    private const MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    private function thai($d): ?string
    {
        return $d ? $d->day.' '.self::MONTHS[$d->month].' '.($d->year + 543) : null;
    }

    /** ตัวเลือก master ตามชนิดสำหรับปีงบประมาณ */
    private function options(int $year): array
    {
        $out = [];
        foreach (['plan', 'project', 'activity', 'fund_source', 'expense_category'] as $type) {
            $out[$type] = FinanceMaster::ofType($type)->forYear($year)->where('is_active', true)
                ->orderBy('sort_order')->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn ($m) => ['id' => $m->id, 'label' => trim(($m->code ? $m->code.' ' : '').$m->name)]);
        }

        return $out;
    }

    public function index(Request $request): Response
    {
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinanceAllocation::with(['plan', 'project', 'activity', 'fundSource', 'expenseCategory'])
            ->where('fiscal_year', $year)
            ->orderBy('voucher_no')
            ->get()
            ->map(fn (FinanceAllocation $a) => [
                'id' => $a->id,
                'voucher_no' => $a->voucher_no,
                'doc_no' => $a->doc_no,
                'doc_date' => $a->doc_date?->format('Y-m-d'),
                'doc_date_thai' => $this->thai($a->doc_date),
                'allocation_ref' => $a->allocation_ref,
                'plan_id' => $a->plan_id,
                'project_id' => $a->project_id,
                'activity_id' => $a->activity_id,
                'activity_extra' => $a->activity_extra,
                'fund_source_id' => $a->fund_source_id,
                'account_code' => $a->account_code,
                'expense_category_id' => $a->expense_category_id,
                'plan' => $a->plan?->name,
                'project' => $a->project?->name,
                'fund_source' => $a->fundSource?->name,
                'expense_category' => $a->expenseCategory?->name,
                'title' => $a->title,
                'detail' => $a->detail,
                'amount' => (float) $a->amount,
                'received_at' => $a->received_at?->format('Y-m-d'),
                'file' => $a->file_path ? asset('storage/'.$a->file_path) : null,
            ]);

        return Inertia::render('Finance::Allocations', [
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'nextVoucher' => FinanceAllocation::nextVoucherNo($year),
            'options' => $this->options($year),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request);
        $v['fiscal_year'] = $year;
        $v['voucher_no'] = FinanceAllocation::nextVoucherNo($year);
        $v['received_at'] = $v['received_at'] ?? now()->toDateString();
        if ($request->hasFile('file')) {
            $v['file_path'] = $request->file('file')->store('finance/allocations', 'public');
        }
        $v['created_by'] = $request->user()->id;
        FinanceAllocation::create($v);

        return back()->with('success', 'บันทึกใบงวด (จัดสรรงบประมาณ) เรียบร้อยแล้ว');
    }

    public function update(Request $request, FinanceAllocation $allocation): RedirectResponse
    {
        $v = $this->validateData($request);
        if ($request->hasFile('file')) {
            if ($allocation->file_path) {
                Storage::disk('public')->delete($allocation->file_path);
            }
            $v['file_path'] = $request->file('file')->store('finance/allocations', 'public');
        }
        $allocation->update($v);

        return back()->with('success', 'แก้ไขใบงวดเรียบร้อยแล้ว');
    }

    public function destroy(FinanceAllocation $allocation): RedirectResponse
    {
        if ($allocation->file_path) {
            Storage::disk('public')->delete($allocation->file_path);
        }
        $allocation->delete();

        return back()->with('success', 'ลบใบงวดแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'doc_no' => ['nullable', 'string', 'max:255'],
            'doc_date' => ['nullable', 'date'],
            'allocation_ref' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:finance_masters,id'],
            'project_id' => ['nullable', 'exists:finance_masters,id'],
            'activity_id' => ['nullable', 'exists:finance_masters,id'],
            'activity_extra' => ['nullable', 'string', 'max:255'],
            'fund_source_id' => ['nullable', 'exists:finance_masters,id'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'expense_category_id' => ['nullable', 'exists:finance_masters,id'],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'received_at' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar', 'max:10240'],
        ]);
    }
}

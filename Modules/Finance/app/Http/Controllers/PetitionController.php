<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceAllocation;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinancePetition;
use Modules\Finance\Models\FinanceReceipt;
use Modules\Finance\Models\FinanceWithdrawal;
use Modules\Finance\Support\FinanceHelpers;

/**
 * ทะเบียนฎีกา — ขอเบิกเงินคงคลัง (AMSS 3.3) / เงินกันเหลื่อมปี (AMSS 3.6)
 * อ้างใบงวด ตัดยอด รวมหลายรายการขอเบิก/ขอยืมเป็นฎีกาเดียว และรับเงินเข้าทะเบียนรับงบฯ
 */
class PetitionController extends Controller
{
    use FinanceHelpers;

    private function assertType(string $type): void
    {
        abort_unless(array_key_exists($type, FinancePetition::TYPES), 404);
    }

    public function index(Request $request, string $type): Response
    {
        $this->assertType($type);
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinancePetition::with(['allocation', 'project', 'expenseCategory', 'withdrawals'])
            ->where('type', $type)->where('fiscal_year', $year)
            ->latest('id')->get()
            ->map(fn (FinancePetition $p) => [
                'id' => $p->id,
                'petition_no' => $p->petition_no,
                'doc_no' => $p->doc_no,
                'allocation_id' => $p->allocation_id,
                'voucher_no' => $p->allocation?->voucher_no,
                'plan_id' => $p->plan_id,
                'project_id' => $p->project_id,
                'activity_id' => $p->activity_id,
                'expense_category_id' => $p->expense_category_id,
                'project' => $p->project?->name,
                'expense_category' => $p->expenseCategory?->name,
                'title' => $p->title,
                'amount' => (float) $p->amount,
                'tax' => (float) $p->tax,
                'net' => (float) $p->net,
                'cancelled' => $p->cancelled,
                'linked' => $p->withdrawals->count(),
            ]);

        // รายการขอเบิก/ขอยืมที่ยังไม่ผูกฎีกา (สำหรับรวมเข้าฎีกา)
        $unlinked = FinanceWithdrawal::with('expenseCategory')
            ->where('fiscal_year', $year)->whereNull('petition_id')
            ->latest('id')->get()
            ->map(fn (FinanceWithdrawal $w) => [
                'id' => $w->id,
                'label' => ($w->doc_no ? '['.$w->doc_no.'] ' : '').$w->title,
                'kind_label' => FinanceWithdrawal::KINDS[$w->kind] ?? $w->kind,
                'amount' => (float) $w->amount,
                'expense_category_id' => $w->expense_category_id,
            ]);

        return Inertia::render('Finance::Petitions', [
            'type' => $type,
            'typeLabel' => FinancePetition::TYPES[$type],
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'totalNet' => (float) $rows->sum('net'),
            'options' => $this->masterOptions($year, ['plan', 'project', 'activity', 'expense_category']),
            'allocations' => FinanceAllocation::where('fiscal_year', $year)->orderBy('voucher_no')
                ->get(['id', 'voucher_no', 'title'])
                ->map(fn ($a) => ['id' => $a->id, 'label' => 'ใบงวด '.$a->voucher_no.' — '.$a->title]),
            'unlinked' => $unlinked,
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->assertType($type);
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request);

        DB::transaction(function () use ($request, $type, $year, $v) {
            $net = round((float) $v['amount'] - (float) ($v['tax'] ?? 0), 2);
            $petition = FinancePetition::create([
                'fiscal_year' => $year,
                'type' => $type,
                'petition_no' => ($v['petition_no'] ?? null) ?: (string) FinancePetition::nextNo($year),
                'doc_no' => $v['doc_no'] ?? null,
                'allocation_id' => $v['allocation_id'] ?? null,
                'plan_id' => $v['plan_id'] ?? null,
                'project_id' => $v['project_id'] ?? null,
                'activity_id' => $v['activity_id'] ?? null,
                'expense_category_id' => $v['expense_category_id'] ?? null,
                'title' => $v['title'],
                'amount' => $v['amount'],
                'tax' => $v['tax'] ?? 0,
                'net' => $net,
                'created_by' => $request->user()->id,
            ]);

            // รวมรายการขอเบิก/ขอยืมเข้าฎีกานี้
            if (! empty($v['withdrawal_ids'])) {
                FinanceWithdrawal::whereIn('id', $v['withdrawal_ids'])
                    ->whereNull('petition_id')->where('fiscal_year', $year)
                    ->update(['petition_id' => $petition->id]);
            }

            // ขอเบิกเงินคงคลัง: รับเงินเข้าทะเบียนรับเงินงบประมาณอัตโนมัติ (รับจริง)
            if ($type === 'treasury' && $net > 0) {
                FinanceReceipt::create([
                    'fiscal_year' => $year,
                    'money_class' => 'budget',
                    'doc_no' => $petition->petition_no,
                    'title' => 'รับเงินงบประมาณตามฎีกา '.$petition->petition_no.' — '.$petition->title,
                    'nature' => 'bank',
                    'amount' => $net,
                    'receive_date' => now()->toDateString(),
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('success', 'บันทึกฎีกาเรียบร้อยแล้ว'.($type === 'treasury' ? ' (รับเงินเข้าทะเบียนรับงบฯ อัตโนมัติ)' : ''));
    }

    public function destroy(FinancePetition $petition): RedirectResponse
    {
        $petition->withdrawals()->update(['petition_id' => null]);
        $petition->delete();

        return back()->with('success', 'ลบฎีกาแล้ว (ปลดการผูกรายการขอเบิก)');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'petition_no' => ['nullable', 'string', 'max:255'],
            'doc_no' => ['nullable', 'string', 'max:255'],
            'allocation_id' => ['nullable', 'exists:finance_allocations,id'],
            'plan_id' => ['nullable', 'exists:finance_masters,id'],
            'project_id' => ['nullable', 'exists:finance_masters,id'],
            'activity_id' => ['nullable', 'exists:finance_masters,id'],
            'expense_category_id' => ['nullable', 'exists:finance_masters,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'withdrawal_ids' => ['nullable', 'array'],
            'withdrawal_ids.*' => ['integer', 'exists:finance_withdrawals,id'],
        ]);
    }
}

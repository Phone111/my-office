<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinanceMaster;
use Modules\Finance\Models\FinanceReceipt;

/**
 * ทะเบียนรับเงิน (AMSS การเงินฯ 2.2–2.4)
 * จัดการ 3 ประเภทผ่านพารามิเตอร์ class: budget | nonbudget | state_revenue
 */
class ReceiptController extends Controller
{
    private const MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    private function thai($d): ?string
    {
        return $d ? $d->day.' '.self::MONTHS[$d->month].' '.($d->year + 543) : null;
    }

    private function assertClass(string $class): void
    {
        abort_unless(array_key_exists($class, FinanceReceipt::CLASSES), 404);
    }

    public function index(Request $request, string $class): Response
    {
        $this->assertClass($class);
        $year = (int) ($request->query('year') ?: FinanceFiscalYear::current());

        $rows = FinanceReceipt::with('moneyType')
            ->where('money_class', $class)
            ->where('fiscal_year', $year)
            ->latest('receive_date')->latest('id')
            ->get()
            ->map(fn (FinanceReceipt $r) => [
                'id' => $r->id,
                'doc_no' => $r->doc_no,
                'title' => $r->title,
                'money_type_id' => $r->money_type_id,
                'money_type' => $r->moneyType?->name,
                'nature' => $r->nature,
                'nature_label' => $r->nature ? (FinanceReceipt::NATURES[$r->nature] ?? $r->nature) : null,
                'amount' => (float) $r->amount,
                'receive_date' => $r->receive_date?->format('Y-m-d'),
                'receive_date_thai' => $this->thai($r->receive_date),
                'file' => $r->file_path ? asset('storage/'.$r->file_path) : null,
            ]);

        // ประเภทของเงิน (เฉพาะนอกงบ/รายได้แผ่นดิน)
        $moneyTypes = [];
        if (isset(FinanceReceipt::CLASS_MAIN[$class])) {
            $moneyTypes = FinanceMaster::ofType('money_type')
                ->where('main_type', FinanceReceipt::CLASS_MAIN[$class])
                ->where('is_active', true)->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn ($m) => ['id' => $m->id, 'label' => trim(($m->code ? $m->code.' ' : '').$m->name)]);
        }

        return Inertia::render('Finance::Receipts', [
            'class' => $class,
            'classLabel' => FinanceReceipt::CLASSES[$class],
            'year' => $year,
            'years' => FinanceFiscalYear::orderByDesc('year')->get(['id', 'year', 'is_current']),
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'natures' => FinanceReceipt::NATURES,
            'moneyTypes' => $moneyTypes,
            'usesMoneyType' => isset(FinanceReceipt::CLASS_MAIN[$class]),
        ]);
    }

    public function store(Request $request, string $class): RedirectResponse
    {
        $this->assertClass($class);
        $year = (int) ($request->input('fiscal_year') ?: FinanceFiscalYear::current());
        $v = $this->validateData($request, $class);
        $v['money_class'] = $class;
        $v['fiscal_year'] = $year;
        $v['receive_date'] = $v['receive_date'] ?? now()->toDateString();
        if ($request->hasFile('file')) {
            $v['file_path'] = $request->file('file')->store('finance/receipts', 'public');
        }
        $v['created_by'] = $request->user()->id;
        FinanceReceipt::create($v);

        return back()->with('success', 'บันทึกรับเงินเรียบร้อยแล้ว');
    }

    public function update(Request $request, FinanceReceipt $receipt): RedirectResponse
    {
        $v = $this->validateData($request, $receipt->money_class);
        if ($request->hasFile('file')) {
            if ($receipt->file_path) {
                Storage::disk('public')->delete($receipt->file_path);
            }
            $v['file_path'] = $request->file('file')->store('finance/receipts', 'public');
        }
        $receipt->update($v);

        return back()->with('success', 'แก้ไขรายการรับเงินเรียบร้อยแล้ว');
    }

    public function destroy(FinanceReceipt $receipt): RedirectResponse
    {
        if ($receipt->file_path) {
            Storage::disk('public')->delete($receipt->file_path);
        }
        $receipt->delete();

        return back()->with('success', 'ลบรายการรับเงินแล้ว');
    }

    private function validateData(Request $request, string $class): array
    {
        $rules = [
            'doc_no' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'nature' => ['nullable', Rule::in(array_keys(FinanceReceipt::NATURES))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'receive_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar', 'max:10240'],
        ];
        $rules['money_type_id'] = isset(FinanceReceipt::CLASS_MAIN[$class])
            ? ['required', 'exists:finance_masters,id']
            : ['nullable'];

        return $request->validate($rules);
    }
}

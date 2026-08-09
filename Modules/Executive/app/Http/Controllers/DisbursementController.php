<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\BudgetDisbursement;
use Modules\Executive\Models\ProjectBudget;

/**
 * บันทึกการเบิกจ่าย — บันทึกการเบิกจ่ายแยกรายการต่อโครงการ (ยอดรวมคำนวณอัตโนมัติ)
 */
class DisbursementController extends Controller
{
    private const MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    private function thai($d): ?string
    {
        return $d ? $d->day.' '.self::MONTHS[$d->month].' '.($d->year + 543) : null;
    }

    public function index(): Response
    {
        $projects = ProjectBudget::with('disbursements')->latest()->get()->map(fn (ProjectBudget $p) => [
            'id' => $p->id,
            'project_name' => $p->project_name,
            'fiscal_year' => $p->fiscal_year,
            'allocated_amount' => (float) $p->allocated_amount,
            'disbursed_amount' => (float) $p->disbursed_amount,
            'remaining' => $p->remaining(),
            'percent' => $p->percentDisbursed(),
            'entries' => $p->disbursements->map(fn (BudgetDisbursement $d) => [
                'id' => $d->id,
                'date' => $d->disburse_date?->format('Y-m-d'),
                'date_thai' => $this->thai($d->disburse_date),
                'amount' => (float) $d->amount,
                'description' => $d->description,
                'file' => $d->file_path ? asset('storage/'.$d->file_path) : null,
            ]),
        ]);

        return Inertia::render('Executive::Disbursements', ['projects' => $projects]);
    }

    public function store(Request $request, ProjectBudget $budget): RedirectResponse
    {
        $v = $request->validate([
            'disburse_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $budget->disbursements()->create([
            'disburse_date' => $v['disburse_date'],
            'amount' => $v['amount'],
            'description' => $v['description'] ?? null,
            'file_path' => $request->hasFile('file') ? $request->file('file')->store('disbursements', 'public') : null,
            'created_by' => $request->user()->id,
        ]);
        $budget->syncDisbursed();

        return back()->with('success', 'บันทึกการเบิกจ่ายเรียบร้อยแล้ว');
    }

    public function update(Request $request, BudgetDisbursement $disbursement): RedirectResponse
    {
        $v = $request->validate([
            'disburse_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $disbursement->disburse_date = $v['disburse_date'];
        $disbursement->amount = $v['amount'];
        $disbursement->description = $v['description'] ?? null;
        if ($request->hasFile('file')) {
            if ($disbursement->file_path) {
                Storage::disk('public')->delete($disbursement->file_path);
            }
            $disbursement->file_path = $request->file('file')->store('disbursements', 'public');
        }
        $disbursement->save();
        $disbursement->project->syncDisbursed();

        return back()->with('success', 'แก้ไขรายการเบิกจ่ายเรียบร้อยแล้ว');
    }

    public function destroy(BudgetDisbursement $disbursement): RedirectResponse
    {
        $project = $disbursement->project;
        if ($disbursement->file_path) {
            Storage::disk('public')->delete($disbursement->file_path);
        }
        $disbursement->delete();
        $project->syncDisbursed();

        return back()->with('success', 'ลบรายการเบิกจ่ายแล้ว');
    }
}

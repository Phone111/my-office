<?php

namespace Modules\Executive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Executive\Models\ProjectBudget;

/**
 * รายงานผลเบิกจ่ายงบประมาณรายโครงการ (เลขาฯ/รองผอ./แอดมิน)
 */
class ProjectBudgetController extends Controller
{
    public function index(): Response
    {
        $budgets = ProjectBudget::latest()->get();

        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        $rows = $budgets->map(fn (ProjectBudget $b) => [
            'id' => $b->id,
            'project_name' => $b->project_name,
            'fiscal_year' => $b->fiscal_year,
            'project_date' => optional($b->project_date)->format('Y-m-d'),
            'project_date_thai' => $b->project_date ? $b->project_date->day.' '.$months[$b->project_date->month].' '.($b->project_date->year + 543) : null,
            'allocated_amount' => (float) $b->allocated_amount,
            'disbursed_amount' => (float) $b->disbursed_amount,
            'remaining' => $b->remaining(),
            'percent' => $b->percentDisbursed(),
            'note' => $b->note,
            'file' => $b->file_path ? asset('storage/'.$b->file_path) : null,
        ]);

        $t = \Illuminate\Support\Carbon::today();
        $systemYear = ($t->month >= 10 ? $t->year + 1 : $t->year) + 543;
        $planYear = (int) (\Modules\Saraban\Models\SarabanSetting::get('plan_year') ?: $systemYear);

        return Inertia::render('Executive::Budgets', [
            'budgets' => $rows,
            'planYear' => $planYear,
            'summary' => [
                'allocated' => (float) $budgets->sum('allocated_amount'),
                'disbursed' => (float) $budgets->sum('disbursed_amount'),
                'remaining' => (float) $budgets->sum('allocated_amount') - (float) $budgets->sum('disbursed_amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('project-budgets', 'public');
        }
        $data['created_by'] = $request->user()->id;
        $data['disbursed_amount'] = 0; // ยอดเบิกจ่ายมาจากเมนู "บันทึกการเบิกจ่าย"
        ProjectBudget::create($data);

        return back()->with('success', 'เพิ่มโครงการเรียบร้อยแล้ว');
    }

    public function update(Request $request, ProjectBudget $budget): RedirectResponse
    {
        $data = $this->validateData($request);
        if ($request->hasFile('file')) {
            if ($budget->file_path) {
                Storage::disk('public')->delete($budget->file_path);
            }
            $data['file_path'] = $request->file('file')->store('project-budgets', 'public');
        }
        $budget->update($data);

        return back()->with('success', 'แก้ไขข้อมูลโครงการเรียบร้อยแล้ว');
    }

    public function destroy(ProjectBudget $budget): RedirectResponse
    {
        if ($budget->file_path) {
            Storage::disk('public')->delete($budget->file_path);
        }
        $budget->delete();

        return back()->with('success', 'ลบโครงการเรียบร้อยแล้ว');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_name' => ['required', 'string', 'max:255'],
            'fiscal_year' => ['required', 'string', 'max:10'],
            'project_date' => ['nullable', 'date'],
            'allocated_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'note' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);
    }
}

<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Department;
use Modules\Core\Models\DevelopmentPlan;
use Modules\Executive\Models\StaffAward;
use Modules\Executive\Models\StaffTraining;

/**
 * "ID Plan ของฉัน" — แผนพัฒนาตนเองของครูและบุคลากร
 * รวม: แผนพัฒนาตนเอง (เป้าหมาย) + ข้อมูลรางวัล + ข้อมูลการอบรม/พัฒนา (เฉพาะของตนเอง)
 */
class DevelopmentPlanController extends Controller
{
    /** ตัวเลือกระดับรางวัล */
    private const LEVELS = [
        'ระดับสถานศึกษา',
        'ระดับกลุ่มโรงเรียน',
        'ระดับเขตพื้นที่การศึกษา',
        'ระดับจังหวัด',
        'ระดับภาค',
        'ระดับชาติ',
        'ระดับนานาชาติ',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $plans = DevelopmentPlan::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (DevelopmentPlan $p) => [
                'id' => $p->id,
                'academic_year' => $p->academic_year,
                'goals' => $p->goals,
                'file_path' => $p->file_path,
                'updated_at' => $p->updated_at->format('Y-m-d H:i'),
            ]);

        $awards = StaffAward::where('staff_name', $user->name)
            ->orderByDesc('awarded_date')
            ->get()
            ->map(fn (StaffAward $x) => [
                'id' => $x->id,
                'award_name' => $x->award_name,
                'level' => $x->level,
                'awarded_by' => $x->awarded_by,
                'awarded_date' => $x->awarded_date->format('d/m/Y'),
                'note' => $x->note,
                'file_url' => $x->file_path ? asset('storage/'.$x->file_path) : null,
            ]);

        $trainings = StaffTraining::where('staff_name', $user->name)
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (StaffTraining $x) => [
                'id' => $x->id,
                'course_name' => $x->course_name,
                'subject_group' => $x->subject_group,
                'organizer' => $x->organizer,
                'start_date' => $x->start_date->format('d/m/Y'),
                'end_date' => $x->end_date?->format('d/m/Y'),
                'hours' => $x->hours,
                'budget_source' => $x->budget_source,
                'location' => $x->location,
                'note' => $x->note,
                'file_url' => $x->file_path ? asset('storage/'.$x->file_path) : null,
            ]);

        return Inertia::render('Core::IdPlan', [
            'plans' => $plans,
            'awards' => $awards,
            'trainings' => $trainings,
            'levels' => self::LEVELS,
            'subjectGroups' => Department::orderBy('name')->pluck('name')->unique()->values(),
        ]);
    }

    /**
     * เพิ่มรางวัลของตนเอง
     */
    public function storeAward(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'award_name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'awarded_date' => ['required', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        StaffAward::create([
            'award_name' => $data['award_name'],
            'level' => $data['level'] ?? null,
            'awarded_date' => $data['awarded_date'],
            'file_path' => $request->hasFile('file')
                ? $request->file('file')->store('staff-awards', 'public')
                : null,
            'staff_name' => $request->user()->name,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('id-plan.index')->with('success', 'บันทึกรางวัลเรียบร้อยแล้ว');
    }

    /**
     * เพิ่มการอบรม/พัฒนาของตนเอง
     */
    public function storeTraining(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'subject_group' => ['nullable', 'string', 'max:255'],
            'hours' => ['required', 'integer', 'min:0'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'budget_source' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        StaffTraining::create([
            'course_name' => $data['course_name'],
            'subject_group' => $data['subject_group'] ?? null,
            'hours' => $data['hours'],
            'organizer' => $data['organizer'] ?? null,
            'start_date' => $data['start_date'],
            'budget_source' => $data['budget_source'] ?? null,
            'file_path' => $request->hasFile('file')
                ? $request->file('file')->store('staff-trainings', 'public')
                : null,
            'staff_name' => $request->user()->name,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('id-plan.index')->with('success', 'บันทึกการอบรมเรียบร้อยแล้ว');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:10'],
            'goals' => ['required', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $plan = new DevelopmentPlan([
            'user_id' => $request->user()->id,
            'academic_year' => $validated['academic_year'],
            'goals' => $validated['goals'],
        ]);

        if ($request->hasFile('file')) {
            $plan->file_path = $request->file('file')->store('id-plans', 'public');
        }

        $plan->save();

        return redirect()
            ->route('id-plan.index')
            ->with('success', 'บันทึก ID Plan เรียบร้อยแล้ว');
    }

    public function update(Request $request, DevelopmentPlan $plan): RedirectResponse
    {
        $this->authorizeOwner($request, $plan);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:10'],
            'goals' => ['required', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $plan->academic_year = $validated['academic_year'];
        $plan->goals = $validated['goals'];

        if ($request->hasFile('file')) {
            if ($plan->file_path) {
                Storage::disk('public')->delete($plan->file_path);
            }
            $plan->file_path = $request->file('file')->store('id-plans', 'public');
        }

        $plan->save();

        return redirect()
            ->route('id-plan.index')
            ->with('success', 'แก้ไข ID Plan เรียบร้อยแล้ว');
    }

    public function destroy(Request $request, DevelopmentPlan $plan): RedirectResponse
    {
        $this->authorizeOwner($request, $plan);

        if ($plan->file_path) {
            Storage::disk('public')->delete($plan->file_path);
        }

        $plan->delete();

        return redirect()
            ->route('id-plan.index')
            ->with('success', 'ลบ ID Plan เรียบร้อยแล้ว');
    }

    /**
     * อนุญาตเฉพาะเจ้าของแผนเท่านั้น
     */
    private function authorizeOwner(Request $request, DevelopmentPlan $plan): void
    {
        abort_unless($plan->user_id === $request->user()->id, 403);
    }
}

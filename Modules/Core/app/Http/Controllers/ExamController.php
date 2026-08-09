<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\ExamQuestion;
use Modules\Core\Models\ExamResult;
use Modules\Core\Models\ExamRun;
use Modules\Core\Models\ExamTest;
use Modules\Core\Models\Unit;

/**
 * ระบบทดสอบการศึกษา — คลังข้อสอบ / แบบทดสอบ / รายการสอบ / ผลรายโรงเรียน + รายงาน
 */
class ExamController extends Controller
{
    private function canManage(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin', 'secretary', 'supervisor']);
    }

    // ===== คลังข้อสอบ =====
    public function questions(Request $request): Response
    {
        $q = ExamQuestion::latest();
        if ($s = $request->input('subject')) {
            $q->where('subject', $s);
        }
        if ($g = $request->input('grade')) {
            $q->where('grade', $g);
        }

        return Inertia::render('Core::Exam/Questions', [
            'rows' => $q->limit(500)->get()->map(fn (ExamQuestion $x) => [
                'id' => $x->id, 'subject' => $x->subject, 'grade' => $x->grade,
                'standard' => $x->standard, 'indicator' => $x->indicator,
                'question' => $x->question, 'options' => $x->options ?? [], 'answer' => $x->answer, 'score' => $x->score,
            ]),
            'subjects' => ExamQuestion::SUBJECTS,
            'grades' => ExamQuestion::GRADES,
            'filters' => ['subject' => $request->input('subject'), 'grade' => $request->input('grade')],
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'subject' => ['required', 'string', 'max:100'],
            'grade' => ['required', 'string', 'max:20'],
            'standard' => ['nullable', 'string', 'max:100'],
            'indicator' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['nullable', 'string', 'max:255'],
            'answer' => ['required', 'integer', 'min:0'],
            'score' => ['nullable', 'integer', 'min:1'],
        ]);
        // ข้อที่ถูกต้องต้องไม่เกินจำนวนตัวเลือก
        abort_if($v['answer'] >= count($v['options']), 422, 'หมายเลขข้อที่ถูกต้องเกินจำนวนตัวเลือก');
        $v['options'] = array_values(array_filter($v['options'], fn ($o) => trim((string) $o) !== ''));
        $v['created_by'] = $request->user()->id;
        ExamQuestion::create($v);

        return back()->with('success', 'เพิ่มข้อสอบเข้าคลังเรียบร้อย');
    }

    public function destroyQuestion(Request $request, ExamQuestion $question): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $question->delete();

        return back()->with('success', 'ลบข้อสอบแล้ว');
    }

    // ===== แบบทดสอบ (ต้นฉบับ) =====
    public function tests(Request $request): Response
    {
        abort_unless($this->canManage($request->user()), 403);

        return Inertia::render('Core::Exam/Tests', [
            'tests' => ExamTest::withCount('questions')->latest()->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'subject' => $t->subject, 'grade' => $t->grade, 'questions' => $t->questions_count]),
            'subjects' => ExamQuestion::SUBJECTS,
            'grades' => ExamQuestion::GRADES,
            'questionBank' => ExamQuestion::latest()->get(['id', 'subject', 'grade', 'question'])
                ->map(fn ($x) => ['id' => $x->id, 'subject' => $x->subject, 'grade' => $x->grade, 'question' => $x->question]),
        ]);
    }

    public function storeTest(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:100'],
            'grade' => ['required', 'string', 'max:20'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:exam_questions,id'],
        ]);
        $test = ExamTest::create(['name' => $v['name'], 'subject' => $v['subject'], 'grade' => $v['grade'], 'created_by' => $request->user()->id]);
        $sync = [];
        foreach (array_values($v['question_ids']) as $i => $qid) {
            $sync[$qid] = ['sort' => $i];
        }
        $test->questions()->sync($sync);

        return back()->with('success', 'สร้างแบบทดสอบเรียบร้อย');
    }

    public function destroyTest(Request $request, ExamTest $test): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $test->delete();

        return back()->with('success', 'ลบแบบทดสอบแล้ว');
    }

    // ===== รายการสอบ =====
    public function runs(Request $request): Response
    {
        abort_unless($this->canManage($request->user()), 403);

        return Inertia::render('Core::Exam/Runs', [
            'runs' => ExamRun::with('test:id,name,subject,grade')->withCount('results')->latest()->get()
                ->map(fn ($r) => [
                    'id' => $r->id, 'name' => $r->name, 'academic_year' => $r->academic_year, 'round' => $r->round,
                    'test' => $r->test?->name, 'subject' => $r->test?->subject, 'grade' => $r->test?->grade, 'results' => $r->results_count,
                ]),
            'tests' => ExamTest::latest()->get(['id', 'name', 'subject', 'grade'])
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'subject' => $t->subject, 'grade' => $t->grade]),
        ]);
    }

    public function storeRun(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'exam_test_id' => ['required', 'integer', 'exists:exam_tests,id'],
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['nullable', 'integer', 'min:2400', 'max:2700'],
            'round' => ['nullable', 'string', 'max:50'],
        ]);
        ExamRun::create($v + ['created_by' => $request->user()->id]);

        return back()->with('success', 'สร้างรายการสอบเรียบร้อย');
    }

    public function destroyRun(Request $request, ExamRun $run): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $run->delete();

        return back()->with('success', 'ลบรายการสอบแล้ว');
    }

    // ===== ผล + รายงาน รายการสอบ =====
    public function run(Request $request, ExamRun $run): Response
    {
        abort_unless($this->canManage($request->user()), 403);
        $run->load('test:id,name,subject,grade');
        $results = ExamResult::with('school:id,name')->where('exam_run_id', $run->id)->get()
            ->map(fn (ExamResult $r) => [
                'id' => $r->id, 'school' => $r->school?->name, 'school_unit_id' => $r->school_unit_id,
                'students' => $r->students, 'passed' => $r->passed, 'avg_percent' => $r->avg_percent, 'note' => $r->note,
            ]);

        $avg = $results->whereNotNull('avg_percent')->pluck('avg_percent');
        $sumStudents = $results->sum('students');
        $sumPassed = $results->sum('passed');

        return Inertia::render('Core::Exam/Run', [
            'run' => ['id' => $run->id, 'name' => $run->name, 'test' => $run->test?->name, 'subject' => $run->test?->subject, 'grade' => $run->test?->grade, 'academic_year' => $run->academic_year, 'round' => $run->round],
            'results' => $results,
            'schools' => Unit::schools()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'summary' => [
                'schools' => $results->count(),
                'students' => $sumStudents,
                'passed' => $sumPassed,
                'pass_rate' => $sumStudents ? round($sumPassed / $sumStudents * 100, 2) : null,
                'avg_percent' => $avg->count() ? round($avg->avg(), 2) : null,
            ],
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function saveResult(Request $request, ExamRun $run): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'school_unit_id' => ['required', 'integer', 'exists:units,id'],
            'students' => ['required', 'integer', 'min:0'],
            'passed' => ['required', 'integer', 'min:0', 'lte:students'],
            'avg_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        ExamResult::updateOrCreate(
            ['exam_run_id' => $run->id, 'school_unit_id' => $v['school_unit_id']],
            ['students' => $v['students'], 'passed' => $v['passed'], 'avg_percent' => $v['avg_percent'] ?? null, 'note' => $v['note'] ?? null, 'entered_by' => $request->user()->id],
        );

        return back()->with('success', 'บันทึกผลการสอบเรียบร้อย');
    }

    public function destroyResult(Request $request, ExamRun $run, ExamResult $result): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $result->delete();

        return back()->with('success', 'ลบผลแล้ว');
    }
}

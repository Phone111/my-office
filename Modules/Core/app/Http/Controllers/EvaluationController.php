<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Evaluation;
use Modules\Core\Models\EvaluationCriteria;
use Modules\Core\Models\EvaluationRound;
use Modules\Core\Models\Unit;
use Modules\Core\Notifications\ApprovalNotification;

/**
 * ระบบประเมินผลการปฏิบัติงาน (แนว ว.PA) — ผู้บังคับบัญชาประเมิน → ผู้รับการประเมินรับทราบ
 */
class EvaluationController extends Controller
{
    private function canEvaluate(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin', 'director', 'deputy_director', 'head_of_department', 'head_of_subject', 'secretary']);
    }

    private function canManage(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin', 'director', 'secretary']);
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    private function mapRow(Evaluation $e): array
    {
        return [
            'id' => $e->id,
            'evaluee' => $e->evaluee?->name,
            'evaluator' => $e->evaluator?->name,
            'round' => $e->round?->name,
            'percent' => $e->percent,
            'grade' => $e->grade,
            'status' => $e->status,
            'status_label' => Evaluation::STATUSES[$e->status] ?? $e->status,
        ];
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $mine = Evaluation::with(['evaluator:id,name', 'round:id,name'])
            ->where('evaluee_id', $user->id)->where('status', '!=', Evaluation::STATUS_DRAFT)
            ->latest()->get()->map(fn ($e) => $this->mapRow($e));

        $given = collect();
        if ($this->canEvaluate($user)) {
            $given = Evaluation::with(['evaluee:id,name', 'round:id,name'])
                ->where('evaluator_id', $user->id)->latest()->get()->map(fn ($e) => $this->mapRow($e));
        }

        return Inertia::render('Core::Evaluation/Index', [
            'mine' => $mine,
            'given' => $given,
            'canEvaluate' => $this->canEvaluate($user),
            'canManage' => $this->canManage($user),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->canEvaluate($user), 403);

        // ผู้ถูกประเมิน: admin/area_admin = ทุกคน, อื่น ๆ = ในหน่วยงานตน
        $people = User::query()
            ->when(! $user->hasAnyRole(['admin', 'area_admin']), fn ($q) => $q->where('unit_id', $user->unit_id))
            ->where('id', '!=', $user->id)->orderBy('name')->limit(1000)->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Core::Evaluation/Create', [
            'people' => $people,
            'rounds' => EvaluationRound::where('is_active', true)->orderByDesc('id')->get(['id', 'name', 'is_current'])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'is_current' => $r->is_current]),
            'criteria' => EvaluationCriteria::where('is_active', true)->orderBy('sort')->get(['id', 'name', 'max_score'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'max_score' => $c->max_score]),
            'currentRound' => EvaluationRound::where('is_current', true)->value('id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canEvaluate($user), 403);

        $v = $request->validate([
            'evaluee_id' => ['required', 'integer', 'exists:users,id'],
            'round_id' => ['nullable', 'integer', 'exists:evaluation_rounds,id'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'improvements' => ['nullable', 'string', 'max:2000'],
            'evaluator_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $evaluee = User::find($v['evaluee_id']);
        // กันประเมินซ้ำ — ผู้ประเมินคนเดิม/ผู้ถูกประเมิน/รอบเดิม = อัปเดตของเดิม ไม่สร้างใหม่
        $eval = Evaluation::updateOrCreate(
            ['round_id' => $v['round_id'] ?? null, 'evaluee_id' => $v['evaluee_id'], 'evaluator_id' => $user->id],
            [
                'unit_id' => $evaluee->unit_id,
                'strengths' => $v['strengths'] ?? null,
                'improvements' => $v['improvements'] ?? null,
                'evaluator_comment' => $v['evaluator_comment'] ?? null,
                'status' => Evaluation::STATUS_EVALUATED,
            ]
        );

        $this->saveScores($eval, $request->input('scores', []));
        $this->notifyEvaluee($eval);

        return redirect()->route('evaluations.index')->with('success', 'บันทึกผลการประเมินและแจ้งผู้รับการประเมินแล้ว');
    }

    public function show(Request $request, Evaluation $evaluation): Response
    {
        $user = $request->user();
        $isEvaluee = $evaluation->evaluee_id === $user->id;
        $isEvaluator = $evaluation->evaluator_id === $user->id;
        abort_unless($isEvaluee || $isEvaluator || $this->canManage($user), 403);

        $evaluation->load(['evaluee:id,name', 'evaluator:id,name', 'round:id,name', 'scores.criteria']);
        $criteria = EvaluationCriteria::where('is_active', true)->orderBy('sort')->get(['id', 'name', 'max_score']);
        $scoreMap = $evaluation->scores->keyBy('criteria_id');

        return Inertia::render('Core::Evaluation/Show', [
            'item' => array_merge($this->mapRow($evaluation), [
                'total_score' => $evaluation->total_score,
                'strengths' => $evaluation->strengths,
                'improvements' => $evaluation->improvements,
                'evaluator_comment' => $evaluation->evaluator_comment,
                'evaluee_note' => $evaluation->evaluee_note,
                'acknowledged_thai' => $this->thai($evaluation->acknowledged_at),
                'scores' => $criteria->map(fn ($c) => [
                    'name' => $c->name, 'max_score' => $c->max_score, 'score' => $scoreMap->get($c->id)?->score,
                ]),
            ]),
            'canEdit' => $isEvaluator && $evaluation->status !== Evaluation::STATUS_ACKNOWLEDGED,
            'canAck' => $isEvaluee && $evaluation->status === Evaluation::STATUS_EVALUATED,
            'criteria' => $criteria->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'max_score' => $c->max_score, 'score' => $scoreMap->get($c->id)?->score]),
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): RedirectResponse
    {
        abort_unless($evaluation->evaluator_id === $request->user()->id && $evaluation->status !== Evaluation::STATUS_ACKNOWLEDGED, 403);
        $v = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'improvements' => ['nullable', 'string', 'max:2000'],
            'evaluator_comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $evaluation->update([
            'strengths' => $v['strengths'] ?? null,
            'improvements' => $v['improvements'] ?? null,
            'evaluator_comment' => $v['evaluator_comment'] ?? null,
        ]);
        $this->saveScores($evaluation, $request->input('scores', []));

        return back()->with('success', 'อัปเดตผลการประเมินเรียบร้อย');
    }

    public function acknowledge(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($evaluation->evaluee_id === $user->id && $evaluation->status === Evaluation::STATUS_EVALUATED, 403);
        $v = $request->validate(['evaluee_note' => ['nullable', 'string', 'max:2000']]);

        $evaluation->update([
            'evaluee_note' => $v['evaluee_note'] ?? null,
            'status' => Evaluation::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);

        $user->unreadNotifications->each(function ($n) use ($evaluation) {
            if (($n->data['key'] ?? null) === 'evaluation-'.$evaluation->id) {
                $n->markAsRead();
            }
        });

        return back()->with('success', 'รับทราบผลการประเมินเรียบร้อย');
    }

    public function destroy(Request $request, Evaluation $evaluation): RedirectResponse
    {
        abort_unless($evaluation->evaluator_id === $request->user()->id || $this->canManage($request->user()), 403);
        \Illuminate\Notifications\DatabaseNotification::whereJsonContains('data->key', 'evaluation-'.$evaluation->id)->delete();
        $evaluation->delete();

        return redirect()->route('evaluations.index')->with('success', 'ลบการประเมินแล้ว');
    }

    /** บันทึกคะแนน + คำนวณรวม/ร้อยละ/ระดับ */
    private function saveScores(Evaluation $eval, array $scores): void
    {
        $criteria = EvaluationCriteria::where('is_active', true)->get();
        $total = 0;
        $maxTotal = 0;
        foreach ($criteria as $c) {
            $maxTotal += (float) $c->max_score;
            $score = isset($scores[$c->id]) && $scores[$c->id] !== '' ? max(0, min((float) $scores[$c->id], (float) $c->max_score)) : null;
            $eval->scores()->updateOrCreate(['criteria_id' => $c->id], ['score' => $score]);
            $total += (float) ($score ?? 0);
        }
        $percent = $maxTotal > 0 ? round($total / $maxTotal * 100, 2) : 0;
        $eval->update(['total_score' => $total, 'percent' => $percent, 'grade' => Evaluation::gradeFor($percent)]);
    }

    private function notifyEvaluee(Evaluation $eval): void
    {
        $eval->evaluee?->notify(new ApprovalNotification(
            'ผลการประเมินรอรับทราบ',
            'มีผลการประเมินผลการปฏิบัติงานรอการรับทราบจากคุณ',
            route('evaluations.show', $eval->id),
            'info',
            'evaluation-'.$eval->id,
        ));
    }

    // ===== ตั้งค่า (รอบ/องค์ประกอบ) =====
    public function settings(Request $request): Response
    {
        abort_unless($this->canManage($request->user()), 403);

        return Inertia::render('Core::Evaluation/Settings', [
            'criteria' => EvaluationCriteria::orderBy('sort')->get(['id', 'name', 'max_score'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'max_score' => $c->max_score]),
            'rounds' => EvaluationRound::orderByDesc('id')->get(['id', 'name', 'is_current'])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'is_current' => $r->is_current]),
        ]);
    }

    public function storeCriteria(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'max_score' => ['required', 'numeric', 'min:1']]);
        EvaluationCriteria::create($v + ['sort' => (EvaluationCriteria::max('sort') ?? 0) + 1]);

        return back()->with('success', 'เพิ่มองค์ประกอบเรียบร้อย');
    }

    public function destroyCriteria(Request $request, EvaluationCriteria $criteria): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $criteria->delete();

        return back()->with('success', 'ลบองค์ประกอบแล้ว');
    }

    public function storeRound(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'fiscal_year' => ['nullable', 'integer', 'min:2400', 'max:2700'], 'period' => ['nullable', 'integer', 'in:1,2']]);
        EvaluationRound::create($v);

        return back()->with('success', 'เพิ่มรอบการประเมินเรียบร้อย');
    }

    public function setCurrentRound(Request $request, EvaluationRound $round): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        EvaluationRound::where('is_current', true)->update(['is_current' => false]);
        $round->update(['is_current' => true]);

        return back()->with('success', 'ตั้งเป็นรอบปัจจุบันแล้ว');
    }

    public function destroyRound(Request $request, EvaluationRound $round): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $round->delete();

        return back()->with('success', 'ลบรอบการประเมินแล้ว');
    }

    public function report(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->canManage($user), 403);
        $overseer = $user->hasAnyRole(['admin', 'area_admin']);

        $rounds = EvaluationRound::orderByDesc('id')->get(['id', 'name', 'is_current']);
        $roundId = (int) ($request->input('round') ?: ($rounds->firstWhere('is_current', true)?->id ?? $rounds->first()?->id ?? 0));

        // แยกข้อมูลรายโรงเรียน: ผู้บริหารโรงเรียนเห็นเฉพาะหน่วยงานตน · เขต/admin เลือกได้ (ค่าเริ่มต้น = ทุกหน่วยงาน)
        $unitFilter = $overseer ? ($request->input('unit') ? (int) $request->input('unit') : null) : (int) $user->unit_id;

        $evals = Evaluation::where('round_id', $roundId)->whereNotNull('percent')
            ->when($unitFilter, fn ($q) => $q->where('unit_id', $unitFilter))
            ->get();
        $grades = ['ดีเด่น', 'ดีมาก', 'ดี', 'พอใช้', 'ต้องปรับปรุง'];
        $byGrade = collect($grades)->map(fn ($g) => ['grade' => $g, 'count' => $evals->where('grade', $g)->count()]);

        return Inertia::render('Core::Evaluation/Report', [
            'rounds' => $rounds,
            'selectedRound' => $roundId,
            'byGrade' => $byGrade,
            'overall' => ['count' => $evals->count(), 'avg' => $evals->count() ? round($evals->avg('percent'), 2) : null],
            'canPickSchool' => $overseer,
            'selectedUnit' => $unitFilter,
            'units' => $overseer ? Unit::orderByRaw("type='area' desc")->orderBy('name')->limit(500)->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]) : [],
        ]);
    }
}

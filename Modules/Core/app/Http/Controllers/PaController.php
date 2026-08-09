<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\PaAgreement;
use Modules\Core\Notifications\ApprovalNotification;

/**
 * ว.PA — ข้อตกลงในการพัฒนางาน
 * ครูจัดทำ → เสนอ ผอ. → ผอ.เห็นชอบ → ปลายปี ผอ.ประเมินผล
 */
class PaController extends Controller
{
    private function fiscalYear(): int
    {
        return now()->year + 543 + (now()->month >= 10 ? 1 : 0);
    }

    private function isApprover(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin', 'director', 'deputy_director', 'secretary']);
    }

    private function row(PaAgreement $a, bool $withOwner = false): array
    {
        return array_filter([
            'id' => $a->id,
            'fiscal_year' => $a->fiscal_year,
            'status' => $a->status,
            'status_label' => PaAgreement::STATUSES[$a->status] ?? $a->status,
            'challenge_issue' => $a->challenge_issue,
            'score' => $a->score !== null ? (float) $a->score : null,
            'owner' => $withOwner ? $a->user?->name : null,
        ], fn ($v) => $v !== null);
    }

    public function index(Request $request): Response
    {
        $me = $request->user();
        $mine = PaAgreement::where('user_id', $me->id)->orderByDesc('fiscal_year')->get()
            ->map(fn ($a) => $this->row($a));

        $toReview = [];
        if ($this->isApprover($me)) {
            $overseer = $me->hasAnyRole(['admin', 'area_admin']);
            $toReview = PaAgreement::with('user:id,name,unit_id')
                ->whereIn('status', ['submitted', 'approved'])
                ->whereHas('user', fn ($q) => $overseer ? $q : $q->where('unit_id', $me->unit_id))
                ->orderByDesc('submitted_at')->get()
                ->map(fn ($a) => $this->row($a, true));
        }

        return Inertia::render('Core::Pa/Index', [
            'mine' => $mine,
            'toReview' => $toReview,
            'currentFiscalYear' => $this->fiscalYear(),
            'isApprover' => $this->isApprover($me),
        ]);
    }

    /** ฟอร์มจัดทำ/แก้ไข ว.PA ของตน (ต่อปีงบประมาณ) */
    public function edit(Request $request): Response
    {
        $me = $request->user();
        $fy = (int) $request->query('year', $this->fiscalYear());
        $agreement = PaAgreement::with('tasks')->firstOrNew(['user_id' => $me->id, 'fiscal_year' => $fy]);

        $tasksByAspect = collect(PaAgreement::ASPECTS)->keys()->mapWithKeys(function ($a) use ($agreement) {
            $rows = $agreement->exists
                ? $agreement->tasks->where('aspect', $a)->map(fn ($t) => ['task' => $t->task, 'expected_outcome' => $t->expected_outcome])->values()->all()
                : [];

            return [$a => $rows ?: [['task' => '', 'expected_outcome' => '']]];
        });

        return Inertia::render('Core::Pa/Edit', [
            'fiscalYear' => $fy,
            'aspects' => PaAgreement::ASPECTS,
            'agreement' => [
                'id' => $agreement->id,
                'status' => $agreement->status ?? 'draft',
                'challenge_issue' => $agreement->challenge_issue,
                'challenge_problem' => $agreement->challenge_problem,
                'challenge_method' => $agreement->challenge_method,
                'challenge_outcome_quant' => $agreement->challenge_outcome_quant,
                'challenge_outcome_qual' => $agreement->challenge_outcome_qual,
            ],
            'tasksByAspect' => $tasksByAspect,
            'editable' => ! $agreement->exists || $agreement->status === 'draft',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $me = $request->user();
        $v = $request->validate([
            'fiscal_year' => ['required', 'integer', 'min:2400', 'max:2700'],
            'challenge_issue' => ['nullable', 'string', 'max:255'],
            'challenge_problem' => ['nullable', 'string', 'max:3000'],
            'challenge_method' => ['nullable', 'string', 'max:3000'],
            'challenge_outcome_quant' => ['nullable', 'string', 'max:2000'],
            'challenge_outcome_qual' => ['nullable', 'string', 'max:2000'],
            'tasks' => ['array'],
            'tasks.*.aspect' => ['required', 'integer', 'in:1,2,3'],
            'tasks.*.task' => ['nullable', 'string', 'max:1000'],
            'tasks.*.expected_outcome' => ['nullable', 'string', 'max:1000'],
            'submit' => ['boolean'],
        ]);

        $agreement = PaAgreement::firstOrNew(['user_id' => $me->id, 'fiscal_year' => $v['fiscal_year']]);
        abort_unless(! $agreement->exists || $agreement->status === 'draft', 403, 'ข้อตกลงนี้เสนอแล้ว แก้ไขไม่ได้');

        $agreement->fill([
            'challenge_issue' => $v['challenge_issue'] ?? null,
            'challenge_problem' => $v['challenge_problem'] ?? null,
            'challenge_method' => $v['challenge_method'] ?? null,
            'challenge_outcome_quant' => $v['challenge_outcome_quant'] ?? null,
            'challenge_outcome_qual' => $v['challenge_outcome_qual'] ?? null,
        ]);

        if (! empty($v['submit'])) {
            $agreement->status = 'submitted';
            $agreement->submitted_at = now();
        }
        $agreement->save();

        // บันทึกงานส่วนที่ 1 (ลบของเดิม + ใส่ใหม่ เฉพาะที่กรอกงาน)
        $agreement->tasks()->delete();
        $sort = 0;
        foreach ($v['tasks'] ?? [] as $t) {
            if (trim($t['task'] ?? '') === '') {
                continue;
            }
            $agreement->tasks()->create([
                'aspect' => $t['aspect'],
                'task' => $t['task'],
                'expected_outcome' => $t['expected_outcome'] ?? null,
                'sort' => $sort++,
            ]);
        }

        // เสนอ ผอ. → แจ้งเตือนผู้บริหารในหน่วยงาน
        if (! empty($v['submit'])) {
            $boss = User::where('unit_id', $me->unit_id)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['executive']))
                ->where('id', '!=', $me->id)->first();
            $boss?->notify(new ApprovalNotification(
                'ข้อตกลง ว.PA รอเห็นชอบ',
                $me->name.' เสนอข้อตกลงพัฒนางาน ปีงบ '.$v['fiscal_year'],
                route('pa.show', $agreement->id), 'info', 'pa:'.$agreement->id,
            ));
        }

        return redirect()->route('pa.index')->with('success', empty($v['submit']) ? 'บันทึกร่างเรียบร้อย' : 'เสนอข้อตกลงให้ ผอ. แล้ว');
    }

    public function show(Request $request, PaAgreement $pa): Response
    {
        $me = $request->user();
        abort_unless($pa->user_id === $me->id || $this->canReview($me, $pa), 403);
        $pa->load(['tasks', 'user:id,name', 'approver:id,name']);
        $pa->load('user.position:id,name');

        return Inertia::render('Core::Pa/Show', [
            'aspects' => PaAgreement::ASPECTS,
            'agreement' => array_merge($this->row($pa, true), [
                'position' => $pa->user->position?->name,
                'challenge_problem' => $pa->challenge_problem,
                'challenge_method' => $pa->challenge_method,
                'challenge_outcome_quant' => $pa->challenge_outcome_quant,
                'challenge_outcome_qual' => $pa->challenge_outcome_qual,
                'approver' => $pa->approver?->name,
                'approver_note' => $pa->approver_note,
            ]),
            'tasks' => $pa->tasks->map(fn ($t) => ['aspect' => $t->aspect, 'task' => $t->task, 'expected_outcome' => $t->expected_outcome]),
            'canReview' => $this->canReview($me, $pa),
            'isOwner' => $pa->user_id === $me->id,
        ]);
    }

    /** ผอ.เห็นชอบข้อตกลง */
    public function approve(Request $request, PaAgreement $pa): RedirectResponse
    {
        abort_unless($this->canReview($request->user(), $pa) && $pa->status === 'submitted', 403);
        $v = $request->validate(['approver_note' => ['nullable', 'string', 'max:1000']]);

        $pa->update([
            'status' => 'approved', 'approver_id' => $request->user()->id,
            'approver_note' => $v['approver_note'] ?? null, 'approved_at' => now(),
        ]);
        $this->notifyOwner($pa, 'ข้อตกลง ว.PA ได้รับความเห็นชอบ', 'ผอ.เห็นชอบข้อตกลงพัฒนางานของคุณแล้ว');

        return back()->with('success', 'เห็นชอบข้อตกลงเรียบร้อย');
    }

    /** ปลายปี: ผอ.ประเมินผลการพัฒนางาน */
    public function evaluate(Request $request, PaAgreement $pa): RedirectResponse
    {
        abort_unless($this->canReview($request->user(), $pa) && in_array($pa->status, ['approved', 'evaluated'], true), 403);
        $v = $request->validate(['score' => ['required', 'numeric', 'min:0', 'max:100']]);

        $pa->update(['status' => 'evaluated', 'score' => $v['score'], 'evaluated_at' => now()]);
        $result = $v['score'] >= 70 ? 'ผ่าน' : 'ไม่ผ่าน';
        $this->notifyOwner($pa, 'ผลประเมิน ว.PA', "ผลการประเมิน {$v['score']} คะแนน ({$result})");

        return back()->with('success', "บันทึกผลประเมิน {$v['score']} คะแนน");
    }

    private function canReview(User $u, PaAgreement $pa): bool
    {
        if (! $this->isApprover($u)) {
            return false;
        }

        return $u->hasAnyRole(['admin', 'area_admin']) || $pa->user?->unit_id === $u->unit_id;
    }

    private function notifyOwner(PaAgreement $pa, string $title, string $msg): void
    {
        $pa->user?->notify(new ApprovalNotification($title, $msg, route('pa.show', $pa->id), 'success', 'pa:'.$pa->id));
    }
}

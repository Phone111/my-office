<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Survey;
use Modules\Core\Models\SurveyAnswer;
use Modules\Core\Models\SurveyResponse;

/**
 * ระบบแบบสอบถาม (Survey) — ระบบเขต ขั้น 6
 * ผู้บริหาร/เขตสร้างแบบสอบถาม (มาตรประมาณค่า/ตัวเลือก/ความคิดเห็น) → เปิดให้ตอบ → สรุปผล
 */
class SurveyController extends Controller
{
    private function canManage(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin', 'director', 'deputy_director', 'secretary']);
    }

    private function owns(User $u, Survey $s): bool
    {
        return $u->hasRole('admin') || $s->created_by === $u->id;
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $manage = $this->canManage($user);

        $mine = collect();
        if ($manage) {
            $q = Survey::withCount('responses')->latest();
            if (! $user->hasRole('admin')) {
                $q->where('created_by', $user->id);
            }
            $mine = $q->get()->map(fn (Survey $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'status' => $s->status,
                'status_label' => Survey::STATUSES[$s->status] ?? $s->status,
                'responses' => $s->responses_count,
                'closes_thai' => $this->thai($s->closes_at),
            ]);
        }

        // แบบสอบถามที่เปิดให้ตอบ (ทุกคน)
        $answeredIds = SurveyResponse::where('user_id', $user->id)->pluck('survey_id')->all();
        $open = Survey::where('status', Survey::STATUS_OPEN)->withCount('questions')->latest()->get()
            ->map(fn (Survey $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'description' => $s->description,
                'questions' => $s->questions_count,
                'answered' => in_array($s->id, $answeredIds, true),
                'closes_thai' => $this->thai($s->closes_at),
            ]);

        return Inertia::render('Core::Surveys/Index', [
            'mine' => $mine,
            'open' => $open,
            'canManage' => $manage,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->canManage($request->user()), 403);

        return Inertia::render('Core::Surveys/Create', [
            'types' => collect(Survey::TYPES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canManage($user), 403);

        $v = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'anonymous' => ['boolean'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'open_now' => ['boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string', 'max:500'],
            'questions.*.type' => ['required', 'in:'.implode(',', array_keys(Survey::TYPES))],
            'questions.*.required' => ['boolean'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:255'],
        ]);

        $survey = Survey::create([
            'unit_id' => $user->unit_id,
            'title' => $v['title'],
            'description' => $v['description'] ?? null,
            'anonymous' => $v['anonymous'] ?? false,
            'opens_at' => $v['opens_at'] ?? null,
            'closes_at' => $v['closes_at'] ?? null,
            'status' => ($v['open_now'] ?? false) ? Survey::STATUS_OPEN : Survey::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        foreach ($v['questions'] as $i => $q) {
            $survey->questions()->create([
                'sort' => $i,
                'text' => $q['text'],
                'type' => $q['type'],
                'required' => $q['required'] ?? true,
                'options' => $q['type'] === 'choice' ? array_values(array_filter($q['options'] ?? [], fn ($o) => trim((string) $o) !== '')) : null,
            ]);
        }

        return redirect()->route('surveys.index')->with('success', 'สร้างแบบสอบถามเรียบร้อย');
    }

    public function show(Request $request, Survey $survey): Response
    {
        $user = $request->user();
        $survey->load('questions');

        // เจ้าของ/admin → หน้าสรุปผล ; คนอื่น → หน้าตอบแบบสอบถาม
        if ($this->canManage($user) && $this->owns($user, $survey)) {
            return $this->results($survey);
        }

        $already = SurveyResponse::where('survey_id', $survey->id)->where('user_id', $user->id)->exists();

        return Inertia::render('Core::Surveys/Respond', [
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'status' => $survey->status,
                'anonymous' => $survey->anonymous,
            ],
            'questions' => $survey->questions->map(fn ($q) => [
                'id' => $q->id,
                'text' => $q->text,
                'type' => $q->type,
                'options' => $q->options ?? [],
                'required' => $q->required,
            ]),
            'already' => $already,
            'closed' => $survey->status !== Survey::STATUS_OPEN,
        ]);
    }

    private function results(Survey $survey): Response
    {
        $survey->loadCount('responses');
        $survey->load(['questions.answers']);

        $questions = $survey->questions->map(function ($q) {
            $answers = $q->answers;
            $base = ['id' => $q->id, 'text' => $q->text, 'type' => $q->type, 'count' => $answers->count()];

            if ($q->type === 'rating') {
                $vals = $answers->pluck('value')->map(fn ($v) => (int) $v)->filter(fn ($v) => $v >= 1 && $v <= 5);
                $dist = [];
                for ($i = 1; $i <= 5; $i++) {
                    $dist[$i] = $vals->filter(fn ($v) => $v === $i)->count();
                }

                return array_merge($base, ['average' => $vals->count() ? round($vals->avg(), 2) : null, 'dist' => $dist]);
            }

            if ($q->type === 'choice') {
                $opts = collect($q->options ?? [])->map(fn ($o) => ['label' => $o, 'count' => $answers->filter(fn ($a) => $a->value === $o)->count()]);

                return array_merge($base, ['options' => $opts]);
            }

            return array_merge($base, ['texts' => $answers->pluck('value')->filter()->values()]);
        });

        return Inertia::render('Core::Surveys/Results', [
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'status' => $survey->status,
                'status_label' => Survey::STATUSES[$survey->status] ?? $survey->status,
                'responses' => $survey->responses_count,
                'anonymous' => $survey->anonymous,
            ],
            'questions' => $questions,
            'typeLabels' => Survey::TYPES,
        ]);
    }

    public function submit(Request $request, Survey $survey): RedirectResponse
    {
        $user = $request->user();
        abort_unless($survey->status === Survey::STATUS_OPEN, 403, 'แบบสอบถามนี้ปิดรับคำตอบแล้ว');
        abort_if(SurveyResponse::where('survey_id', $survey->id)->where('user_id', $user->id)->exists(), 422, 'คุณตอบแบบสอบถามนี้แล้ว');

        $survey->load('questions');
        $request->validate(['answers' => ['required', 'array']]);
        $answers = $request->input('answers', []);

        // ตรวจคำถามบังคับ
        foreach ($survey->questions as $q) {
            if ($q->required && (! isset($answers[$q->id]) || trim((string) $answers[$q->id]) === '')) {
                return back()->withErrors(['answers' => 'กรุณาตอบคำถามที่จำเป็นให้ครบ']);
            }
        }

        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);

        foreach ($survey->questions as $q) {
            if (isset($answers[$q->id]) && trim((string) $answers[$q->id]) !== '') {
                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $q->id,
                    'value' => (string) $answers[$q->id],
                ]);
            }
        }

        return redirect()->route('surveys.index')->with('success', 'ส่งแบบสอบถามเรียบร้อย ขอบคุณครับ');
    }

    public function toggle(Request $request, Survey $survey): RedirectResponse
    {
        abort_unless($this->canManage($request->user()) && $this->owns($request->user(), $survey), 403);

        $next = match ($survey->status) {
            Survey::STATUS_DRAFT, Survey::STATUS_CLOSED => Survey::STATUS_OPEN,
            default => Survey::STATUS_CLOSED,
        };
        $survey->update(['status' => $next]);

        return back()->with('success', 'เปลี่ยนสถานะเป็น "'.(Survey::STATUSES[$next] ?? $next).'" แล้ว');
    }

    public function destroy(Request $request, Survey $survey): RedirectResponse
    {
        abort_unless($this->canManage($request->user()) && $this->owns($request->user(), $survey), 403);
        $survey->delete();

        return redirect()->route('surveys.index')->with('success', 'ลบแบบสอบถามแล้ว');
    }
}

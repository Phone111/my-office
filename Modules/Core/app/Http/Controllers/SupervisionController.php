<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Supervision;
use Modules\Core\Models\SupervisionRound;
use Modules\Core\Models\SupervisionStandard;
use Modules\Core\Models\Unit;
use Modules\Core\Notifications\ApprovalNotification;

/**
 * ระบบนิเทศการศึกษา — เขต (ศึกษานิเทศก์/ผู้บริหารเขต) ออกนิเทศโรงเรียนในสังกัด
 * ฝั่งเขต: วางแผน/บันทึก/สรุปผลการนิเทศ · ฝั่งโรงเรียน: ดูผล + รับทราบ/ตอบกลับ
 */
class SupervisionController extends Controller
{
    /** ฝั่งเขต = admin/area_admin หรือบุคลากรที่สังกัดสำนักงานเขต */
    private function isAreaSide(User $u): bool
    {
        if ($u->hasAnyRole(['admin', 'area_admin', 'supervisor'])) {
            return true;
        }
        $unit = Unit::find($u->unit_id);

        return $unit && $unit->type === Unit::TYPE_AREA;
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    /** แจ้งเตือนโรงเรียนเมื่อมีผลการนิเทศรอรับทราบ (กระดิ่ง) */
    private function notifySchool(Supervision $s): void
    {
        $recipients = User::where('unit_id', $s->school_unit_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['executive', 'officer']))
            ->get();

        // ถ้าไม่พบบุคลากรตามบทบาท ให้แจ้งทุกคนในโรงเรียน
        if ($recipients->isEmpty()) {
            $recipients = User::where('unit_id', $s->school_unit_id)->get();
        }

        foreach ($recipients as $r) {
            $r->notify(new ApprovalNotification(
                'ผลการนิเทศรอรับทราบ',
                'มีผลการนิเทศ "'.$s->topic.'" รอการรับทราบจากโรงเรียน',
                route('supervisions.show', $s->id),
                'info',
                'supervision-'.$s->id,
            ));
        }
    }

    /** กรอบการนิเทศ (มาตรฐาน/ตัวชี้วัด/รอบ) สำหรับฟอร์ม */
    private function frameworkData(): array
    {
        return [
            'standards' => SupervisionStandard::with('indicators')->where('is_active', true)->orderBy('sort')->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'indicators' => $s->indicators->where('is_active', true)->values()
                        ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name]),
                ]),
            'rounds' => SupervisionRound::where('is_active', true)->orderByDesc('id')->get(['id', 'name', 'is_current'])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'is_current' => $r->is_current]),
            'qualityOptions' => collect(Supervision::QUALITY)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'currentRound' => SupervisionRound::where('is_current', true)->value('id'),
        ];
    }

    /** บันทึกคะแนนรายตัวชี้วัด */
    private function saveScores(Supervision $s, array $scores): bool
    {
        $hasQuality = false;
        foreach ($scores as $indicatorId => $data) {
            $quality = isset($data['quality']) && $data['quality'] !== '' ? (int) $data['quality'] : null;
            $practiced = array_key_exists('practiced', $data) ? (bool) $data['practiced'] : null;
            if ($quality === null && $practiced === null) {
                continue;
            }
            $s->scores()->updateOrCreate(
                ['indicator_id' => (int) $indicatorId],
                ['quality' => $quality, 'practiced' => $practiced],
            );
            if ($quality !== null) {
                $hasQuality = true;
            }
        }

        return $hasQuality;
    }

    private function mapRow(Supervision $s): array
    {
        $q = $s->relationLoaded('scores') ? $s->scores->whereNotNull('quality')->pluck('quality') : collect();

        return [
            'id' => $s->id,
            'school' => $s->school?->name,
            'supervisor' => $s->supervisor?->name,
            'round' => $s->round?->name,
            'visit_date_thai' => $this->thai($s->visit_date),
            'aspect' => Supervision::ASPECTS[$s->aspect] ?? $s->aspect,
            'topic' => $s->topic,
            'rating' => $s->rating ? (Supervision::RATINGS[$s->rating] ?? $s->rating) : null,
            'quality_avg' => $q->count() ? round($q->avg(), 2) : null,
            'status' => $s->status,
            'status_label' => Supervision::STATUSES[$s->status] ?? $s->status,
            'has_files' => ! empty($s->attachments),
        ];
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $area = $this->isAreaSide($user);

        $query = Supervision::with(['school:id,name', 'supervisor:id,name', 'round:id,name', 'scores'])->latest('visit_date');
        if ($area) {
            // เขตเห็นทั้งหมดในสังกัด (ที่ตนเป็นเจ้าของ หรือยังไม่ระบุเขต)
            $query->where(fn ($q) => $q->where('area_unit_id', $user->unit_id)->orWhereNull('area_unit_id'));
        } else {
            // โรงเรียนเห็นเฉพาะการนิเทศของโรงเรียนตน
            $query->where('school_unit_id', $user->unit_id);
        }

        $rows = $query->get()->map(fn ($s) => $this->mapRow($s));

        return Inertia::render('Core::Supervision/Index', [
            'rows' => $rows,
            'isArea' => $area,
            'stats' => [
                'total' => $rows->count(),
                'planned' => $rows->where('status', Supervision::STATUS_PLANNED)->count(),
                'completed' => $rows->where('status', Supervision::STATUS_COMPLETED)->count(),
                'acknowledged' => $rows->where('status', Supervision::STATUS_ACKNOWLEDGED)->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->isAreaSide($request->user()), 403, 'เฉพาะเจ้าหน้าที่เขตเท่านั้น');

        return Inertia::render('Core::Supervision/Create', array_merge([
            'schools' => Unit::schools()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'aspects' => collect(Supervision::ASPECTS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'ratings' => collect(Supervision::RATINGS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
        ], $this->frameworkData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->isAreaSide($user), 403, 'เฉพาะเจ้าหน้าที่เขตเท่านั้น');

        $v = $request->validate([
            'school_unit_id' => ['required', 'integer', 'exists:units,id'],
            'round_id' => ['nullable', 'integer', 'exists:supervision_rounds,id'],
            'visit_date' => ['required', 'date'],
            'aspect' => ['required', 'in:'.implode(',', array_keys(Supervision::ASPECTS))],
            'topic' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'findings' => ['nullable', 'string', 'max:4000'],
            'recommendations' => ['nullable', 'string', 'max:4000'],
            'rating' => ['nullable', 'in:'.implode(',', array_keys(Supervision::RATINGS))],
            'scores' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:20480'],
        ]);

        $files = [];
        foreach ($request->file('attachments', []) as $f) {
            $files[] = ['name' => $f->getClientOriginalName(), 'path' => $f->store('supervisions', 'public')];
        }

        $sup = Supervision::create([
            'area_unit_id' => $user->unit_id,
            'school_unit_id' => $v['school_unit_id'],
            'round_id' => $v['round_id'] ?? null,
            'supervisor_id' => $user->id,
            'visit_date' => $v['visit_date'],
            'aspect' => $v['aspect'],
            'topic' => $v['topic'],
            'objective' => $v['objective'] ?? null,
            'findings' => $v['findings'] ?? null,
            'recommendations' => $v['recommendations'] ?? null,
            'rating' => $v['rating'] ?? null,
            'attachments' => $files,
            'status' => Supervision::STATUS_PLANNED,
            'created_by' => $user->id,
        ]);

        // บันทึกคะแนนรายตัวชี้วัด + ตัดสินสถานะ (มีผล = นิเทศแล้ว)
        $hasScores = $this->saveScores($sup, $request->input('scores', []));
        $completed = $hasScores || ! empty($v['findings']) || ! empty($v['rating']);
        if ($completed) {
            $sup->update(['status' => Supervision::STATUS_COMPLETED]);
        }

        if ($completed) {
            $this->notifySchool($sup);
        }

        return redirect()->route('supervisions.index')->with('success', $completed
            ? 'บันทึกผลการนิเทศและแจ้งโรงเรียนให้รับทราบแล้ว'
            : 'บันทึกแผนการนิเทศเรียบร้อย');
    }

    public function show(Request $request, Supervision $supervision): Response
    {
        $user = $request->user();
        $area = $this->isAreaSide($user);
        abort_unless($area || $supervision->school_unit_id === $user->unit_id, 403);

        $supervision->load(['school:id,name', 'supervisor:id,name', 'acknowledger:id,name', 'round:id,name', 'scores']);

        // จัดผลคะแนนเป็นกลุ่มตามมาตรฐาน → ตัวชี้วัด
        $scoreMap = $supervision->scores->keyBy('indicator_id');
        $standardResults = SupervisionStandard::with('indicators')->where('is_active', true)->orderBy('sort')->get()
            ->map(function ($std) use ($scoreMap) {
                $inds = $std->indicators->where('is_active', true)->values()->map(function ($i) use ($scoreMap) {
                    $sc = $scoreMap->get($i->id);

                    return [
                        'id' => $i->id,
                        'name' => $i->name,
                        'practiced' => $sc?->practiced,
                        'quality' => $sc?->quality,
                        'quality_label' => $sc && $sc->quality ? (Supervision::QUALITY[$sc->quality] ?? $sc->quality) : null,
                    ];
                });
                $q = $inds->pluck('quality')->filter(fn ($x) => $x !== null);

                return [
                    'id' => $std->id,
                    'name' => $std->name,
                    'avg' => $q->count() ? round($q->avg(), 2) : null,
                    'indicators' => $inds,
                ];
            })->filter(fn ($s) => count($s['indicators']) > 0)->values();

        return Inertia::render('Core::Supervision/Show', array_merge([
            'item' => array_merge($this->mapRow($supervision), [
                'visit_date_iso' => $supervision->visit_date?->format('Y-m-d'),
                'round_id' => $supervision->round_id,
                'objective' => $supervision->objective,
                'findings' => $supervision->findings,
                'recommendations' => $supervision->recommendations,
                'rating_key' => $supervision->rating,
                'aspect_key' => $supervision->aspect,
                'school_response' => $supervision->school_response,
                'acknowledger' => $supervision->acknowledger?->name,
                'acknowledged_thai' => $this->thai($supervision->acknowledged_at),
                'files' => collect($supervision->attachments ?? [])->map(fn ($a) => ['name' => $a['name'] ?? 'ไฟล์', 'url' => Storage::url($a['path'])]),
            ]),
            'standardResults' => $standardResults,
            'isArea' => $area,
            'canEdit' => $area && $supervision->status !== Supervision::STATUS_ACKNOWLEDGED,
            'canAck' => ! $area && $supervision->school_unit_id === $user->unit_id && $supervision->status === Supervision::STATUS_COMPLETED,
            'aspects' => collect(Supervision::ASPECTS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'ratings' => collect(Supervision::RATINGS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
        ], $this->frameworkData()));
    }

    /** เขตแก้ไข/สรุปผลการนิเทศ */
    public function update(Request $request, Supervision $supervision): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->isAreaSide($user) && $supervision->status !== Supervision::STATUS_ACKNOWLEDGED, 403);

        $v = $request->validate([
            'round_id' => ['nullable', 'integer', 'exists:supervision_rounds,id'],
            'visit_date' => ['required', 'date'],
            'aspect' => ['required', 'in:'.implode(',', array_keys(Supervision::ASPECTS))],
            'topic' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'findings' => ['nullable', 'string', 'max:4000'],
            'recommendations' => ['nullable', 'string', 'max:4000'],
            'rating' => ['nullable', 'in:'.implode(',', array_keys(Supervision::RATINGS))],
            'scores' => ['nullable', 'array'],
        ]);

        $wasCompleted = $supervision->status === Supervision::STATUS_COMPLETED;

        $supervision->update([
            'round_id' => $v['round_id'] ?? null,
            'visit_date' => $v['visit_date'],
            'aspect' => $v['aspect'],
            'topic' => $v['topic'],
            'objective' => $v['objective'] ?? null,
            'findings' => $v['findings'] ?? null,
            'recommendations' => $v['recommendations'] ?? null,
            'rating' => $v['rating'] ?? null,
        ]);

        $hasScores = $this->saveScores($supervision, $request->input('scores', []));
        $completed = $hasScores || ! empty($v['findings']) || ! empty($v['rating']);
        $supervision->update(['status' => $completed ? Supervision::STATUS_COMPLETED : Supervision::STATUS_PLANNED]);

        // แจ้งโรงเรียนเฉพาะตอนเพิ่งเปลี่ยนเป็น "นิเทศแล้ว" (ไม่ส่งซ้ำทุกครั้งที่แก้)
        if ($completed && ! $wasCompleted) {
            $this->notifySchool($supervision);
        }

        return back()->with('success', 'อัปเดตผลการนิเทศเรียบร้อย');
    }

    /** โรงเรียนรับทราบ + ตอบกลับการดำเนินการ */
    public function acknowledge(Request $request, Supervision $supervision): RedirectResponse
    {
        $user = $request->user();
        abort_unless($supervision->school_unit_id === $user->unit_id && $supervision->status === Supervision::STATUS_COMPLETED, 403);

        $v = $request->validate(['school_response' => ['nullable', 'string', 'max:4000']]);

        $supervision->update([
            'school_response' => $v['school_response'] ?? null,
            'status' => Supervision::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        // เคลียร์กระดิ่งของผู้กดรับทราบ
        $user->unreadNotifications->each(function ($n) use ($supervision) {
            if (($n->data['key'] ?? null) === 'supervision-'.$supervision->id) {
                $n->markAsRead();
            }
        });

        return back()->with('success', 'รับทราบผลการนิเทศเรียบร้อย');
    }

    public function destroy(Request $request, Supervision $supervision): RedirectResponse
    {
        abort_unless($this->isAreaSide($request->user()), 403);

        // ลบกระดิ่งที่ชี้ไปรายการนี้ทั้งหมด (กันลิงก์เสีย 404)
        \Illuminate\Notifications\DatabaseNotification::whereJsonContains('data->key', 'supervision-'.$supervision->id)->delete();

        $supervision->delete();

        return redirect()->route('supervisions.index')->with('success', 'ลบรายการนิเทศแล้ว');
    }
}

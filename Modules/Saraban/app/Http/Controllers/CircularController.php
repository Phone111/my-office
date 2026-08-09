<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Department;
use Modules\Core\Models\Group;
use Modules\Core\Notifications\ApprovalNotification;
use Modules\Saraban\Models\Circular;
use Modules\Saraban\Models\Document;

/**
 * ส่งหนังสือราชการภายใน — broadcast ถึงผู้รับรายบุคคล (จัดกลุ่มตามกลุ่มงาน/กลุ่มสาระ)
 */
class CircularController extends Controller
{
    /** รายการหนังสือที่ฉันส่ง */
    public function index(Request $request): Response
    {
        $circulars = Circular::with('senderGroup:id,name')
            ->where('sender_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Circular $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'sender_group' => $c->senderGroup?->name,
                'recipients' => count($c->target_users ?? []),
                'attachments' => count($c->attachments ?? []),
                'created_at' => $c->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Saraban::Circulars/Index', [
            'circulars' => $circulars,
        ]);
    }

    /** แฟ้มหนังสือเวียน (ฝั่งผู้รับ) — หนังสือที่ส่งถึงฉัน */
    public function inbox(Request $request): Response
    {
        $userId = $request->user()->id;

        $circulars = Circular::with(['sender:id,name', 'senderGroup:id,name'])
            ->whereJsonContains('target_users', $userId)
            ->latest()
            ->get()
            // ที่จัดเก็บแล้วให้ย้ายออกจากแฟ้ม (ไปอยู่ในแฟ้มบันทึกข้อความแทน)
            ->reject(fn (Circular $c) => in_array($userId, $c->filed_by ?? [], true))
            ->map(fn (Circular $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'sender' => $c->sender?->name,
                'is_read' => in_array($userId, $c->read_by ?? [], true),
                'is_filed' => false,
                'created_at' => $c->created_at->format('d/m/Y H:i'),
            ])
            ->values();

        return Inertia::render('Saraban::Circulars/Inbox', [
            'circulars' => $circulars,
        ]);
    }

    /** จัดเก็บ (ลงทะเบียน) หนังสือเวียน — เฉพาะผู้รับ */
    public function file(Request $request, Circular $circular): RedirectResponse
    {
        $userId = $request->user()->id;
        abort_unless(in_array($userId, $circular->target_users ?? [], true), 403);

        // จัดเก็บ = ถือว่ารับทราบ/อ่านแล้วด้วย (ตัดออกจากป้ายยังไม่อ่าน)
        $readBy = $circular->read_by ?? [];
        if (! in_array($userId, $readBy, true)) {
            $readBy[] = $userId;
            $circular->update(['read_by' => array_values(array_unique($readBy))]);
        }

        $filedBy = $circular->filed_by ?? [];
        if (! in_array($userId, $filedBy, true)) {
            $filedBy[] = $userId;
            $circular->update(['filed_by' => array_values(array_unique($filedBy))]);

            // คัดลอกเข้าแฟ้มเอกสารของผู้รับ — หมวด "รับหนังสือภายใน" (หนังสือเวียน = หนังสือภายในที่ได้รับ)
            Document::create([
                'category' => Document::CATEGORY_INTERNAL_IN,
                'title' => $circular->title,
                'content' => $circular->content,
                'priority' => 'normal',
                'is_urgent' => false,
                'status' => Document::STATUS_APPROVED,
                'filing' => 'จัดเก็บจากหนังสือเวียน',
                'creator_id' => $userId,
                'source_name' => $circular->sender?->name,
                'source_date' => $circular->created_at,
                'attachments' => $circular->attachments ?? [],
            ]);
        }

        $this->clearCircularNotification($request->user(), $circular->id);

        return back()->with('success', 'จัดเก็บหนังสือเวียนเข้าแฟ้มรับหนังสือภายในเรียบร้อยแล้ว');
    }

    /** รายละเอียดหนังสือเวียน (ผู้รับเปิดอ่าน = ทำเครื่องหมายอ่านแล้ว) */
    public function show(Request $request, Circular $circular): Response
    {
        $userId = $request->user()->id;
        $targets = $circular->target_users ?? [];

        abort_unless(
            in_array($userId, $targets, true) || $circular->sender_id === $userId || $request->user()->hasRole('admin'),
            403,
        );

        // ทำเครื่องหมายว่าอ่านแล้ว (เฉพาะผู้รับ) + เคลียร์แจ้งเตือนกระดิ่ง
        if (in_array($userId, $targets, true)) {
            $readBy = $circular->read_by ?? [];
            if (! in_array($userId, $readBy, true)) {
                $readBy[] = $userId;
                $circular->update(['read_by' => array_values(array_unique($readBy))]);
            }
            $this->clearCircularNotification($request->user(), $circular->id);
        }

        $circular->load(['sender:id,name', 'senderGroup:id,name']);
        $readBy = $circular->read_by ?? [];
        $responses = $circular->responses ?? [];
        $recipients = User::whereIn('id', $targets)->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $u) => [
                'name' => $u->name,
                'is_read' => in_array($u->id, $readBy, true),
                'rsvp' => $responses[$u->id]['status'] ?? null,
                'rsvp_note' => $responses[$u->id]['note'] ?? null,
            ]);

        // สรุปจำนวนการตอบรับ (เฉพาะหนังสือเชิญประชุม)
        $rsvpCounts = collect(array_keys(Circular::RSVP))
            ->mapWithKeys(fn ($k) => [$k => $recipients->where('rsvp', $k)->count()]);

        return Inertia::render('Saraban::Circulars/Show', [
            'circular' => [
                'id' => $circular->id,
                'title' => $circular->title,
                'content' => $circular->content,
                'sender' => $circular->sender?->name,
                'sender_group' => $circular->senderGroup?->name,
                'created_at' => $circular->created_at->format('d/m/Y H:i'),
                'is_meeting' => $circular->is_meeting,
                'meeting_at' => $circular->meeting_at?->format('d/m/Y H:i'),
                'meeting_place' => $circular->meeting_place,
                'can_respond' => in_array($userId, $targets, true),
                'my_rsvp' => $responses[$userId] ?? null,
                'rsvp_labels' => Circular::RSVP,
                'rsvp_counts' => $rsvpCounts,
                'no_reply_count' => $recipients->whereNull('rsvp')->count(),
                'attachments' => collect($circular->attachments ?? [])->map(fn ($p, $i) => [
                    'name' => 'ไฟล์แนบ '.($i + 1),
                    'url' => asset('storage/'.$p),
                ])->values(),
                'recipients' => $recipients,
                'read_count' => $recipients->where('is_read', true)->count(),
                'total' => $recipients->count(),
            ],
        ]);
    }

    /** ตอบรับเข้าประชุม (เฉพาะผู้รับหนังสือเชิญประชุม) */
    public function respond(Request $request, Circular $circular): RedirectResponse
    {
        $userId = $request->user()->id;
        abort_unless($circular->is_meeting, 404);
        abort_unless(in_array($userId, $circular->target_users ?? [], true), 403, 'เฉพาะผู้รับหนังสือเท่านั้นที่ตอบรับได้');

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Circular::RSVP))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $responses = $circular->responses ?? [];
        $responses[$userId] = [
            'status' => $validated['status'],
            'note' => $validated['note'] ?? null,
            'at' => now()->toDateTimeString(),
        ];

        // ตอบรับ = ถือว่าอ่านแล้วด้วย
        $readBy = $circular->read_by ?? [];
        if (! in_array($userId, $readBy, true)) {
            $readBy[] = $userId;
        }

        $circular->update([
            'responses' => $responses,
            'read_by' => array_values(array_unique($readBy)),
        ]);
        $this->clearCircularNotification($request->user(), $circular->id);

        return back()->with('success', 'บันทึกการตอบรับ "'.Circular::RSVP[$validated['status']].'" เรียบร้อยแล้ว');
    }

    /** ฟอร์มเขียนหนังสือ — แนบรายชื่อผู้รับจัดกลุ่มตามกลุ่มงาน/กลุ่มสาระ */
    public function create(Request $request): Response
    {
        // หนังสือเวียนภายในหน่วยงานตน (admin/area_admin เวียนได้ทุกหน่วยงาน)
        $users = User::orderBy('name')
            ->when(! $request->user()->hasAnyRole(['admin', 'area_admin']), fn ($q) => $q->where('unit_id', $request->user()->unit_id))
            ->get(['id', 'name', 'group_id', 'department_id']);

        // ส่งหนังสือเวียนต่อ — ดึงเรื่อง/รายละเอียด/ไฟล์แนบจากหนังสือเวียนต้นทางมาตั้งต้น
        $prefill = null;
        if ($fromId = $request->integer('from')) {
            $source = Circular::find($fromId);
            if ($source && $this->canViewCircular($request->user(), $source)) {
                $prefill = [
                    'title' => $source->title,
                    'content' => $source->content,
                    'carry_from' => $source->id,
                    'attachments' => collect($source->attachments ?? [])
                        ->map(fn ($p, $i) => 'ไฟล์แนบ '.($i + 1))
                        ->values(),
                ];
            }
        }

        return Inertia::render('Saraban::Circulars/Create', [
            'groups' => Group::orderBy('level')->orderBy('name')->get(['id', 'name']),
            'byGroup' => $this->bucketsBy($users, Group::orderBy('level')->orderBy('name')->get(['id', 'name']), 'group_id'),
            'byDepartment' => $this->bucketsBy($users, Department::orderBy('sort_order')->get(['id', 'name']), 'department_id'),
            'prefill' => $prefill,
        ]);
    }

    /** ตรวจสิทธิ์ดูหนังสือเวียน (ผู้ส่ง / ผู้รับ / admin) */
    private function canViewCircular(User $user, Circular $circular): bool
    {
        return $circular->sender_id === $user->id
            || in_array($user->id, $circular->target_users ?? [], true)
            || $user->hasRole('admin');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sender_group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'target_users' => ['required', 'array', 'min:1'],
            'target_users.*' => ['integer', 'exists:users,id'],
            'files' => ['array', 'max:4'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
            'carry_from' => ['nullable', 'integer', 'exists:circulars,id'], // ส่งต่อจากหนังสือเวียนเดิม
            'is_meeting' => ['boolean'],
            'meeting_at' => ['nullable', 'required_if:is_meeting,true', 'date'],
            'meeting_place' => ['nullable', 'string', 'max:255'],
        ], [
            'meeting_at.required_if' => 'กรุณาระบุวันเวลาประชุม',
            'target_users.required' => 'กรุณาเลือกผู้รับอย่างน้อย 1 คน',
            'target_users.min' => 'กรุณาเลือกผู้รับอย่างน้อย 1 คน',
        ]);

        $paths = [];

        // พกไฟล์แนบจากหนังสือเวียนต้นทาง (กรณีส่งต่อ) มาก่อน
        if (! empty($validated['carry_from'])) {
            $source = Circular::find($validated['carry_from']);
            if ($source && $this->canViewCircular($request->user(), $source)) {
                $paths = array_values($source->attachments ?? []);
            }
        }

        foreach ((array) $request->file('files', []) as $file) {
            if ($file) {
                $paths[] = $file->store('circulars', 'public');
            }
        }

        $targets = array_values(array_unique(array_map('intval', $validated['target_users'] ?? [])));

        $circular = Circular::create([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'sender_id' => $request->user()->id,
            'sender_group_id' => $validated['sender_group_id'] ?? null,
            'target_users' => $targets,
            'attachments' => $paths,
            'is_meeting' => $validated['is_meeting'] ?? false,
            'meeting_at' => ! empty($validated['is_meeting']) ? ($validated['meeting_at'] ?? null) : null,
            'meeting_place' => ! empty($validated['is_meeting']) ? ($validated['meeting_place'] ?? null) : null,
        ]);

        // บันทึกสำเนาเข้า "แฟ้มส่งหนังสือภายใน" ของผู้ส่ง (เก็บเป็นหลักฐาน)
        Document::create([
            'category' => Document::CATEGORY_INTERNAL_OUT,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'priority' => 'normal',
            'is_urgent' => false,
            'status' => Document::STATUS_APPROVED,
            'filing' => 'จัดเก็บจากการส่งหนังสือเวียน',
            'creator_id' => $request->user()->id,
            'attachments' => $paths,
        ]);

        // แจ้งเตือนผู้รับทุกคน (กระดิ่ง)
        User::whereIn('id', $targets)->get()->each(fn (User $u) => $u->notify(new ApprovalNotification(
            'หนังสือเวียนเข้าใหม่',
            'มีหนังสือเวียน "'.$validated['title'].'" ส่งถึงคุณ',
            route('saraban.circulars.show', $circular->id),
            'info',
            'circular:'.$circular->id,
        )));

        $verb = ! empty($validated['carry_from']) ? 'ส่งหนังสือเวียนต่อ' : 'ส่งหนังสือราชการภายใน';

        return redirect()
            ->route('saraban.circulars.index')
            ->with('success', $verb.'ให้บุคลากร '.count($targets).' คนเรียบร้อยแล้ว');
    }

    /** ทำเครื่องหมายอ่านแล้วให้แจ้งเตือนกระดิ่งของหนังสือเวียนนี้ */
    private function clearCircularNotification(User $user, int $circularId): void
    {
        $user->unreadNotifications->each(function ($n) use ($circularId) {
            if (($n->data['key'] ?? null) === 'circular:'.$circularId) {
                $n->markAsRead();
            }
        });
    }

    /**
     * จัดผู้ใช้เป็นถัง (bucket) ตามกลุ่ม/สังกัด + ถัง "ไม่ระบุ" สำหรับผู้ไม่มีสังกัด
     *
     * @return array<int, array{id: int|string, name: string, users: array}>
     */
    private function bucketsBy(Collection $users, Collection $owners, string $key): array
    {
        $buckets = $owners->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'users' => $users->where($key, $o->id)
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                ->values(),
        ])->filter(fn ($b) => count($b['users']) > 0)->values();

        $unassigned = $users->whereNull($key)
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values();
        if ($unassigned->isNotEmpty()) {
            $buckets->push(['id' => 'none', 'name' => 'ไม่ระบุสังกัด', 'users' => $unassigned]);
        }

        return $buckets->all();
    }
}

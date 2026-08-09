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
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\PersonalDocument;

/**
 * ส่งเอกสารส่วนตัว — ส่งเอกสารถึงบุคคลโดยตรง (ไม่ผ่านเส้นทางอนุมัติ)
 */
class PersonalDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $map = fn (PersonalDocument $d) => [
            'id' => $d->id,
            'title' => $d->title,
            'content' => $d->content,
            'sender' => $d->sender?->name,
            'recipient' => $d->recipient?->name,
            'attachments' => collect($d->attachments ?? [])
                ->map(fn ($p, $i) => ['name' => 'ไฟล์แนบ '.($i + 1), 'url' => asset('storage/'.$p)])
                ->when($d->file_path, fn ($c) => $c->push(['name' => 'ไฟล์แนบ', 'url' => asset('storage/'.$d->file_path)]))
                ->values(),
            'is_read' => $d->read_at !== null,
            'created_at' => $d->created_at->format('d/m/Y H:i'),
        ];

        // กล่องรับ = ที่ยังไม่จัดเก็บ (จัดเก็บแล้วย้ายไปแฟ้มรับเอกสารทั่วไป)
        $received = PersonalDocument::with(['sender:id,name'])
            ->where('recipient_id', $userId)->whereNull('filed_at')->latest()->get()->map($map);

        $sent = PersonalDocument::with(['recipient:id,name'])
            ->where('sender_id', $userId)->latest()->get()->map($map);

        return Inertia::render('Saraban::PersonalDocuments', [
            'received' => $received,
            'sent' => $sent,
            'unreadCount' => $received->where('is_read', false)->count(),
        ]);
    }

    /**
     * หน้าฟอร์มส่งเอกสารส่วนตัว (หน้าเต็ม)
     */
    public function create(Request $request): Response
    {
        // ส่งได้เฉพาะบุคลากรในหน่วยงานตน (admin/area_admin ส่งได้ทุกหน่วยงาน)
        $users = User::orderBy('name')
            ->when(! $request->user()->hasAnyRole(['admin', 'area_admin']), fn ($q) => $q->where('unit_id', $request->user()->unit_id))
            ->get(['id', 'name', 'group_id', 'department_id']);

        return Inertia::render('Saraban::PersonalDocuments/Create', [
            'byGroup' => $this->bucketsBy($users, Group::orderBy('level')->orderBy('name')->get(['id', 'name']), 'group_id'),
            'byDepartment' => $this->bucketsBy($users, Department::orderBy('sort_order')->get(['id', 'name']), 'department_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'target_users' => ['required', 'array', 'min:1'],
            'target_users.*' => ['integer', 'exists:users,id'],
            'files' => ['array', 'max:4'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        // อัปโหลดไฟล์แนบครั้งเดียว ใช้ร่วมกันทุกผู้รับ
        $paths = [];
        foreach ((array) $request->file('files', []) as $file) {
            if ($file) {
                $paths[] = $file->store('personal-docs', 'public');
            }
        }

        $targets = array_values(array_unique(array_map('intval', $data['target_users'])));
        $senderId = $request->user()->id;

        $createdIds = [];
        foreach ($targets as $rid) {
            $doc = PersonalDocument::create([
                'sender_id' => $senderId,
                'recipient_id' => $rid,
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'attachments' => $paths,
            ]);
            $createdIds[$rid] = $doc->id;
        }

        // บันทึกสำเนาเข้า "แฟ้มส่งเอกสารทั่วไป" ของผู้ส่ง (เก็บเป็นหลักฐาน)
        Document::create([
            'category' => Document::CATEGORY_GENERAL_OUT,
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'priority' => 'normal',
            'is_urgent' => false,
            'status' => Document::STATUS_APPROVED,
            'filing' => 'จัดเก็บจากการส่งเอกสารส่วนตัว',
            'creator_id' => $senderId,
            'attachments' => $paths,
        ]);

        // แจ้งเตือนผู้รับ (กระดิ่ง) — ผูก key เอกสารของผู้รับแต่ละคน เพื่อเคลียร์อัตโนมัติเมื่ออ่าน/จัดเก็บ
        foreach ($createdIds as $rid => $docId) {
            User::find($rid)?->notify(new ApprovalNotification(
                'เอกสารทั่วไปเข้าใหม่',
                'มีเอกสาร "'.$data['title'].'" ส่งถึงคุณ',
                route('saraban.personal-documents.index'),
                'info',
                'personal:'.$docId,
            ));
        }

        return redirect()
            ->route('saraban.personal-documents.index')
            ->with('success', 'ส่งเอกสารถึง '.count($targets).' คนเรียบร้อยแล้ว');
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว (เฉพาะผู้รับ)
     */
    public function markRead(Request $request, PersonalDocument $personalDocument): RedirectResponse
    {
        abort_unless($personalDocument->recipient_id === $request->user()->id, 403);

        if ($personalDocument->read_at === null) {
            $personalDocument->update(['read_at' => now()]);
        }

        $this->clearPersonalNotification($request->user(), $personalDocument->id);

        return back();
    }

    /**
     * จัดเก็บเข้าแฟ้ม — ย้ายเข้า "แฟ้มรับเอกสารทั่วไป" (เฉพาะผู้รับ)
     */
    public function file(Request $request, PersonalDocument $personalDocument): RedirectResponse
    {
        abort_unless($personalDocument->recipient_id === $request->user()->id, 403);

        if ($personalDocument->filed_at === null) {
            $personalDocument->update([
                'filed_at' => now(),
                'read_at' => $personalDocument->read_at ?? now(),
            ]);

            $personalDocument->loadMissing('sender:id,name');

            Document::create([
                'category' => Document::CATEGORY_GENERAL_IN,
                'title' => $personalDocument->title,
                'content' => $personalDocument->content,
                'priority' => 'normal',
                'is_urgent' => false,
                'status' => Document::STATUS_APPROVED,
                'filing' => 'จัดเก็บจากเอกสารส่วนตัว',
                'creator_id' => $request->user()->id,
                'source_name' => $personalDocument->sender?->name,
                'source_date' => $personalDocument->created_at,
                'attachments' => $personalDocument->attachments ?? [],
            ]);
        }

        $this->clearPersonalNotification($request->user(), $personalDocument->id);

        return back()->with('success', 'จัดเก็บเข้าแฟ้มรับเอกสารทั่วไปเรียบร้อยแล้ว');
    }

    /** ทำเครื่องหมายอ่านแล้วให้แจ้งเตือนกระดิ่งของเอกสารนี้ */
    private function clearPersonalNotification(\App\Models\User $user, int $id): void
    {
        $user->unreadNotifications->each(function ($n) use ($id) {
            if (($n->data['key'] ?? null) === 'personal:'.$id) {
                $n->markAsRead();
            }
        });
    }

    /**
     * จัดผู้ใช้เป็นถัง (bucket) ตามกลุ่ม/สังกัด + ถัง "ไม่ระบุ"
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

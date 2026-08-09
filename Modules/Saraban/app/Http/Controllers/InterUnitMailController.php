<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Group;
use Modules\Core\Models\Unit;
use Modules\Core\Notifications\ApprovalNotification;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\InterUnitMail;
use Modules\Saraban\Models\SchoolGroup;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * รับส่งหนังสือราชการระหว่างหน่วยงาน (ระบบเขต Phase 2)
 * เขต ↔ โรงเรียน / โรงเรียน ↔ โรงเรียน — routing สารบรรณ → บุคคลในหน่วยงาน
 */
class InterUnitMailController extends Controller
{
    private function myUnit(Request $request): ?int
    {
        return $request->user()->unit_id;
    }

    /** หน่วยงานแบบเขต (มีกลุ่มงาน → ใช้เส้นทาง 2 ขั้น สารบรรณกลาง→กลุ่ม→บุคคล) */
    private function isAreaUnit(?int $unitId): bool
    {
        return $unitId && Unit::where('id', $unitId)->where('type', Unit::TYPE_AREA)->exists();
    }

    /** แจ้งเตือนผู้ใช้หลายคน (กระดิ่ง) */
    private function notifyUsers($users, string $title, string $message, InterUnitMail $mail): void
    {
        foreach ($users as $u) {
            if ($u) {
                $u->notify(new ApprovalNotification(
                    title: $title,
                    message: $message,
                    url: route('saraban.area-mail.show', $mail->id),
                    type: $mail->priority === 'normal' ? 'info' : 'warning',
                    key: 'iumail:'.$mail->id.':'.$title,
                ));
            }
        }
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    private function mapRow(InterUnitMail $m): array
    {
        return [
            'id' => $m->id,
            'tracking_no' => $m->tracking_no,
            'number' => $m->number,
            'doc_date_thai' => $this->thai($m->doc_date),
            'subject' => $m->subject,
            'from' => $m->fromUnit?->name,
            'to' => $m->toUnit?->name,
            'priority' => InterUnitMail::PRIORITIES[$m->priority] ?? $m->priority,
            'priority_key' => $m->priority,
            'confidential' => $m->confidential,
            'status' => $m->status,
            'receive_number' => $m->receive_number,
            'received_thai' => $this->thai($m->received_at),
            'assignee' => $m->assignee?->name,
            'to_group' => $m->toGroup?->name,
            'sender' => $m->sender?->name,
            'has_files' => ! empty($m->attachments),
        ];
    }

    /** ฟอร์มส่งหนังสือราชการ */
    public function compose(Request $request): Response
    {
        $myUnit = $this->myUnit($request);
        $units = Unit::where('is_active', true)
            ->where('id', '!=', $myUnit)
            ->orderByRaw("type = 'area' desc")->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Unit $u) => ['id' => $u->id, 'name' => $u->name, 'type' => $u->type]);

        $unit = Unit::find($myUnit);

        // AMSS ส่วน 16: ปุ่ม "ส่ง ร.ร." จากทะเบียนหนังสือส่ง — ถ่ายทอด เลขที่/ลงวันที่/เรื่อง/ไฟล์ มาที่ฟอร์มส่ง
        $prefill = null;
        if ($oid = $request->query('from_outgoing')) {
            $doc = $this->ownedOutgoing((int) $oid, $request->user()->id);
            if ($doc) {
                $prefill = [
                    'source_outgoing_id' => $doc->id,
                    'number' => $doc->document_number,
                    'doc_date' => optional($doc->source_date)->format('Y-m-d'),
                    'subject' => $doc->title,
                    'files' => array_map(fn ($f) => $f['name'], $this->outgoingFiles($doc)),
                ];
            }
        }

        // กลุ่มโรงเรียนของเขตนี้ (ปุ่มเลือกทั้งกลุ่มทีเดียว — AMSS "โรงเรียนในฝัน" ฯลฯ)
        $schoolGroups = SchoolGroup::where('unit_id', $myUnit)->where('is_active', true)
            ->with('members:id')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolGroup $g) => ['id' => $g->id, 'name' => $g->name, 'unit_ids' => $g->members->pluck('id')])
            ->filter(fn ($g) => $g['unit_ids']->isNotEmpty())
            ->values();

        return Inertia::render('Saraban::AreaMail/Compose', [
            'units' => $units,
            'myUnit' => $unit?->name,
            'bookPrefix' => $unit?->book_prefix,
            'priorities' => collect(InterUnitMail::PRIORITIES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'prefill' => $prefill,
            'schoolGroups' => $schoolGroups,
        ]);
    }

    /** หนังสือส่งของผู้ใช้ (สำหรับถ่ายทอดเข้าระบบส่ง) */
    private function ownedOutgoing(int $id, int $userId): ?Document
    {
        return Document::where('id', $id)
            ->where('category', Document::CATEGORY_OUTGOING)
            ->where('creator_id', $userId)
            ->first();
    }

    /** ไฟล์แนบของหนังสือส่ง → รูปแบบ {name, path} ของ InterUnitMail */
    private function outgoingFiles(Document $doc): array
    {
        $files = [];
        if ($doc->file_path) {
            $files[] = ['name' => 'หนังสือนำ ('.$doc->document_number.')', 'path' => $doc->file_path];
        }
        foreach ($doc->attachments ?? [] as $i => $p) {
            $files[] = ['name' => 'เอกสารแนบ '.($i + 1), 'path' => is_array($p) ? ($p['path'] ?? '') : $p];
        }

        return array_values(array_filter($files, fn ($f) => $f['path'] !== ''));
    }

    public function store(Request $request): RedirectResponse
    {
        $from = $this->myUnit($request);
        abort_unless($from, 403, 'บัญชีของคุณยังไม่ได้สังกัดหน่วยงาน');

        $v = $request->validate([
            'to_unit_ids' => ['required', 'array', 'min:1'],
            'to_unit_ids.*' => ['integer', 'exists:units,id'],
            'number' => ['nullable', 'string', 'max:100'],
            'auto_number' => ['boolean'],
            'doc_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:4000'],
            'reference' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:'.implode(',', array_keys(InterUnitMail::PRIORITIES))],
            'confidential' => ['boolean'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:20480'],
            'source_outgoing_id' => ['nullable', 'integer', 'exists:documents,id'],
        ]);

        $files = [];
        foreach ($request->file('attachments', []) as $f) {
            $files[] = ['name' => $f->getClientOriginalName(), 'path' => $f->store('inter-unit-mail', 'public')];
        }

        // ถ่ายทอดไฟล์จากทะเบียนหนังสือส่ง (ปุ่ม "ส่ง ร.ร.") — อ้างอิง path เดิม
        if (! empty($v['source_outgoing_id'])) {
            $src = $this->ownedOutgoing((int) $v['source_outgoing_id'], $request->user()->id);
            if ($src) {
                $files = array_merge($files, $this->outgoingFiles($src));
            }
        }

        // ต้องมีปลายทางอย่างน้อย 1 แห่งที่ไม่ใช่หน่วยงานตน (กันออกเลขทิ้ง)
        $targets = array_values(array_filter(array_unique($v['to_unit_ids']), fn ($to) => (int) $to !== (int) $from));
        abort_if(empty($targets), 422, 'กรุณาเลือกหน่วยงานปลายทางอย่างน้อย 1 แห่ง (ไม่นับหน่วยงานของท่าน)');

        // ออกเลขที่หนังสือส่งอัตโนมัติ (ต่อหน่วยงาน/ปี) — เลขเดียวกันทุกผู้รับในการส่งครั้งนี้
        $number = $v['number'] ?? null;
        $seq = null;
        if (($v['auto_number'] ?? false) || ! $number) {
            $fromUnit = Unit::find($from);
            $prefix = $fromUnit?->book_prefix ?: 'ที่';
            $seq = app(NumberRegisterService::class)->nextScoped(
                "iu_send:{$from}",
                (int) now()->year,
                fn () => (int) (InterUnitMail::where('from_unit_id', $from)->whereYear('created_at', now()->year)->max('send_seq') ?? 0),
            );
            $number = $prefix.'/'.$seq;
        }

        $count = 0;
        foreach ($targets as $to) {
            $mail = InterUnitMail::create([
                'from_unit_id' => $from,
                'to_unit_id' => $to,
                'sender_id' => $request->user()->id,
                'number' => $number,
                'send_seq' => $seq,
                'doc_date' => $v['doc_date'],
                'subject' => $v['subject'],
                'detail' => $v['detail'] ?? null,
                'reference' => $v['reference'] ?? null,
                'priority' => $v['priority'],
                'confidential' => $v['confidential'] ?? false,
                'attachments' => $files,
                'status' => InterUnitMail::STATUS_SENT,
            ]);
            // เลขติดตาม (แบบไปรษณีย์) ต่อปลายทาง — RB + ปี + id
            $mail->update(['tracking_no' => 'RB'.now()->format('y').str_pad((string) $mail->id, 6, '0')]);

            // แจ้งเตือนสารบรรณหน่วยงานปลายทางว่ามีหนังสือรอลงทะเบียนรับ (AMSS: เด้งเตือนตอน login)
            $clerks = User::where('unit_id', $to)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['saraban', 'school_clerk']))
                ->get();
            $this->notifyUsers($clerks, 'หนังสือราชการใหม่ รอลงทะเบียนรับ', 'จาก '.(Unit::find($from)?->name ?? '').' เรื่อง "'.$mail->subject.'"', $mail);
            $count++;
        }

        return redirect()->route('saraban.area-mail.outbox')->with('success', "ส่งหนังสือราชการไปยัง {$count} หน่วยงานแล้ว");
    }

    /** ทะเบียนหนังสือส่ง (ออกจากหน่วยงานเรา) */
    public function outbox(Request $request): Response
    {
        $rows = InterUnitMail::with(['toUnit:id,name', 'toGroup:id,name', 'sender:id,name', 'fromUnit:id,name'])
            ->where('from_unit_id', $this->myUnit($request))
            ->latest()->get()->map(fn ($m) => $this->mapRow($m));

        return Inertia::render('Saraban::AreaMail/Outbox', [
            'rows' => $rows,
            'myUnit' => Unit::find($this->myUnit($request))?->name,
        ]);
    }

    /** หนังสือรับ — ส่งถึงหน่วยงานเรา (รอลงทะเบียน + ลงแล้ว) */
    public function inbox(Request $request): Response
    {
        $mine = $this->myUnit($request);
        $base = InterUnitMail::with(['fromUnit:id,name', 'toGroup:id,name', 'sender:id,name', 'assignee:id,name'])->where('to_unit_id', $mine);

        $pending = (clone $base)->where('status', InterUnitMail::STATUS_SENT)->latest()->get()->map(fn ($m) => $this->mapRow($m));
        $received = (clone $base)->whereIn('status', [InterUnitMail::STATUS_RECEIVED, InterUnitMail::STATUS_ASSIGNED_GROUP, InterUnitMail::STATUS_FORWARDED])->latest('received_at')->get()->map(fn ($m) => $this->mapRow($m));

        return Inertia::render('Saraban::AreaMail/Inbox', [
            'pending' => $pending,
            'received' => $received,
            'myUnit' => Unit::find($mine)?->name,
        ]);
    }

    /** ลงทะเบียนรับ — ออกเลขทะเบียนรับ */
    public function receive(Request $request, InterUnitMail $mail): RedirectResponse
    {
        abort_unless($mail->to_unit_id === $this->myUnit($request) && $mail->status === InterUnitMail::STATUS_SENT, 403);
        abort_unless($request->user()->hasAnyRole(['saraban', 'secretary', 'school_clerk', 'admin']), 403);

        $be = now()->year + 543;
        $seq = app(NumberRegisterService::class)->nextScoped(
            "iu_recv:{$mail->to_unit_id}",
            (int) now()->year,
            fn () => (int) InterUnitMail::where('to_unit_id', $mail->to_unit_id)->whereNotNull('receive_number')->whereYear('received_at', now()->year)->count(),
        );

        $mail->update([
            'status' => InterUnitMail::STATUS_RECEIVED,
            'receive_number' => $seq.'/'.$be,
            'received_at' => now(),
            'received_by' => $request->user()->id,
        ]);

        return back()->with('success', 'ลงทะเบียนรับเรียบร้อย — เลขรับ '.$seq.'/'.$be);
    }

    /**
     * สารบรรณกลางมอบกลุ่มงาน (เฉพาะหน่วยงานแบบเขต) — AMSS ส่วน 16 hop ที่ 2
     * สารบรรณกลาง → มอบกลุ่ม → สารบรรณกลุ่มรับไปดำเนินการต่อ
     */
    public function assignGroup(Request $request, InterUnitMail $mail): RedirectResponse
    {
        $mine = $this->myUnit($request);
        abort_unless($mail->to_unit_id === $mine && $mail->status === InterUnitMail::STATUS_RECEIVED, 403);
        abort_unless($this->isAreaUnit($mine), 400, 'หน่วยงานนี้ไม่ใช้เส้นทางกลุ่มงาน');
        abort_unless($request->user()->hasAnyRole(['saraban', 'secretary', 'admin']), 403);

        $v = $request->validate(['to_group_id' => ['required', 'integer', 'exists:groups,id']]);
        $group = Group::where('id', $v['to_group_id'])->where('unit_id', $mine)->first();
        abort_unless($group, 422, 'ต้องเลือกกลุ่มงานในหน่วยงานนี้');

        $mail->update([
            'to_group_id' => $group->id,
            'status' => InterUnitMail::STATUS_ASSIGNED_GROUP,
            'assigned_group_at' => now(),
        ]);

        // แจ้งสารบรรณกลุ่ม (group_clerk) ของกลุ่มนั้น; ถ้าไม่มีให้แจ้งหัวหน้ากลุ่ม
        $clerks = User::where('group_id', $group->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'group_clerk'))
            ->get();
        if ($clerks->isEmpty() && $group->head_user_id) {
            $clerks = User::whereKey($group->head_user_id)->get();
        }
        $this->notifyUsers($clerks, 'หนังสือราชการมอบให้กลุ่ม รอมอบบุคลากร', 'กลุ่ม '.$group->name.' เรื่อง "'.$mail->subject.'"', $mail);

        return back()->with('success', 'มอบให้กลุ่ม '.$group->name.' เรียบร้อย');
    }

    /** มอบหนังสือให้บุคคล — สารบรรณกลุ่ม (เขต) หรือสารบรรณสถานศึกษา (โรงเรียน) */
    public function forward(Request $request, InterUnitMail $mail): RedirectResponse
    {
        $mine = $this->myUnit($request);
        abort_unless($mail->to_unit_id === $mine, 403);
        $user = $request->user();
        $isArea = $this->isAreaUnit($mine);

        if ($isArea) {
            // เขต: ต้องผ่านการมอบกลุ่มก่อน และผู้มอบบุคคลคือสารบรรณกลุ่มของกลุ่มนั้น (หรือสารบรรณกลาง/admin)
            abort_unless(in_array($mail->status, [InterUnitMail::STATUS_ASSIGNED_GROUP, InterUnitMail::STATUS_FORWARDED], true) && $mail->to_group_id, 403);
            $isGroupClerk = $user->group_id === $mail->to_group_id && $user->hasRole('group_clerk');
            abort_unless($isGroupClerk || $user->hasAnyRole(['saraban', 'secretary', 'admin']), 403);
        } else {
            // โรงเรียน: สารบรรณสถานศึกษามอบบุคคลได้เลยหลังลงทะเบียนรับ
            abort_unless(in_array($mail->status, [InterUnitMail::STATUS_RECEIVED, InterUnitMail::STATUS_FORWARDED], true), 403);
            abort_unless($user->hasAnyRole(['saraban', 'school_clerk', 'secretary', 'admin']), 403);
        }

        $v = $request->validate(['assigned_to' => ['required', 'integer', 'exists:users,id']]);

        // เขต = ต้องเป็นบุคคลในกลุ่มที่รับมอบ; โรงเรียน = บุคคลในหน่วยงาน
        $valid = $isArea
            ? User::where('id', $v['assigned_to'])->where('group_id', $mail->to_group_id)->exists()
            : User::where('id', $v['assigned_to'])->where('unit_id', $mine)->exists();
        abort_unless($valid, 422, $isArea ? 'ต้องเลือกบุคลากรในกลุ่มที่รับมอบ' : 'ต้องเลือกบุคลากรในหน่วยงานเดียวกัน');

        $mail->update([
            'assigned_to' => $v['assigned_to'],
            'status' => InterUnitMail::STATUS_FORWARDED,
            'forwarded_at' => now(),
        ]);

        $this->notifyUsers(User::whereKey($v['assigned_to'])->get(), 'หนังสือราชการมอบให้ท่านดำเนินการ', 'เรื่อง "'.$mail->subject.'"', $mail);

        return back()->with('success', 'มอบหนังสือให้บุคลากรเรียบร้อย');
    }

    /** สร้างไทม์ไลน์สถานะหนังสือ (ส่ง → รับ → มอบหมาย) */
    private function timelineFor(InterUnitMail $m): array
    {
        $events = [[
            'key' => 'sent',
            'label' => 'ส่งออกจากต้นทาง',
            'who' => $m->sender?->name,
            'where' => $m->fromUnit?->name,
            'at' => $this->thai($m->created_at),
            'time' => $m->created_at?->format('H:i'),
        ]];
        if ($m->received_at) {
            $events[] = [
                'key' => 'received',
                'label' => 'ลงทะเบียนรับ (เลขรับ '.$m->receive_number.')',
                'who' => $m->receiver?->name,
                'where' => $m->toUnit?->name,
                'at' => $this->thai($m->received_at),
                'time' => $m->received_at?->format('H:i'),
            ];
        }
        if ($m->assigned_group_at) {
            $events[] = [
                'key' => 'assigned_group',
                'label' => 'มอบให้กลุ่มงาน'.($m->toGroup ? ' ('.$m->toGroup->name.')' : ''),
                'who' => null,
                'where' => $m->toUnit?->name,
                'at' => $this->thai($m->assigned_group_at),
                'time' => $m->assigned_group_at?->format('H:i'),
            ];
        }
        if ($m->forwarded_at) {
            $events[] = [
                'key' => 'forwarded',
                'label' => 'มอบหมายให้บุคลากร',
                'who' => $m->assignee?->name,
                'where' => $m->toUnit?->name,
                'at' => $this->thai($m->forwarded_at),
                'time' => $m->forwarded_at?->format('H:i'),
            ];
        }

        return $events;
    }

    /** ค้นหาติดตามหนังสือด้วยเลขติดตาม (แบบไปรษณีย์) */
    public function track(Request $request): Response
    {
        $no = trim((string) $request->query('no', ''));
        $result = null;
        if ($no !== '') {
            $mail = InterUnitMail::with(['fromUnit:id,name', 'toUnit:id,name', 'toGroup:id,name', 'sender:id,name', 'receiver:id,name', 'assignee:id,name'])
                ->where('tracking_no', $no)->first();
            if ($mail) {
                $result = [
                    'tracking_no' => $mail->tracking_no,
                    'number' => $mail->number,
                    'subject' => $mail->subject,
                    'from' => $mail->fromUnit?->name,
                    'to' => $mail->toUnit?->name,
                    'status' => $mail->status,
                    'status_label' => InterUnitMail::STATUS_LABELS[$mail->status] ?? $mail->status,
                    'timeline' => $this->timelineFor($mail),
                ];
            }
        }

        return Inertia::render('Saraban::AreaMail/Track', [
            'q' => $no,
            'result' => $result,
            'notFound' => $no !== '' && ! $result,
        ]);
    }

    public function show(Request $request, InterUnitMail $mail): Response
    {
        $mine = $this->myUnit($request);
        abort_unless($mail->from_unit_id === $mine || $mail->to_unit_id === $mine, 403);
        $mail->load(['fromUnit:id,name', 'toUnit:id,name', 'toGroup:id,name', 'sender:id,name', 'receiver:id,name', 'assignee:id,name']);

        $user = $request->user();
        $isRecipient = $mail->to_unit_id === $mine;
        $isArea = $isRecipient && $this->isAreaUnit($mine);

        // สิทธิ์ดำเนินการตามเส้นทาง AMSS
        $canReceive = $isRecipient && $mail->status === InterUnitMail::STATUS_SENT
            && $user->hasAnyRole(['saraban', 'secretary', 'school_clerk', 'admin']);
        $canAssignGroup = $isArea && $mail->status === InterUnitMail::STATUS_RECEIVED
            && $user->hasAnyRole(['saraban', 'secretary', 'admin']);
        if ($isArea) {
            $isGroupClerk = $user->group_id === $mail->to_group_id && $user->hasRole('group_clerk');
            $canForward = in_array($mail->status, [InterUnitMail::STATUS_ASSIGNED_GROUP, InterUnitMail::STATUS_FORWARDED], true)
                && $mail->to_group_id && ($isGroupClerk || $user->hasAnyRole(['saraban', 'secretary', 'admin']));
        } else {
            $canForward = $isRecipient && in_array($mail->status, [InterUnitMail::STATUS_RECEIVED, InterUnitMail::STATUS_FORWARDED], true)
                && $user->hasAnyRole(['saraban', 'school_clerk', 'secretary', 'admin']);
        }

        // รายชื่อผู้รับมอบ: เขต = บุคคลในกลุ่มที่รับมอบ / โรงเรียน = บุคคลในหน่วยงาน
        $people = [];
        if ($canForward) {
            $q = $isArea
                ? User::where('group_id', $mail->to_group_id)
                : User::where('unit_id', $mine);
            $people = $q->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
        }

        // กลุ่มงานสำหรับมอบ (เฉพาะเขต ตอนสารบรรณกลางมอบกลุ่ม)
        $groups = $canAssignGroup
            ? Group::where('unit_id', $mine)->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])
            : [];

        return Inertia::render('Saraban::AreaMail/Show', [
            'mail' => array_merge($this->mapRow($mail), [
                'detail' => $mail->detail,
                'reference' => $mail->reference,
                'files' => collect($mail->attachments ?? [])->map(fn ($a) => ['name' => $a['name'] ?? 'ไฟล์', 'url' => Storage::url($a['path'])]),
                'can_receive' => $canReceive,
                'can_assign_group' => $canAssignGroup,
                'can_forward' => $canForward,
                'status_label' => InterUnitMail::STATUS_LABELS[$mail->status] ?? $mail->status,
                'timeline' => $this->timelineFor($mail),
            ]),
            'people' => $people,
            'groups' => $groups,
        ]);
    }
}

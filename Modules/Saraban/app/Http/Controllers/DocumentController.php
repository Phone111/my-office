<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Signature;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentRoute;
use Modules\Saraban\Services\DocumentWorkflowService;
use Modules\Saraban\Services\NumberRegisterService;
use RuntimeException;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentWorkflowService $workflow,
        private readonly NumberRegisterService $numbers,
    ) {
    }

    /**
     * แฟ้มเอกสารของฉัน — แยกตามหมวดหมู่แฟ้ม (บันทึกข้อความ / รับ / ส่ง / ทั่วไป)
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        // หมวดที่กำลังเปิดดู (ค่าเริ่มต้น = บันทึกข้อความ)
        $category = $request->string('category')->toString();
        if (! array_key_exists($category, Document::CATEGORIES)) {
            $category = Document::CATEGORY_MEMO;
        }

        // ทะเบียนรายปี — ค่าเริ่มต้น = ปีสารบรรณปัจจุบัน, เลือกปีอื่นได้
        $activeYear = (int) (\Modules\Saraban\Models\SarabanSetting::get('active_year') ?: (now()->year + 543));
        $year = (int) ($request->get('year') ?: $activeYear);
        $gy = $year - 543;

        $documents = Document::with(['currentRoute.approver:id,name'])
            ->where('creator_id', $userId)
            ->where('category', $category)
            ->whereYear('created_at', $gy)
            ->latest()
            ->get()
            ->map(fn (Document $doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'document_number' => $doc->document_number,
                'status' => $doc->status,
                'priority' => $doc->priority ?? 'normal',
                'filing' => $doc->filing,
                'current_approver' => $doc->currentRoute?->approver?->name,
                'created_at' => $doc->created_at->format('d/m/Y'),
                'source_name' => $doc->source_name,
                'division' => $doc->division,
                'source_number' => $doc->source_number,
                'source_date' => $doc->source_date
                    ? $doc->source_date->locale('th')->translatedFormat('j M').' '.($doc->source_date->year + 543).' : '.$doc->source_date->format('H:i')
                    : null,
            ]);

        // จำนวนเอกสารในแต่ละแฟ้ม (ป้ายบนแท็บ) — เฉพาะปีที่เลือก
        $counts = Document::where('creator_id', $userId)
            ->whereYear('created_at', $gy)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $folders = collect(Document::CATEGORIES)
            ->except(Document::ISSUER_ONLY)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
            ])
            ->values();

        // ปี พ.ศ. ที่มีเอกสาร (สำหรับเลือกดูย้อนหลัง)
        $years = Document::where('creator_id', $userId)
            ->selectRaw('DISTINCT YEAR(created_at) as gy')
            ->orderByDesc('gy')
            ->pluck('gy')
            ->map(fn ($g) => (int) $g + 543)
            ->values();
        if (! $years->contains($year)) {
            $years->prepend($year);
        }

        return Inertia::render('Saraban::Index', [
            'documents' => $documents,
            'category' => $category,
            'year' => $year,
            'years' => $years,
            'folders' => $folders,
            'divisions' => Document::DIVISIONS,
            'priorities' => collect(Document::PRIORITIES)->map(fn ($p, $k) => ['value' => $k, 'label' => $p['label']])->values(),
        ]);
    }

    /**
     * หน้าเขียนเอกสารใหม่ (แยกเป็นหน้าเฉพาะ)
     */
    public function create(Request $request): Response
    {
        $category = $request->string('category')->toString();
        if (! in_array($category, Document::composableCategories(), true)) {
            $category = Document::CATEGORY_MEMO;
        }

        return Inertia::render('Saraban::Create', [
            'category' => $category,
            'folders' => collect(Document::CATEGORIES)
                ->except(Document::ISSUER_ONLY)
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'divisions' => Document::DIVISIONS,
            'priorities' => collect(Document::PRIORITIES)->map(fn ($p, $k) => ['value' => $k, 'label' => $p['label']])->values(),
        ]);
    }

    /**
     * รายชื่อผู้รับเสนอแฟ้ม จัดกลุ่มตามบทบาท (ใช้ในแผง "เสนอต่อ" ของหน้าเอกสาร)
     *
     * @return array<int, array{role: string, label: string, users: array}>
     */
    private function approverOptions(): array
    {
        // ลำดับสายงาน (เสนอต่อได้เฉพาะตำแหน่งที่ "สูงกว่า" ผู้เสนอ) — บนลงล่าง
        $roleLabels = [
            'head' => 'หัวหน้างาน / หัวหน้ากลุ่ม',
            'deputy_director' => 'รองผู้อำนวยการ',
            'director' => 'ผู้อำนวยการ',
        ];
        $rank = ['head' => 1, 'deputy_director' => 2, 'director' => 3];

        $me = Auth::user();
        $myId = $me?->id;
        $overseer = $me && $me->hasAnyRole(['admin', 'area_admin']);

        // ระดับของผู้ใช้ปัจจุบัน (ผู้เขียน/เจ้าหน้าที่ = 0 เสนอได้ทุกตำแหน่ง)
        $myRank = match (true) {
            (bool) $me?->hasRole('director') => 3,
            (bool) $me?->hasRole('deputy_director') => 2,
            (bool) $me?->hasRole('head') => 1,
            default => 0,
        };

        return collect($roleLabels)
            ->filter(fn (string $label, string $role) => ($rank[$role] ?? 0) > $myRank) // เฉพาะตำแหน่งสูงกว่าตน
            ->map(fn (string $label, string $role) => [
                'role' => $role,
                'label' => $label,
                'users' => User::whereHas('roles', fn ($q) => $q->where('name', $role))
                    ->where('id', '!=', $myId) // ไม่เสนอให้ตัวเอง
                    ->when(! $overseer, fn ($q) => $q->where('unit_id', $me?->unit_id)) // เฉพาะหน่วยงานตน
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                    ->all(),
            ])
            ->filter(fn (array $g) => count($g['users']) > 0)
            ->values()
            ->all();
    }

    /**
     * แฟ้มเอกสารถูกตีกลับ — เอกสารของฉันที่ถูกตีกลับ รอแก้ไข+เสนอใหม่
     */
    public function rejected(Request $request): Response
    {
        $userId = $request->user()->id;

        $rows = Document::with(['routes' => fn ($q) => $q->where('status', DocumentRoute::STATUS_REJECTED)->latest('acted_at')])
            ->where('creator_id', $userId)
            ->where('status', Document::STATUS_REJECTED)
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'document_number' => $d->document_number,
                'title' => $d->title,
                'category_label' => Document::CATEGORIES[$d->category] ?? $d->category,
                'reject_comment' => preg_replace('/^\[.*?\]\s*/u', '', (string) optional($d->routes->first())->comment) ?: null,
                'created_at' => $d->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Saraban::Rejected', ['rows' => $rows]);
    }

    /**
     * หน้าแก้ไขเอกสารที่ถูกตีกลับ (เจ้าของเรื่อง) — ใช้ฟอร์มเดียวกับเขียนใหม่
     */
    public function edit(Document $document): Response
    {
        abort_unless(
            $document->creator_id === Auth::id()
                && in_array($document->status, [Document::STATUS_REJECTED, Document::STATUS_DRAFT], true),
            403,
        );

        $rejectComment = $document->routes()
            ->where('status', DocumentRoute::STATUS_REJECTED)
            ->latest('acted_at')
            ->value('comment');

        return Inertia::render('Saraban::Create', [
            'category' => $document->category,
            'folders' => collect(Document::CATEGORIES)->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values(),
            'divisions' => Document::DIVISIONS,
            'priorities' => collect(Document::PRIORITIES)->map(fn ($p, $k) => ['value' => $k, 'label' => $p['label']])->values(),
            'document' => [
                'id' => $document->id,
                'category' => $document->category,
                'title' => $document->title,
                'content' => $document->content,
                'priority' => $document->priority ?? 'normal',
                'division' => $document->division,
                'reject_comment' => $rejectComment,
            ],
        ]);
    }

    /**
     * แก้ไข + เสนอใหม่ — ส่งกลับเข้าเส้นทางเดิม (ผู้รับเสนอคนแรก) อีกครั้ง
     */
    public function resubmit(Request $request, Document $document): RedirectResponse
    {
        abort_unless(
            $document->creator_id === $request->user()->id
                && in_array($document->status, [Document::STATUS_REJECTED, Document::STATUS_DRAFT], true),
            403,
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', Rule::in(array_keys(Document::PRIORITIES))],
            'division' => ['nullable', 'string', 'max:255'],
        ]);

        $priority = $validated['priority'] ?? 'normal';

        // ผู้รับเสนอคนแรกเดิม (ผอ. สำหรับบันทึก / ผู้ที่เลือกไว้ สำหรับหนังสือรับ)
        $firstApprover = $document->routes()->orderBy('step_order')->first()?->approver_id;
        if (! $firstApprover) {
            $firstApprover = User::whereHas('roles', fn ($q) => $q->where('name', 'director'))->value('id');
        }
        if (! $firstApprover) {
            throw ValidationException::withMessages(['title' => 'ไม่พบผู้รับเสนอ ไม่สามารถเสนอใหม่ได้']);
        }

        $document->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $priority,
            'is_urgent' => $priority !== 'normal',
            'division' => $validated['division'] ?? null,
        ]);

        $this->workflow->startTo($document, (int) $firstApprover);

        return redirect()
            ->route('saraban.documents.index', ['category' => $document->category])
            ->with('success', 'แก้ไขและเสนอใหม่เรียบร้อยแล้ว');
    }

    /**
     * เขียน + ส่งเอกสารเข้าสู่เส้นทางอนุมัติ
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(Document::composableCategories())],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', Rule::in(array_keys(Document::PRIORITIES))],
            'division' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        $priority = $validated['priority'] ?? 'normal';

        $document = new Document([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $priority,
            'is_urgent' => $priority !== 'normal',
            'division' => $validated['division'] ?? null,
            'creator_id' => $request->user()->id,
            'status' => Document::STATUS_DRAFT,
        ]);

        if ($request->hasFile('file')) {
            $document->file_path = $request->file('file')->store('documents', 'public');
        }

        $document->save();

        // บันทึกเป็น "ร่าง" (รอเสนอแฟ้ม) — ยังไม่ส่ง/ออกเลข จนกว่าจะกด "เสนอแฟ้ม"
        return redirect()
            ->route('saraban.documents.drafts')
            ->with('success', 'บันทึกร่างแล้ว — กด "เสนอแฟ้ม" เพื่อเลือกผู้รับและส่ง');
    }

    /** รายการร่างของฉัน (รอเสนอแฟ้ม) */
    public function drafts(Request $request): Response
    {
        $rows = Document::where('creator_id', $request->user()->id)
            ->where('status', Document::STATUS_DRAFT)
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category_label' => Document::CATEGORIES[$d->category] ?? $d->category,
                'priority_label' => Document::PRIORITIES[$d->priority]['label'] ?? $d->priority,
                'created_thai' => $d->created_at->format('d/m/').($d->created_at->year + 543).$d->created_at->format(' H:i'),
                'has_file' => (bool) $d->file_path,
            ]);

        return Inertia::render('Saraban::Drafts', [
            'rows' => $rows,
            'approverOptions' => $this->approverOptions(),
        ]);
    }

    /** เสนอแฟ้ม — ส่งร่างเข้าเส้นทางถึงผู้รับที่เลือก + ออกเลขทะเบียน */
    public function propose(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->creator_id === $request->user()->id && $document->status === Document::STATUS_DRAFT, 403);

        $validated = $request->validate(['approver_id' => ['required', 'integer', 'exists:users,id']]);

        $allowed = collect($this->approverOptions())->flatMap(fn ($g) => collect($g['users'])->pluck('id'))->all();
        abort_unless(in_array((int) $validated['approver_id'], $allowed, true), 422, 'ผู้รับเสนอไม่ถูกต้อง');

        $this->workflow->startTo($document, (int) $validated['approver_id']);

        if (! $document->document_number) {
            $document->update([
                'document_number' => $this->numbers->issue($document->category, Document::NUMBER_PREFIXES[$document->category] ?? ''),
                'number_issued_at' => now(),
            ]);
        }

        return redirect()
            ->route('saraban.documents.drafts')
            ->with('success', "เสนอแฟ้มเรียบร้อยแล้ว (เลขทะเบียน {$document->document_number})");
    }

    /** แนบไฟล์ให้ร่าง */
    public function attachFile(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->creator_id === $request->user()->id && $document->status === Document::STATUS_DRAFT, 403);

        $request->validate(['file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240']]);

        $document->update(['file_path' => $request->file('file')->store('documents', 'public')]);

        return back()->with('success', 'แนบไฟล์เรียบร้อย');
    }

    /**
     * รายละเอียดเอกสาร + ไทม์ไลน์การอนุมัติ
     */
    public function show(Document $document): Response
    {
        $this->authorizeView($document);

        // เคลียร์แจ้งเตือนกระดิ่งของเอกสารนี้เมื่อผู้เกี่ยวข้องเปิดอ่าน
        // (จับคู่ด้วย URL → ครอบคลุมทั้งผู้อนุมัติ "รออนุมัติ" และเจ้าของเรื่อง "อนุมัติ/ตีกลับ")
        $docUrl = route('saraban.documents.show', $document->id);
        Auth::user()->unreadNotifications->each(function ($n) use ($docUrl, $document) {
            if (($n->data['url'] ?? null) === $docUrl || ($n->data['key'] ?? null) === 'document:'.$document->id) {
                $n->markAsRead();
            }
        });

        $document->load(['creator:id,name', 'routes.approver:id,name']);

        // ลายเซ็นของผู้อนุมัติ + ผู้สร้าง (ใช้ประทับบนเอกสาร)
        $sigUserIds = $document->routes->pluck('approver_id')->push($document->creator_id);
        $signatures = Signature::whereIn('user_id', $sigUserIds)->pluck('file_path', 'user_id');

        // ขั้นที่ "ฉัน" ต้องดำเนินการอยู่ตอนนี้
        $myRoute = $document->routes->first(
            fn (DocumentRoute $r) => $r->approver_id === Auth::id() && $r->status === DocumentRoute::STATUS_PENDING,
        );

        $thaiDate = $document->created_at->locale('th')->translatedFormat('j F').' '.($document->created_at->year + 543);

        $priority = $document->priority ?? 'normal';

        // แยก "[คำสั่งการ] ความเห็น" ออกจากกัน
        $parseComment = function (?string $comment): array {
            if (! $comment) {
                return ['actions' => [], 'note' => null];
            }
            if (preg_match('/^\[(.*?)\]\s*(.*)$/su', $comment, $m)) {
                return [
                    'actions' => array_values(array_filter(array_map('trim', explode(',', $m[1])))),
                    'note' => $m[2] !== '' ? $m[2] : null,
                ];
            }

            return ['actions' => [], 'note' => $comment];
        };

        return Inertia::render('Saraban::Show', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'document_number' => $document->document_number,
                'category' => $document->category,
                'category_label' => Document::CATEGORIES[$document->category] ?? $document->category,
                'content' => $document->content,
                'file_path' => $document->file_path,
                'attachments' => collect($document->attachments ?? [])
                    ->map(fn ($p, $i) => ['name' => 'ไฟล์แนบ '.($i + 1), 'url' => asset('storage/'.$p)])
                    ->when($document->file_path, fn ($c) => $c->push(['name' => 'เอกสารแนบ', 'url' => asset('storage/'.$document->file_path)]))
                    ->values(),
                'status' => $document->status,
                'priority' => $priority,
                'priority_label' => Document::PRIORITIES[$priority]['label'] ?? 'ปกติ',
                'priority_classes' => Document::PRIORITIES[$priority]['classes'] ?? 'bg-emerald-100 text-emerald-700',
                'division' => $document->division,
                'creator' => $document->creator->name,
                'source_name' => $document->source_name,
                'source_number' => $document->source_number,
                'source_date_thai' => $document->source_date
                    ? $document->source_date->locale('th')->translatedFormat('j F').' '.($document->source_date->year + 543)
                    : null,
                'is_received' => in_array($document->category, [Document::CATEGORY_INCOMING, Document::CATEGORY_INTERNAL_IN, Document::CATEGORY_GENERAL_IN], true),
                'is_copy' => str_starts_with((string) $document->filing, 'จัดเก็บจาก'),
                'creator_signature' => isset($signatures[$document->creator_id]) ? asset('storage/'.$signatures[$document->creator_id]) : null,
                'created_at' => $document->created_at->format('Y-m-d H:i'),
                'created_thai' => $thaiDate,
                'filing' => $document->filing,
                'routes' => $document->routes->map(function (DocumentRoute $r) use ($signatures, $parseComment) {
                    $parsed = $parseComment($r->comment);

                    return [
                        'step_order' => $r->step_order,
                        'approver' => $r->approver->name,
                        'status' => $r->status,
                        'comment' => $r->comment,
                        'actions' => $parsed['actions'],
                        'note' => $parsed['note'],
                        'acted_at' => $r->acted_at?->format('Y-m-d H:i'),
                        'acted_thai' => $r->acted_at
                            ? $r->acted_at->locale('th')->translatedFormat('j M').' '.($r->acted_at->year + 543)
                            : null,
                        'signature_url' => (in_array($r->status, [DocumentRoute::STATUS_APPROVED, DocumentRoute::STATUS_REJECTED], true) && isset($signatures[$r->approver_id]))
                            ? asset('storage/'.$signatures[$r->approver_id])
                            : null,
                    ];
                }),
            ],
            // ขั้นที่ฉันต้องดำเนินการ (แสดงแถบสั่งการ)
            'myRouteId' => $myRoute?->id,
            // ผอ. = ผู้ลงนามขั้นสุดท้าย (แถบ "ผู้บริหารดำเนินการต่อ"); บทบาทอื่น = แถบเสนอต่อ
            'isDirector' => Auth::user()->hasRole('director'),
            // ตัวเลือกผู้รับเสนอต่อ (แสดงเฉพาะตอนที่ฉันต้องเสนอต่อ)
            'approverOptions' => $myRoute ? $this->approverOptions() : [],
            // รองผอ. ลงนามปิดเรื่องในนาม "ปฏิบัติราชการแทน ผอ." ได้ (เมื่อเป็นผู้รับเสนอปัจจุบัน)
            'canSignActing' => (bool) ($myRoute && Auth::user()->hasRole('deputy_director')),
            // ผู้รักษาการในตำแหน่ง ผอ. (สำหรับปุ่ม "เสนอรักษาการ")
            'actingDirector' => $myRoute
                ? User::where('is_acting_director', true)
                    ->where('unit_id', Auth::user()->unit_id)
                    ->where('id', '!=', Auth::id())
                    ->first(['id', 'name'])
                : null,
            // ผู้สร้างจัดเก็บเอกสารได้เมื่ออนุมัติครบแล้ว
            'canFile' => $document->creator_id === Auth::id() && $document->status === Document::STATUS_APPROVED,
            // เจ้าหน้าที่สารบรรณกลาง (สำหรับโหมด "นำส่งสารบรรณกลาง" ให้ออกเลขส่ง)
            'sarabanUsers' => ($document->creator_id === Auth::id() && $document->status === Document::STATUS_APPROVED)
                ? User::whereHas('roles', fn ($q) => $q->where('name', 'saraban'))
                    ->where('id', '!=', Auth::id())
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            // ผู้ที่ถูกนำส่งให้ออกเลขส่งแล้ว (แสดงสถานะ)
            'handedToSaraban' => $document->handed_to_saraban_id
                ? User::find($document->handed_to_saraban_id, ['id', 'name'])
                : null,
            // ผู้สร้างแก้ไข+เสนอใหม่ได้เมื่อถูกตีกลับ
            'canEdit' => $document->creator_id === Auth::id() && $document->status === Document::STATUS_REJECTED,
        ]);
    }

    /**
     * แฟ้มเอกสารรอดำเนินการ — เอกสารที่รอ "ฉัน" อนุมัติ
     */
    public function inbox(Request $request): Response
    {
        $userId = $request->user()->id;

        // กรองตามกลุ่มแฟ้ม (proposal/official/...) ถ้าระบุมา
        $folder = $request->string('folder')->toString();
        $folderCategories = Document::INBOX_FOLDERS[$folder]['categories'] ?? null;

        $routes = DocumentRoute::with(['document.creator:id,name'])
            ->where('approver_id', $userId)
            ->where('status', DocumentRoute::STATUS_PENDING)
            ->when($folderCategories, fn ($q) => $q->whereHas('document', fn ($d) => $d->whereIn('category', $folderCategories)))
            ->latest()
            ->get()
            // ข้ามเอกสารที่ถูกทำลาย (soft delete) — relation คืน null
            ->filter(fn (DocumentRoute $r) => $r->document)
            // เอกสารด่วนขึ้นก่อน (sort เสถียร คงลำดับ latest เดิมในกลุ่มเดียวกัน)
            ->sortByDesc(fn (DocumentRoute $r) => $r->document->is_urgent)
            ->values()
            ->map(fn (DocumentRoute $r) => [
                'route_id' => $r->id,
                'document_id' => $r->document_id,
                'step_order' => $r->step_order,
                'title' => $r->document->title,
                'category' => $r->document->category,
                'is_urgent' => $r->document->is_urgent,
                'creator' => $r->document->creator->name,
                'source_name' => $r->document->source_name,
                'created_at' => $r->document->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Saraban::Inbox', [
            'routes' => $routes,
            'folder' => $folder ?: null,
            'title' => $folderCategories ? Document::INBOX_FOLDERS[$folder]['label'] : 'เอกสารรอดำเนินการ',
        ]);
    }

    /**
     * เอกสารที่ดำเนินการแล้ว — เอกสารที่ "ฉัน" อนุมัติ/ตีกลับไปแล้ว (เก็บแยกจากแฟ้มรอดำเนินการ)
     */
    public function acted(Request $request): Response
    {
        $userId = $request->user()->id;

        $routes = DocumentRoute::with(['document.creator:id,name'])
            ->where('approver_id', $userId)
            ->whereIn('status', [DocumentRoute::STATUS_APPROVED, DocumentRoute::STATUS_REJECTED])
            ->latest('acted_at')
            ->get()
            ->filter(fn (DocumentRoute $r) => $r->document)
            ->values()
            ->map(fn (DocumentRoute $r) => [
                'document_id' => $r->document_id,
                'title' => $r->document->title,
                'creator' => $r->document->creator->name,
                'status' => $r->status,
                'acted_at' => $r->acted_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Saraban::Acted', [
            'routes' => $routes,
        ]);
    }

    /**
     * อนุมัติขั้นที่ได้รับมอบหมาย
     */
    public function approve(Request $request, DocumentRoute $route): RedirectResponse
    {
        $this->authorizeAction($route);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->approve($route, $validated['comment'] ?? null);

        return redirect()
            ->route('saraban.documents.inbox')
            ->with('success', 'อนุมัติเอกสารเรียบร้อยแล้ว');
    }

    /**
     * เสนอต่อ — ผ่านขั้นปัจจุบันแล้วส่งต่อไปยังผู้เสนอที่เลือก
     * (หัวหน้ากลุ่ม → รองผอ. → ผอ.)
     */
    public function forward(Request $request, DocumentRoute $route): RedirectResponse
    {
        $this->authorizeAction($route);

        $validated = $request->validate([
            'approver_id' => ['required', 'integer', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->forward($route, (int) $validated['approver_id'], $validated['comment'] ?? null);

        return redirect()
            ->route('saraban.documents.inbox')
            ->with('success', 'เสนอเอกสารต่อเรียบร้อยแล้ว');
    }

    /**
     * ตีกลับเอกสาร
     */
    public function reject(Request $request, DocumentRoute $route): RedirectResponse
    {
        $this->authorizeAction($route);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $this->workflow->reject($route, $validated['comment']);

        return redirect()
            ->route('saraban.documents.inbox')
            ->with('success', 'ตีกลับเอกสารเรียบร้อยแล้ว');
    }

    /**
     * จัดเก็บเอกสาร (แฟ้ม) — เฉพาะผู้สร้าง หลังอนุมัติครบ
     */
    public function file(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->creator_id === $request->user()->id, 403);
        abort_unless($document->status === Document::STATUS_APPROVED, 400);

        $validated = $request->validate([
            'filing' => ['required', 'string', 'max:255'],
        ]);

        $document->update(['filing' => $validated['filing']]);

        return back()->with('success', 'จัดเก็บเอกสารเรียบร้อยแล้ว');
    }

    /**
     * นำส่งสารบรรณกลาง — เจ้าของเรื่องส่งหนังสือที่อนุมัติแล้วให้เจ้าหน้าที่สารบรรณกลาง
     * เพื่อออกเลขส่ง + ส่งหน่วยงานปลายทาง (กรณีให้สารบรรณช่วยส่งหนังสือ)
     */
    public function handToSaraban(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->creator_id === $request->user()->id, 403);
        abort_unless($document->status === Document::STATUS_APPROVED, 400);

        $validated = $request->validate([
            'saraban_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        // ผู้รับต้องเป็นเจ้าหน้าที่สารบรรณจริง
        $saraban = User::whereKey($validated['saraban_id'])
            ->whereHas('roles', fn ($q) => $q->where('name', 'saraban'))
            ->first();

        if (! $saraban) {
            throw ValidationException::withMessages([
                'saraban_id' => 'กรุณาเลือกเจ้าหน้าที่สารบรรณกลาง',
            ]);
        }

        $document->update([
            'handed_to_saraban_id' => $saraban->id,
            'filing' => 'นำส่งสารบรรณกลาง',
        ]);

        // แจ้งเตือนเจ้าหน้าที่สารบรรณให้ออกเลขส่ง
        $saraban->notify(new \Modules\Core\Notifications\ApprovalNotification(
            title: 'หนังสือรอออกเลขส่ง',
            message: $request->user()->name.' นำส่ง "'.$document->title.'" ให้ออกเลขส่งหนังสือ',
            url: route('saraban.documents.show', $document->id),
            type: 'info',
            key: 'doc-hand:'.$document->id,
        ));

        return back()->with('success', 'นำส่งสารบรรณกลาง ('.$saraban->name.') เรียบร้อย รอออกเลขส่ง');
    }

    /**
     * ลบเอกสาร (เฉพาะผู้สร้าง หรือ admin)
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless(
            $document->creator_id === $request->user()->id || $request->user()->hasRole('admin'),
            403,
        );

        // เอกสารที่จัดเก็บเข้าแฟ้มแล้ว = บันทึกถาวร ลบไม่ได้
        if ($document->filing) {
            return back()->with('error', 'เอกสารที่จัดเก็บเข้าแฟ้มแล้วไม่สามารถลบได้');
        }

        if ($document->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        }
        $document->routes()->delete();
        $document->delete();

        return back()->with('success', 'ลบเอกสารเรียบร้อยแล้ว');
    }

    /**
     * ผู้ที่ดูเอกสารได้: ผู้สร้าง, ผู้อนุมัติในเส้นทาง, หรือ admin
     */
    private function authorizeView(Document $document): void
    {
        $user = Auth::user();

        // หัวหน้ากลุ่ม/ธุรการกลุ่ม เปิดดูเอกสารของสมาชิกในกลุ่มเดียวกันได้
        $isGroupHead = $user->hasAnyRole(['head_of_department', 'head_of_subject', 'group_clerk']);
        $sameGroup = $isGroupHead && $user->group_id
            && User::where('id', $document->creator_id)->value('group_id') === $user->group_id;

        $allowed = $document->creator_id === $user->id
            || $user->hasRole('admin')
            || $sameGroup
            || $document->routes()->where('approver_id', $user->id)->exists();

        abort_unless($allowed, 403);
    }

    /**
     * เฉพาะผู้อนุมัติที่ถึงคิว (status = pending) ของขั้นนั้นเท่านั้นที่ดำเนินการได้
     */
    private function authorizeAction(DocumentRoute $route): void
    {
        abort_unless(
            $route->approver_id === Auth::id()
                && $route->status === DocumentRoute::STATUS_PENDING,
            403
        );
    }
}

<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Group;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ออกเลขทะเบียนส่ง — หนังสือส่งออก (ส่งโรงเรียน/หน่วยงานภายนอก)
 * ออกเลขส่งอัตโนมัติ + แนบไฟล์ + เก็บเข้าทะเบียนส่ง (ไม่ต้องเสนอ)
 */
class OutgoingController extends Controller
{
    public function __construct(private readonly NumberRegisterService $numbers)
    {
    }

    /** หน้าฟอร์มออกเลขทะเบียนส่ง */
    public function create(Request $request): Response
    {
        $number = $request->session()->get('issuedNumber');

        return Inertia::render('Saraban::OutgoingCreate', [
            'priorities' => collect(Document::PRIORITIES)->map(fn ($p, $k) => ['value' => $k, 'label' => $p['label']])->values(),
            // ตัวอย่างรูปแบบเลขทะเบียน (เลขจริงออกตอนบันทึก)
            'numberHint' => Document::NUMBER_PREFIXES[Document::CATEGORY_OUTGOING].' …/'.$this->numbers->buddhistYear(),
            // เจ้าของเรื่อง: เลือกรายบุคคล จัดกลุ่ม (ผู้บริหาร + แต่ละกลุ่ม)
            'ownerBuckets' => $this->ownerBuckets(),
            // ผลการออกเลขล่าสุด (โชว์เลขทะเบียนส่งเด่นๆ หลังบันทึก)
            'justIssued' => $number ? [
                'number' => $number,
                'title' => $request->session()->get('issuedTitle'),
            ] : null,
        ]);
    }

    /**
     * เจ้าของเรื่องจัดกลุ่ม: ผู้บริหาร (executive roles) + กลุ่มงานทั้งหมด
     *
     * @return array<int, array{key: string, name: string, users: array}>
     */
    private function ownerBuckets(): array
    {
        // จำกัดเฉพาะบุคลากรในหน่วยงานตน (admin/area_admin เห็นทุกหน่วยงาน)
        $me = auth()->user();
        $scoped = fn ($q) => $me && ! $me->hasAnyRole(['admin', 'area_admin']) ? $q->where('unit_id', $me->unit_id) : $q;

        $users = $scoped(User::orderBy('name'))->get(['id', 'name', 'group_id']);
        $execIds = $scoped(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['executive', 'head', 'officer'])))
            ->pluck('id')->all();

        $buckets = [];

        $execUsers = $users->whereIn('id', $execIds)->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values();
        if ($execUsers->isNotEmpty()) {
            $buckets[] = ['key' => 'exec', 'name' => 'ผู้บริหาร', 'users' => $execUsers];
        }

        foreach (Group::orderBy('level')->orderBy('name')->get(['id', 'name']) as $g) {
            $gu = $users->where('group_id', $g->id)->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values();
            if ($gu->isNotEmpty()) {
                $buckets[] = ['key' => 'g'.$g->id, 'name' => $g->name, 'users' => $gu];
            }
        }

        return $buckets;
    }

    /** บันทึกหนังสือส่ง + ออกเลขส่ง */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => ['nullable', Rule::in(array_keys(Document::PRIORITIES))],
            'title' => ['required', 'string', 'max:255'],        // เรื่อง
            'source_date' => ['nullable', 'date'],               // ลงวันที่
            'owners' => ['required', 'array', 'min:1'],           // เจ้าของเรื่อง (รายบุคคล)
            'owners.*' => ['integer', 'exists:users,id'],
        ], [
            'owners.required' => 'กรุณาเลือกเจ้าของเรื่องอย่างน้อย 1 คน',
            'owners.min' => 'กรุณาเลือกเจ้าของเรื่องอย่างน้อย 1 คน',
        ]);

        $priority = $validated['priority'] ?? 'normal';
        $ownerNames = User::whereIn('id', $validated['owners'])->orderBy('name')->pluck('name')->implode(', ');

        $document = new Document([
            'category' => Document::CATEGORY_OUTGOING,
            'title' => $validated['title'],
            'content' => '',
            'priority' => $priority,
            'is_urgent' => $priority !== 'normal',
            'division' => $ownerNames,                          // เจ้าของเรื่อง
            'creator_id' => $request->user()->id,              // ผู้ออกเลข
            'source_date' => $validated['source_date'] ?? now(),
            'status' => Document::STATUS_APPROVED,              // หนังสือส่ง = ลงทะเบียนเสร็จ
        ]);

        $document->save();

        // ออกเลขส่งอัตโนมัติ
        $document->update([
            'document_number' => $this->numbers->issue(
                Document::CATEGORY_OUTGOING,
                Document::NUMBER_PREFIXES[Document::CATEGORY_OUTGOING],
            ),
            'number_issued_at' => now(),
        ]);

        return redirect()
            ->route('saraban.outgoing.create')
            ->with('success', 'ออกเลขทะเบียนส่งเรียบร้อย')
            ->with('issuedNumber', $document->document_number)
            ->with('issuedTitle', $document->title);
    }

    /** แฟ้มรอแนบไฟล์ส่ง — หนังสือที่ออกเลขแล้วแต่ยังไม่แนบไฟล์ */
    public function pending(Request $request): Response
    {
        $rows = $this->pendingQuery($request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'document_number' => $d->document_number,
                'title' => $d->title,
                'owner' => $d->division,
                'to' => $d->source_name,
                'date_thai' => $d->source_date
                    ? $d->source_date->locale('th')->translatedFormat('j M').' '.($d->source_date->year + 543)
                    : null,
            ]);

        return Inertia::render('Saraban::OutgoingPending', ['rows' => $rows]);
    }

    /** หน้าแนบไฟล์หนังสือส่ง (เรื่อง/ลงวันที่/ส่งถึง + ไฟล์) */
    public function attachForm(Request $request, Document $document): Response
    {
        $this->authorizeOutgoing($request, $document);

        return Inertia::render('Saraban::OutgoingAttach', [
            'doc' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'source_name' => $document->source_name,
                'source_date' => optional($document->source_date)->format('Y-m-d'),
            ],
        ]);
    }

    /** บันทึกการส่ง — แนบไฟล์ + ส่งถึง */
    public function attach(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOutgoing($request, $document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_date' => ['nullable', 'date'],
            'source_name' => ['required', 'string', 'max:255'],  // ส่งถึง
            'cover_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],   // หนังสือนำ
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ], [
            'source_name.required' => 'กรุณาระบุผู้รับ (ส่งถึง)',
        ]);

        if (! $request->hasFile('cover_file') && empty($request->file('attachments'))) {
            throw ValidationException::withMessages(['cover_file' => 'กรุณาแนบไฟล์อย่างน้อย 1 ไฟล์ (หนังสือนำหรือเอกสารแนบ)']);
        }

        $document->title = $validated['title'];
        $document->source_name = $validated['source_name'];
        if (! empty($validated['source_date'])) {
            $document->source_date = $validated['source_date'];
        }
        if ($request->hasFile('cover_file')) {
            $document->file_path = $request->file('cover_file')->store('documents', 'public');
        }
        $paths = [];
        foreach ($request->file('attachments', []) as $file) {
            $paths[] = $file->store('documents', 'public');
        }
        if ($paths) {
            $document->attachments = $paths;
        }
        $document->save();

        return redirect()->route('saraban.outgoing.show', $document->id);
    }

    /** ตรวจสอบเอกสาร (สรุปหนังสือส่งหลังแนบไฟล์) */
    public function show(Request $request, Document $document): Response
    {
        $this->authorizeOutgoing($request, $document);
        $document->load('creator:id,name');

        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j F').' '.($d->year + 543) : null;

        $files = collect();
        if ($document->file_path) {
            $files->push(['name' => 'หนังสือนำ', 'url' => asset('storage/'.$document->file_path)]);
        }
        foreach ($document->attachments ?? [] as $i => $p) {
            $files->push(['name' => 'เอกสารแนบ '.($i + 1), 'url' => asset('storage/'.$p)]);
        }

        return Inertia::render('Saraban::OutgoingShow', [
            'doc' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'to' => $document->source_name,
                'owner' => $document->division,
                'issuer' => $document->creator?->name,
                'date_thai' => $thai($document->source_date),
                'sent_thai' => $thai($document->created_at),
                'files' => $files->values(),
            ],
        ]);
    }

    /** หนังสือส่งที่ออกเลขแล้วแต่ยังไม่แนบไฟล์ ของผู้ใช้ */
    private function pendingQuery(int $userId)
    {
        return Document::where('category', Document::CATEGORY_OUTGOING)
            ->where('creator_id', $userId)
            ->whereNull('file_path')
            ->where(fn ($q) => $q->whereNull('attachments')->orWhere('attachments', '[]'));
    }

    private function authorizeOutgoing(Request $request, Document $document): void
    {
        abort_unless(
            $document->category === Document::CATEGORY_OUTGOING && $document->creator_id === $request->user()->id,
            403,
        );
    }
}

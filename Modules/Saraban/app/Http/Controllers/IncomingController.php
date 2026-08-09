<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Services\DocumentWorkflowService;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ลงทะเบียนรับหนังสือนอกระบบ — เจ้าหน้าที่สารบรรณรับหนังสือจากภายนอก
 * ออกเลขรับอัตโนมัติ + เก็บเข้าทะเบียนรับ + (ถ้าเลือก) เสนอผู้บริหารตามเส้นทาง
 */
class IncomingController extends Controller
{
    public function __construct(
        private readonly DocumentWorkflowService $workflow,
        private readonly NumberRegisterService $numbers,
    ) {
    }

    /**
     * หน้าฟอร์มลงทะเบียนรับ
     */
    public function create(Request $request): Response
    {
        $number = $request->session()->get('receivedNumber');

        return Inertia::render('Saraban::IncomingCreate', [
            'divisions' => Document::DIVISIONS,
            'priorities' => collect(Document::PRIORITIES)->map(fn ($p, $k) => ['value' => $k, 'label' => $p['label']])->values(),
            'approverOptions' => $this->approverOptions(),
            // ผลการลงรับล่าสุด (โชว์เลขทะเบียนรับเด่นๆ หลังบันทึก)
            'justReceived' => $number ? [
                'number' => $number,
                'title' => $request->session()->get('receivedTitle'),
                'forwarded' => (bool) $request->session()->get('receivedForwarded'),
            ] : null,
        ]);
    }

    /**
     * บันทึกหนังสือรับ + ออกเลขรับ
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => ['nullable', Rule::in(array_keys(Document::PRIORITIES))],
            'source_number' => ['nullable', 'string', 'max:255'],   // เลขที่หนังสือต้นเรื่อง
            'source_date' => ['nullable', 'date'],                  // ลงวันที่
            'title' => ['required', 'string', 'max:255'],           // เรื่อง
            'source_name' => ['required', 'string', 'max:255'],     // จาก (หน่วยงาน)
            'division' => ['nullable', 'string', 'max:255'],        // หนังสือของกลุ่ม
            'content' => ['nullable', 'string'],                    // เกษียนหนังสือ
            'cover_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],      // หนังสือนำ
            'attachments' => ['nullable', 'array', 'max:4'],        // เอกสารแนบ 1-4
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
            'first_approver_id' => ['required', 'integer', 'exists:users,id'], // เสนอถึง (บังคับ)
        ]);

        $priority = $validated['priority'] ?? 'normal';

        $document = new Document([
            'category' => Document::CATEGORY_INCOMING,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? '',
            'priority' => $priority,
            'is_urgent' => $priority !== 'normal',
            'division' => $validated['division'] ?? null,
            'creator_id' => $request->user()->id,
            'source_name' => $validated['source_name'],
            'source_date' => $validated['source_date'] ?? now(),
            'source_number' => $validated['source_number'] ?? null,
            'status' => Document::STATUS_DRAFT,
        ]);

        // หนังสือนำ = ไฟล์หลัก
        if ($request->hasFile('cover_file')) {
            $document->file_path = $request->file('cover_file')->store('documents', 'public');
        }

        // เอกสารแนบ 1-4
        $paths = [];
        foreach ($request->file('attachments', []) as $file) {
            $paths[] = $file->store('documents', 'public');
        }
        $document->attachments = $paths;

        $document->save();

        // ออกเลขรับอัตโนมัติ
        $document->update([
            'document_number' => $this->numbers->issue(
                Document::CATEGORY_INCOMING,
                Document::NUMBER_PREFIXES[Document::CATEGORY_INCOMING],
            ),
            'number_issued_at' => now(),
        ]);

        // เสนอผู้บริหารตามเส้นทางเสนอแฟ้ม (บังคับเลือก)
        $this->workflow->startTo($document, (int) $validated['first_approver_id']);

        // เด้งกลับฟอร์ม + โชว์เลขทะเบียนรับเด่นๆ (พร้อมลงรับเรื่องต่อไปได้ทันที)
        return redirect()
            ->route('saraban.incoming.create')
            ->with('success', 'ลงรับหนังสือภายนอกเรียบร้อย')
            ->with('receivedNumber', $document->document_number)
            ->with('receivedTitle', $document->title)
            ->with('receivedForwarded', true);
    }

    /**
     * รายชื่อผู้รับเสนอ จัดกลุ่มตามบทบาท (หัวหน้ากลุ่ม → รองผอ. → ผอ.)
     */
    private function approverOptions(): array
    {
        $roleLabels = [
            'head' => 'หัวหน้างาน / หัวหน้ากลุ่ม',
            'director' => 'ผู้อำนวยการ',
            'deputy_director' => 'รองผู้อำนวยการ',
        ];

        $me = Auth::user();
        $myId = $me?->id;
        $overseer = $me && $me->hasAnyRole(['admin', 'area_admin']);

        return collect($roleLabels)
            ->map(fn (string $label, string $role) => [
                'role' => $role,
                'label' => $label,
                'users' => User::whereHas('roles', fn ($q) => $q->where('name', $role))
                    ->where('id', '!=', $myId)
                    ->when(! $overseer, fn ($q) => $q->where('unit_id', $me?->unit_id))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                    ->all(),
            ])
            ->filter(fn (array $g) => count($g['users']) > 0)
            ->values()
            ->all();
    }
}

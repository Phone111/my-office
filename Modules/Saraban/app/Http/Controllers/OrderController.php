<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ระบบออกเลขคำสั่ง — ออกเลขคำสั่งอัตโนมัติ + แนบไฟล์คำสั่ง → เก็บทะเบียนคำสั่ง
 */
class OrderController extends Controller
{
    public function __construct(private readonly NumberRegisterService $numbers)
    {
    }

    /** หน้าออกเลขคำสั่ง */
    public function create(Request $request): Response
    {
        $number = $request->session()->get('issuedNumber');

        return Inertia::render('Saraban::OrderCreate', [
            'divisions' => Document::DIVISIONS,
            'numberHint' => '…/'.$this->numbers->buddhistYear(),
            'justIssued' => $number ? [
                'number' => $number,
                'title' => $request->session()->get('issuedTitle'),
            ] : null,
        ]);
    }

    /** บันทึกออกเลขคำสั่ง */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],         // เรื่อง
            'effective_date' => ['nullable', 'date'],             // ทั้งนี้ ตั้งแต่วันที่
            'source_date' => ['nullable', 'date'],                // สั่ง ณ วันที่
            'division' => ['nullable', 'string', 'max:255'],      // กลุ่ม
        ]);

        $document = new Document([
            'category' => Document::CATEGORY_ORDER,
            'title' => $validated['title'],
            'content' => '',
            'priority' => 'normal',
            'division' => $validated['division'] ?? null,
            'creator_id' => $request->user()->id,                 // ผู้ออกเลขคำสั่ง
            'effective_date' => $validated['effective_date'] ?? null,
            'source_date' => $validated['source_date'] ?? now(),  // สั่ง ณ วันที่
            'status' => Document::STATUS_APPROVED,
        ]);
        $document->save();

        // ออกเลขคำสั่งอัตโนมัติ (เล่ม order, ไม่มีคำนำหน้า → "0122/2569")
        $document->update([
            'document_number' => $this->numbers->issue(Document::CATEGORY_ORDER, ''),
            'number_issued_at' => now(),
        ]);

        return redirect()
            ->route('saraban.orders.create')
            ->with('success', 'ออกเลขคำสั่งเรียบร้อย')
            ->with('issuedNumber', $document->document_number)
            ->with('issuedTitle', $document->title);
    }

    /** แฟ้มรอแนบไฟล์คำสั่ง — คำสั่งที่ออกเลขแล้วแต่ยังไม่แนบไฟล์ */
    public function pending(Request $request): Response
    {
        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        $rows = $this->pendingQuery($request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'document_number' => $d->document_number,
                'title' => $d->title,
                'effective_thai' => $thai($d->effective_date),
                'order_thai' => $thai($d->source_date),
            ]);

        return Inertia::render('Saraban::OrderPending', ['rows' => $rows]);
    }

    /** หน้าแนบไฟล์คำสั่ง */
    public function attachForm(Request $request, Document $document): Response
    {
        $this->authorizeOrder($request, $document);

        return Inertia::render('Saraban::OrderAttach', [
            'doc' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'division' => $document->division,
                'effective_date' => optional($document->effective_date)->format('Y-m-d'),
                'source_date' => optional($document->source_date)->format('Y-m-d'),
            ],
        ]);
    }

    /** บันทึกเอกสาร — แนบไฟล์คำสั่ง */
    public function attach(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOrder($request, $document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'effective_date' => ['nullable', 'date'],
            'source_date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        if (empty($request->file('attachments'))) {
            throw ValidationException::withMessages(['attachments' => 'กรุณาแนบไฟล์คำสั่งอย่างน้อย 1 ไฟล์']);
        }

        $document->title = $validated['title'];
        if (! empty($validated['effective_date'])) {
            $document->effective_date = $validated['effective_date'];
        }
        if (! empty($validated['source_date'])) {
            $document->source_date = $validated['source_date'];
        }
        $paths = [];
        foreach ($request->file('attachments', []) as $file) {
            $paths[] = $file->store('documents', 'public');
        }
        $document->attachments = $paths;
        $document->save();

        return redirect()
            ->route('reports.registry.orders')
            ->with('success', "บันทึกคำสั่งที่ {$document->document_number} เข้าทะเบียนคำสั่งเรียบร้อยแล้ว");
    }

    private function pendingQuery(int $userId)
    {
        return Document::where('category', Document::CATEGORY_ORDER)
            ->where('creator_id', $userId)
            ->whereNull('file_path')
            ->where(fn ($q) => $q->whereNull('attachments')->orWhere('attachments', '[]'));
    }

    private function authorizeOrder(Request $request, Document $document): void
    {
        abort_unless(
            $document->category === Document::CATEGORY_ORDER && $document->creator_id === $request->user()->id,
            403,
        );
    }
}

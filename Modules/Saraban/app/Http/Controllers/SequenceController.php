<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * ระบบออกเลขลำดับเอกสาร — ออกเลขรันอัตโนมัติ (เลขลำดับ/ปี พ.ศ.) + แนบไฟล์ในขั้นตอนเดียว
 * เก็บลงทะเบียนลำดับเอกสาร
 */
class SequenceController extends Controller
{
    public function __construct(private readonly NumberRegisterService $numbers)
    {
    }

    /** หน้าออกเลขลำดับเอกสาร */
    public function create(Request $request): Response
    {
        $year = $this->numbers->buddhistYear();

        return Inertia::render('Saraban::SequenceCreate', [
            'divisions' => Document::DIVISIONS,
            'year' => $year,
            'justIssued' => $request->session()->get('issuedNumber') ? [
                'number' => $request->session()->get('issuedNumber'),
                'thai' => $request->session()->get('issuedThai'),
            ] : null,
        ]);
    }

    /** บันทึกออกเลขลำดับเอกสาร */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],        // เรื่อง
            'division' => ['nullable', 'string', 'max:255'],     // ผู้ขอ (กลุ่ม)
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],   // แนบเอกสาร
        ]);

        // ออกเลขลำดับแบบไม่เติมศูนย์ เช่น "1/2569"
        $year = $this->numbers->buddhistYear();
        $no = $this->numbers->next(Document::CATEGORY_SEQUENCE, $year);
        $number = $no.'/'.$year;

        $document = new Document([
            'category' => Document::CATEGORY_SEQUENCE,
            'title' => $validated['title'],
            'content' => '',
            'priority' => 'normal',
            'division' => $validated['division'] ?? null,
            'creator_id' => $request->user()->id,
            'document_number' => $number,
            'number_issued_at' => now(),
            'source_date' => now(),
            'status' => Document::STATUS_APPROVED,
        ]);

        if ($request->hasFile('attachment')) {
            $document->attachments = [$request->file('attachment')->store('documents', 'public')];
        }
        $document->save();

        return redirect()
            ->route('saraban.sequence.create')
            ->with('success', "ออกเลขลำดับเอกสาร {$number} เรียบร้อย")
            ->with('issuedNumber', $number)
            ->with('issuedThai', now()->locale('th')->translatedFormat('j M').' '.(now()->year + 543));
    }

    /** ทะเบียนลำดับเอกสาร */
    public function index(): Response
    {
        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        $rows = Document::where('category', Document::CATEGORY_SEQUENCE)
            ->with('creator:id,name')
            ->latest('number_issued_at')
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'number' => $d->document_number,
                'title' => $d->title,
                'division' => $d->division,
                'issued_thai' => $thai($d->number_issued_at),
                'creator' => $d->creator?->name,
                'file' => ! empty($d->attachments) ? asset('storage/'.$d->attachments[0]) : null,
            ]);

        return Inertia::render('Saraban::SequenceRegistry', ['rows' => $rows]);
    }
}

<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Group;
use Modules\Core\Models\Unit;
use Modules\Saraban\Models\ExternalIncoming;
use Modules\Saraban\Services\NumberRegisterService;

/**
 * รับหนังสือจากหน่วยงานภายนอก (เหนือเขต) — สพฐ./ศธจ./จังหวัด (ระบบเขต ขั้น 4)
 * สารบรรณเขตลงทะเบียนรับ → ออกเลขรับอัตโนมัติ → มอบกลุ่มงาน/บุคคล
 */
class ExternalIncomingController extends Controller
{
    private function myUnit(Request $request): ?int
    {
        return $request->user()->unit_id;
    }

    private function thai($d): ?string
    {
        if (! $d) {
            return null;
        }
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$m[$d->month].' '.($d->year + 543);
    }

    private function mapRow(ExternalIncoming $m): array
    {
        return [
            'id' => $m->id,
            'receive_label' => $m->receive_no ? $m->receive_no.'/'.$m->receive_year : '—',
            'source' => ExternalIncoming::SOURCES[$m->source_type] ?? $m->source_type,
            'source_name' => $m->source_name,
            'number' => $m->number,
            'doc_date_thai' => $this->thai($m->doc_date),
            'received_thai' => $this->thai($m->received_at),
            'subject' => $m->subject,
            'priority' => ExternalIncoming::PRIORITIES[$m->priority] ?? $m->priority,
            'priority_key' => $m->priority,
            'confidential' => $m->confidential,
            'status' => $m->status,
            'group' => $m->group?->name,
            'assignee' => $m->assignee?->name,
            'has_files' => ! empty($m->attachments),
        ];
    }

    public function index(Request $request): Response
    {
        $unit = $this->myUnit($request);
        $rows = ExternalIncoming::with(['group:id,name', 'assignee:id,name'])
            ->where('unit_id', $unit)
            ->latest()->get()->map(fn ($m) => $this->mapRow($m));

        return Inertia::render('Saraban::ExternalMail/Index', [
            'rows' => $rows,
            'myUnit' => Unit::find($unit)?->name,
            'pending' => $rows->where('status', ExternalIncoming::STATUS_RECEIVED)->count(),
        ]);
    }

    public function create(Request $request): Response
    {
        $unit = $this->myUnit($request);

        return Inertia::render('Saraban::ExternalMail/Create', [
            'sources' => collect(ExternalIncoming::SOURCES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'priorities' => collect(ExternalIncoming::PRIORITIES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'groups' => Group::where('unit_id', $unit)->where('is_active', true)->orderBy('level')->get(['id', 'name'])
                ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = $this->myUnit($request);
        abort_unless($unit, 403, 'บัญชีของคุณยังไม่ได้สังกัดหน่วยงาน');

        $v = $request->validate([
            'source_type' => ['required', 'in:'.implode(',', array_keys(ExternalIncoming::SOURCES))],
            'source_name' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:100'],
            'doc_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:4000'],
            'priority' => ['required', 'in:'.implode(',', array_keys(ExternalIncoming::PRIORITIES))],
            'confidential' => ['boolean'],
            'assigned_group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:20480'],
        ]);

        $files = [];
        foreach ($request->file('attachments', []) as $f) {
            $files[] = ['name' => $f->getClientOriginalName(), 'path' => $f->store('external-mail', 'public')];
        }

        // ออกเลขทะเบียนรับอัตโนมัติ (ต่อหน่วยงาน/ปี พ.ศ.) — ล็อกแถวกันเลขซ้ำเมื่อลงพร้อมกัน
        $beYear = (int) now()->year + 543;
        $no = app(NumberRegisterService::class)->nextScoped(
            "ext_in:{$unit}",
            $beYear,
            fn () => (int) (ExternalIncoming::where('unit_id', $unit)->where('receive_year', $beYear)->max('receive_no') ?? 0),
        );

        $mail = ExternalIncoming::create([
            'unit_id' => $unit,
            'source_type' => $v['source_type'],
            'source_name' => $v['source_name'] ?? null,
            'number' => $v['number'] ?? null,
            'doc_date' => $v['doc_date'],
            'subject' => $v['subject'],
            'detail' => $v['detail'] ?? null,
            'priority' => $v['priority'],
            'confidential' => $v['confidential'] ?? false,
            'attachments' => $files,
            'receive_no' => $no,
            'receive_year' => $beYear,
            'received_at' => now(),
            'received_by' => $request->user()->id,
            'assigned_group_id' => $v['assigned_group_id'] ?? null,
            'status' => ! empty($v['assigned_group_id']) ? ExternalIncoming::STATUS_ASSIGNED : ExternalIncoming::STATUS_RECEIVED,
        ]);

        return redirect()->route('saraban.external-mail.index')->with('success', "ลงทะเบียนรับหนังสือเลขที่รับ {$mail->receive_no}/{$mail->receive_year} แล้ว");
    }

    public function show(Request $request, ExternalIncoming $external_mail): Response
    {
        abort_unless($external_mail->unit_id === $this->myUnit($request), 403);
        $external_mail->load(['group:id,name', 'assignee:id,name', 'receiver:id,name']);

        $unit = $this->myUnit($request);

        return Inertia::render('Saraban::ExternalMail/Show', [
            'mail' => array_merge($this->mapRow($external_mail), [
                'detail' => $external_mail->detail,
                'note' => $external_mail->note,
                'receiver' => $external_mail->receiver?->name,
                'files' => collect($external_mail->attachments ?? [])->map(fn ($a) => ['name' => $a['name'] ?? 'ไฟล์', 'url' => Storage::url($a['path'])]),
            ]),
            'groups' => Group::where('unit_id', $unit)->where('is_active', true)->orderBy('level')->get(['id', 'name'])
                ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]),
            'people' => User::where('unit_id', $unit)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    /** มอบกลุ่มงาน/บุคคล */
    public function assign(Request $request, ExternalIncoming $external_mail): RedirectResponse
    {
        abort_unless($external_mail->unit_id === $this->myUnit($request), 403);
        $v = $request->validate([
            'assigned_group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        // จำกัดผู้รับมอบให้อยู่ในหน่วยงานเดียวกับหนังสือ
        $unit = $external_mail->unit_id;
        abort_if(! empty($v['assigned_to']) && ! User::where('id', $v['assigned_to'])->where('unit_id', $unit)->exists(), 422, 'เลือกบุคคลในหน่วยงานเท่านั้น');
        abort_if(! empty($v['assigned_group_id']) && ! Group::where('id', $v['assigned_group_id'])->where('unit_id', $unit)->exists(), 422, 'เลือกกลุ่มในหน่วยงานเท่านั้น');

        $external_mail->update([
            'assigned_group_id' => $v['assigned_group_id'] ?? null,
            'assigned_to' => $v['assigned_to'] ?? null,
            'note' => $v['note'] ?? null,
            'status' => ExternalIncoming::STATUS_ASSIGNED,
        ]);

        return back()->with('success', 'มอบหมายหนังสือเรียบร้อย');
    }
}

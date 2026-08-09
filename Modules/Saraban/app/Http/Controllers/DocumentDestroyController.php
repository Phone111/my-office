<?php

namespace Modules\Saraban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Support\AuditLogger;
use Modules\Saraban\Models\Document;

/**
 * ระบบทำลายหนังสือ — ตารางทะเบียน × ปี กดเลือกปี → เลือกเอกสาร → ทำลาย (soft delete)
 * เอกสารที่ทำลายหายจากทุกทะเบียน เก็บไว้ในทะเบียนทำลาย (กู้คืนได้)
 */
class DocumentDestroyController extends Controller
{
    public function index(Request $request): Response
    {
        $me = $request->user();
        $overseer = $me->hasAnyRole(['admin', 'area_admin']);
        // เฉพาะเอกสารของหน่วยงานตน (ดูจากหน่วยงานของผู้สร้าง) — admin/เขต เห็นทุกหน่วยงาน
        $scoped = fn ($q) => $overseer ? $q : $q->whereHas('creator', fn ($c) => $c->where('unit_id', $me->unit_id));

        // ตาราง: นับจำนวนต่อหมวดต่อปี พ.ศ. (เฉพาะที่ยังไม่ถูกทำลาย)
        $matrix = [];
        $years = collect();
        foreach (Document::composableCategories() as $cat) {
            $rows = $scoped(Document::where('category', $cat))
                ->selectRaw('YEAR(created_at) as gy, COUNT(*) as c')
                ->groupBy('gy')->pluck('c', 'gy');
            $counts = [];
            foreach ($rows as $gy => $c) {
                $counts[(int) $gy + 543] = (int) $c;
            }
            $matrix[$cat] = $counts;
            $years = $years->merge(array_keys($counts));
        }
        $years = $years->unique()->sortDesc()->values();

        $registers = collect(Document::CATEGORIES)
            ->except(Document::ISSUER_ONLY)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'counts' => $matrix[$key] ?? []])
            ->values();

        // รายการเอกสารของหมวด+ปีที่เลือก (ผู้สมัครให้ทำลาย)
        $register = (string) $request->get('register', '');
        $year = (int) $request->get('year', 0);
        $candidates = null;
        $candidateTitle = null;
        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        if (array_key_exists($register, Document::CATEGORIES) && $year > 0) {
            $candidates = $scoped(Document::where('category', $register)->whereYear('created_at', $year - 543))
                ->latest()
                ->get()
                ->map(fn (Document $d) => [
                    'id' => $d->id,
                    'number' => $d->document_number ?? '—',
                    'title' => $d->title,
                    'date_thai' => $thai($d->source_date ?? $d->created_at),
                ]);
            $candidateTitle = (Document::CATEGORIES[$register] ?? $register).' ปี '.$year;
        }

        // ทะเบียนทำลาย (เอกสารที่ทำลายแล้ว — กู้คืนได้)
        $destroyed = $scoped(Document::onlyTrashed())
            ->with('creator:id,name')
            ->latest('deleted_at')
            ->limit(200)
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'number' => $d->document_number ?? '—',
                'title' => $d->title,
                'category_label' => Document::CATEGORIES[$d->category] ?? $d->category,
                'destroyed_thai' => $thai($d->deleted_at),
            ]);

        return Inertia::render('Saraban::DocumentDestroy', [
            'registers' => $registers,
            'years' => $years,
            'selected' => ['register' => $register ?: null, 'year' => $year ?: null],
            'candidates' => $candidates,
            'candidateTitle' => $candidateTitle,
            'destroyed' => $destroyed,
        ]);
    }

    /** ทำลายเอกสารที่เลือก (soft delete) */
    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:documents,id'],
        ], [
            'ids.required' => 'กรุณาเลือกเอกสารที่ต้องการทำลายอย่างน้อย 1 ฉบับ',
        ]);

        $me = $request->user();
        $overseer = $me->hasAnyRole(['admin', 'area_admin']);
        // กันทำลายเอกสารข้ามหน่วยงาน — ทำได้เฉพาะเอกสารของหน่วยงานตน
        $ids = Document::whereIn('id', $validated['ids'])
            ->when(! $overseer, fn ($q) => $q->whereHas('creator', fn ($c) => $c->where('unit_id', $me->unit_id)))
            ->pluck('id');
        abort_if($ids->count() !== count($validated['ids']), 403, 'ไม่มีสิทธิ์ทำลายเอกสารของหน่วยงานอื่น');

        Document::whereIn('id', $ids)->update(['destroyed_by' => $me->id]);
        Document::whereIn('id', $ids)->delete(); // soft delete
        AuditLogger::log('destroy', null, 'ทำลายหนังสือ '.$ids->count().' ฉบับ (id: '.$ids->implode(', ').')');

        return back()->with('success', 'ทำลายเอกสาร '.$ids->count().' ฉบับเรียบร้อยแล้ว (เก็บไว้ในทะเบียนทำลาย)');
    }

    /** กู้คืนเอกสารที่ทำลาย */
    public function restore(Request $request, int $id): RedirectResponse
    {
        $me = $request->user();
        $doc = Document::onlyTrashed()->with('creator:id,unit_id')->findOrFail($id);
        abort_unless($me->hasAnyRole(['admin', 'area_admin']) || $doc->creator?->unit_id === $me->unit_id, 403, 'ไม่มีสิทธิ์กู้คืนเอกสารของหน่วยงานอื่น');
        $doc->restore();
        $doc->update(['destroyed_by' => null]);
        AuditLogger::log('restore', $doc, 'กู้คืนหนังสือ: '.$doc->title);

        return back()->with('success', 'กู้คืนเอกสารเรียบร้อยแล้ว');
    }
}

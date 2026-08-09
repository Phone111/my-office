<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\KrsIndicator;
use Modules\Core\Models\KrsReport;

/**
 * คำรับรองปฏิบัติราชการ KRS/ARS (ระบบเขต Phase 3)
 * krs_officer: กำหนดตัวชี้วัด + ผู้รายงาน + รับรายงาน · ผู้รายงาน: ส่งรายงานตามรอบ
 */
class KrsController extends Controller
{
    private function beYear(Request $request): int
    {
        $y = (int) ($request->input('year') ?: (now()->year + 543));

        return $y < 2500 ? $y + 543 : $y;
    }

    private function people()
    {
        return User::orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
    }

    /** หน้า krs_officer — จัดการตัวชี้วัด + สถานะรายงานตามรอบ */
    public function index(Request $request): Response
    {
        $year = $this->beYear($request);

        $indicators = KrsIndicator::with(['reporter:id,name', 'receiver:id,name', 'reports'])
            ->where('year', $year)
            ->orderBy('category')->orderBy('code')
            ->get()
            ->map(function (KrsIndicator $i) {
                $byRound = [];
                foreach (KrsIndicator::ROUNDS as $r) {
                    $rep = $i->reports->firstWhere('round', $r);
                    $byRound[$r] = $rep ? ['status' => $rep->status, 'has_file' => (bool) $rep->file_path, 'url' => $rep->file_path ? Storage::url($rep->file_path) : null, 'report_id' => $rep->id] : null;
                }

                return [
                    'id' => $i->id,
                    'category' => $i->category,
                    'category_label' => KrsIndicator::CATEGORIES[$i->category] ?? $i->category,
                    'code' => $i->code,
                    'name' => $i->name,
                    'reporter' => $i->reporter?->name,
                    'reporter_id' => $i->reporter_id,
                    'receiver' => $i->receiver?->name,
                    'receiver_id' => $i->receiver_id,
                    'is_active' => $i->is_active,
                    'rounds' => $byRound,
                ];
            });

        return Inertia::render('Core::Krs/Indicators', [
            'year' => $year,
            'indicators' => $indicators,
            'categories' => collect(KrsIndicator::CATEGORIES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'rounds' => KrsIndicator::ROUNDS,
            'people' => $this->people(),
        ]);
    }

    public function storeIndicator(Request $request): RedirectResponse
    {
        $v = $this->validateIndicator($request);
        KrsIndicator::create([...$v, 'created_by' => $request->user()->id]);

        return back()->with('success', 'เพิ่มตัวชี้วัดแล้ว');
    }

    public function updateIndicator(Request $request, KrsIndicator $indicator): RedirectResponse
    {
        $indicator->update($this->validateIndicator($request, $indicator->id));

        return back()->with('success', 'แก้ไขตัวชี้วัดแล้ว');
    }

    public function destroyIndicator(KrsIndicator $indicator): RedirectResponse
    {
        $indicator->delete();

        return back()->with('success', 'ลบตัวชี้วัดแล้ว');
    }

    /** มุมมองผู้รายงาน — ตัวชี้วัดที่ฉันรับผิดชอบ + ส่งรายงานตามรอบ */
    public function my(Request $request): Response
    {
        $year = $this->beYear($request);
        $rows = KrsIndicator::with('reports')
            ->where('year', $year)
            ->where('reporter_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('category')->orderBy('code')
            ->get()
            ->map(function (KrsIndicator $i) {
                $byRound = [];
                foreach (KrsIndicator::ROUNDS as $r) {
                    $rep = $i->reports->firstWhere('round', $r);
                    $byRound[$r] = $rep ? [
                        'status' => $rep->status,
                        'note' => $rep->note,
                        'has_file' => (bool) $rep->file_path,
                        'url' => $rep->file_path ? Storage::url($rep->file_path) : null,
                    ] : null;
                }

                return [
                    'id' => $i->id,
                    'category_label' => KrsIndicator::CATEGORIES[$i->category] ?? $i->category,
                    'code' => $i->code,
                    'name' => $i->name,
                    'rounds' => $byRound,
                ];
            });

        return Inertia::render('Core::Krs/MyReports', [
            'year' => $year,
            'rows' => $rows,
            'rounds' => KrsIndicator::ROUNDS,
        ]);
    }

    /** ผู้รายงานส่งรายงานตามรอบ */
    public function submit(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'indicator_id' => ['required', 'exists:krs_indicators,id'],
            'round' => ['required', 'in:6,9,12'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:20480'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $indicator = KrsIndicator::findOrFail($v['indicator_id']);
        abort_unless($indicator->reporter_id === $request->user()->id, 403);

        $report = KrsReport::firstOrNew(['indicator_id' => $indicator->id, 'round' => $v['round']]);
        if ($request->hasFile('file')) {
            if ($report->file_path) {
                Storage::disk('public')->delete($report->file_path);
            }
            $report->file_path = $request->file('file')->store('krs', 'public');
        }
        $report->reporter_id = $request->user()->id;
        $report->note = $v['note'] ?? null;
        $report->status = KrsReport::STATUS_SUBMITTED;
        $report->submitted_at = now();
        $report->save();

        return back()->with('success', 'ส่งรายงานตัวชี้วัดรอบ '.$v['round'].' เดือนแล้ว');
    }

    /** krs_officer/ผู้รับ — รับเอกสารรายงาน */
    public function receive(Request $request, KrsReport $report): RedirectResponse
    {
        // รับได้เฉพาะ: admin · ผู้รับที่กำหนดของตัวชี้วัด · (ถ้าตัวชี้วัดไม่ได้กำหนดผู้รับ = จนท.คำรับรองรับได้)
        $me = $request->user();
        $receiverId = $report->indicator?->receiver_id;
        abort_unless(
            $me->hasRole('admin') || $receiverId === null || $receiverId === $me->id,
            403,
            'รับรายงานได้เฉพาะผู้รับที่กำหนดของตัวชี้วัดนี้'
        );

        $report->update([
            'status' => KrsReport::STATUS_RECEIVED,
            'received_at' => now(),
            'received_by' => $me->id,
        ]);

        return back()->with('success', 'รับเอกสารรายงานแล้ว');
    }

    private function validateIndicator(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'category' => ['required', 'in:'.implode(',', array_keys(KrsIndicator::CATEGORIES))],
            'code' => ['required', 'string', 'max:50', Rule::unique('krs_indicators', 'code')->where(fn ($q) => $q->where('year', $request->input('year'))->where('category', $request->input('category')))->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:500'],
            'reporter_id' => ['nullable', 'exists:users,id'],
            'receiver_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);
    }
}

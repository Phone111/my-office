<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Supervision;
use Modules\Core\Models\SupervisionIndicator;
use Modules\Core\Models\SupervisionRound;
use Modules\Core\Models\SupervisionStandard;

/**
 * ตั้งค่ากรอบการนิเทศ (มาตรฐาน/ตัวชี้วัด/รอบ) + รายงานสรุประดับเขต
 */
class SupervisionFrameworkController extends Controller
{
    private function canManage(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin', 'supervisor']);
    }

    // ===== ตั้งค่า =====
    public function settings(Request $request): Response
    {
        abort_unless($this->canManage($request->user()), 403);

        return Inertia::render('Core::Supervision/Settings', [
            'standards' => SupervisionStandard::with('indicators')->orderBy('sort')->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'indicators' => $s->indicators->map(fn ($i) => [
                        'id' => $i->id, 'name' => $i->name, 'weight' => $i->weight,
                    ]),
                ]),
            'rounds' => SupervisionRound::orderByDesc('id')->get()
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'is_current' => $r->is_current]),
        ]);
    }

    public function storeStandard(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255', Rule::unique('supervision_standards', 'name')],
        ]);
        SupervisionStandard::create([
            'code' => $v['code'] ?? null,
            'name' => $v['name'],
            'sort' => (SupervisionStandard::max('sort') ?? 0) + 1,
        ]);

        return back()->with('success', 'เพิ่มมาตรฐานเรียบร้อย');
    }

    public function destroyStandard(Request $request, SupervisionStandard $standard): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $standard->delete();

        return back()->with('success', 'ลบมาตรฐานแล้ว');
    }

    public function storeIndicator(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'standard_id' => ['required', 'integer', 'exists:supervision_standards,id'],
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        SupervisionIndicator::create([
            'standard_id' => $v['standard_id'],
            'name' => $v['name'],
            'weight' => $v['weight'] ?? 1,
            'sort' => (SupervisionIndicator::where('standard_id', $v['standard_id'])->max('sort') ?? 0) + 1,
        ]);

        return back()->with('success', 'เพิ่มตัวชี้วัดเรียบร้อย');
    }

    public function destroyIndicator(Request $request, SupervisionIndicator $indicator): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $indicator->delete();

        return back()->with('success', 'ลบตัวชี้วัดแล้ว');
    }

    public function storeRound(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['nullable', 'integer', 'min:2400', 'max:2700'],
            'semester' => ['nullable', 'integer', 'in:1,2'],
        ]);
        SupervisionRound::create($v);

        return back()->with('success', 'เพิ่มรอบการนิเทศเรียบร้อย');
    }

    public function setCurrentRound(Request $request, SupervisionRound $round): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        SupervisionRound::where('is_current', true)->update(['is_current' => false]);
        $round->update(['is_current' => true]);

        return back()->with('success', 'ตั้งเป็นรอบปัจจุบันแล้ว');
    }

    public function destroyRound(Request $request, SupervisionRound $round): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $round->delete();

        return back()->with('success', 'ลบรอบการนิเทศแล้ว');
    }

    // ===== รายงานสรุประดับเขต =====
    public function report(Request $request): Response
    {
        abort_unless($this->canManage($request->user()), 403);
        $user = $request->user();

        $rounds = SupervisionRound::orderByDesc('id')->get(['id', 'name', 'is_current']);
        // 'all' = ทุกรอบ (รวมที่ไม่ระบุรอบ)
        $roundParam = $request->input('round', $rounds->firstWhere('is_current', true)?->id ?? $rounds->first()?->id ?? 'all');
        $roundId = $roundParam === 'all' ? 'all' : (int) $roundParam;

        $sups = Supervision::with(['school:id,name', 'scores'])
            ->when($roundId !== 'all', fn ($q) => $q->where('round_id', $roundId))
            ->where(fn ($q) => $q->where('area_unit_id', $user->unit_id)->orWhereNull('area_unit_id'))
            ->get();

        // map ตัวชี้วัด -> มาตรฐาน เพื่อจัดกลุ่ม
        $indStd = SupervisionIndicator::pluck('standard_id', 'id');
        $standards = SupervisionStandard::orderBy('sort')->get(['id', 'name']);

        // สรุปต่อโรงเรียน
        $bySchool = $sups->map(function ($s) {
            $q = $s->scores->whereNotNull('quality')->pluck('quality');

            return [
                'id' => $s->id,
                'school' => $s->school?->name,
                'avg' => $q->count() ? round($q->avg(), 2) : null,
                'scored' => $s->scores->count(),
                'status' => $s->status,
                'status_label' => Supervision::STATUSES[$s->status] ?? $s->status,
            ];
        })->values();

        // สรุปต่อมาตรฐาน (เฉลี่ยข้ามทุกโรงเรียนในรอบ)
        $allScores = $sups->flatMap(fn ($s) => $s->scores);
        $byStandard = $standards->map(function ($std) use ($allScores, $indStd) {
            $vals = $allScores->filter(fn ($sc) => ($indStd[$sc->indicator_id] ?? null) === $std->id && $sc->quality !== null)->pluck('quality');

            return [
                'standard' => $std->name,
                'avg' => $vals->count() ? round($vals->avg(), 2) : null,
                'count' => $vals->count(),
            ];
        });

        $allQ = $allScores->whereNotNull('quality')->pluck('quality');

        return Inertia::render('Core::Supervision/Report', [
            'rounds' => $rounds,
            'selectedRound' => $roundId,
            'bySchool' => $bySchool,
            'byStandard' => $byStandard,
            'overall' => [
                'schools' => $sups->count(),
                'avg' => $allQ->count() ? round($allQ->avg(), 2) : null,
            ],
            'qualityLabels' => Supervision::QUALITY,
        ]);
    }
}

<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Attendance\Models\Attendance;

/**
 * บันทึกผู้ไม่ลงเวลาวันนี้ (เจ้าหน้าที่วันลา) — ระบุสถานะ/เหตุผลของคนที่ไม่ได้ลงเวลา
 */
class AbsenceController extends Controller
{
    public function form(Request $request): Response
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $records = Attendance::whereDate('date', $date)->get()->keyBy('user_id');
        $clockedIds = $records
            ->filter(fn (Attendance $a) => in_array($a->status, Attendance::CLOCKED_STATUSES, true))
            ->keys()->all();

        // เฉพาะบุคลากรในหน่วยงานตน (admin/area_admin เห็นทุกหน่วยงาน)
        $overseer = $request->user()->hasAnyRole(['admin', 'area_admin']);
        $absentees = User::with('position:id,name')
            ->whereNotIn('id', $clockedIds ?: [0])
            ->when(! $overseer, fn ($q) => $q->where('unit_id', $request->user()->unit_id))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'position' => $u->position?->name,
                'status' => $records[$u->id]->status ?? '',
                'note' => $records[$u->id]->note ?? '',
            ]);

        return Inertia::render('Attendance::AbsenceRecord', [
            'date' => $date->toDateString(),
            'absentees' => $absentees,
            'statuses' => collect(Attendance::ABSENCE_STATUSES)
                ->map(fn ($s) => ['value' => $s, 'label' => Attendance::STATUS_LABELS[$s]])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'records' => ['array'],
            'records.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['nullable', Rule::in(Attendance::ABSENCE_STATUSES)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $saved = 0;

        // จำกัดให้บันทึกได้เฉพาะบุคลากรในหน่วยงานตน (admin/area_admin บันทึกได้ทุกหน่วยงาน)
        $overseer = $request->user()->hasAnyRole(['admin', 'area_admin']);
        $allowedIds = $overseer ? null : User::where('unit_id', $request->user()->unit_id)->pluck('id')->flip();

        foreach ($validated['records'] ?? [] as $r) {
            if ($allowedIds !== null && ! $allowedIds->has($r['user_id'])) {
                continue; // ข้ามผู้ใช้ต่างหน่วยงาน
            }
            if (empty($r['status'])) {
                // ไม่ได้เลือกสถานะ → ลบรายการที่เคยบันทึกไว้ (เฉพาะที่เป็นการบันทึกไม่ลงเวลา)
                Attendance::where('user_id', $r['user_id'])
                    ->whereDate('date', $date)
                    ->whereIn('status', Attendance::ABSENCE_STATUSES)
                    ->delete();

                continue;
            }

            Attendance::updateOrCreate(
                ['user_id' => $r['user_id'], 'date' => $date],
                [
                    'status' => $r['status'],
                    'note' => $r['note'] ?? null,
                    'check_in_time' => null,
                    'recorded_by' => $request->user()->id,
                ],
            );
            $saved++;
        }

        return back()->with('success', "บันทึกผู้ไม่ลงเวลา {$saved} รายการเรียบร้อยแล้ว");
    }
}

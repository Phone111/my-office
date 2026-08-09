<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\OfficialTrip;
use Modules\Leave\Models\WorkAttendance;

/**
 * ลงเวลาปฏิบัติราชการ — เจ้าหน้าที่บันทึกการมาปฏิบัติราชการรายวัน + รายงานรายวัน/รอบเดือน
 */
class AttendanceController extends Controller
{
    /** หน้าบันทึกข้อมูล (เจ้าหน้าที่) — วันนี้หรือย้อนหลัง */
    public function entry(Request $request): Response
    {
        $date = $this->parseDate($request->query('date'));
        $existing = WorkAttendance::where('work_date', $date->toDateString())
            ->get()->keyBy('user_id');
        $auto = $this->autoStatusFor($date);

        $rows = $this->personnel()->map(fn (User $u) => [
            'user_id' => $u->id,
            'name' => $u->name,
            'group' => $u->group?->name,
            'status' => $existing->get($u->id)?->status ?? $auto->get($u->id) ?? 'present',
            'note' => $existing->get($u->id)?->note,
            'saved' => $existing->has($u->id),
        ])->values();

        return Inertia::render('Leave::Attendance/Entry', [
            'rows' => $rows,
            'statuses' => WorkAttendance::STATUSES,
            'date' => $date->toDateString(),
            'dateThai' => $this->thai($date),
            'isToday' => $date->isToday(),
        ]);
    }

    /** บันทึกการลงเวลา (เจ้าหน้าที่) */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', 'string', 'in:'.implode(',', array_keys(WorkAttendance::STATUSES))],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $date = Carbon::parse($data['work_date'])->toDateString();

        // ลงเวลาได้เฉพาะบุคลากรในหน่วยงานตน (admin/เขต ลงได้ทุกหน่วยงาน)
        $me = $request->user();
        $overseer = $me->hasAnyRole(['admin', 'area_admin']);
        $allowed = $overseer ? null : User::where('unit_id', $me->unit_id)->pluck('id')->all();

        foreach ($data['records'] as $r) {
            if ($allowed !== null && ! in_array((int) $r['user_id'], $allowed, true)) {
                continue; // ข้ามคนนอกหน่วยงาน
            }
            WorkAttendance::updateOrCreate(
                ['user_id' => $r['user_id'], 'work_date' => $date],
                ['status' => $r['status'], 'note' => $r['note'] ?? null, 'recorded_by' => $me->id],
            );
        }

        return back()->with('success', 'บันทึกการปฏิบัติราชการวันที่ '.$date.' เรียบร้อยแล้ว ('.count($data['records']).' คน)');
    }

    /** รายงานสรุปรายวัน (ทุกคนดูได้) */
    public function dailyReport(Request $request): Response
    {
        $date = $this->parseDate($request->query('date'));
        $records = WorkAttendance::where('work_date', $date->toDateString())->get()->keyBy('user_id');
        $auto = $this->autoStatusFor($date);

        $rows = $this->personnel()->map(function (User $u) use ($records, $auto) {
            $rec = $records->get($u->id);
            $status = $rec?->status ?? $auto->get($u->id);

            return [
                'name' => $u->name,
                'group' => $u->group?->name,
                'status' => $status,
                'label' => $status ? WorkAttendance::label($status) : null,
                'note' => $rec?->note,
                'recorded' => $rec !== null,
            ];
        })->values();

        return Inertia::render('Leave::Attendance/Daily', [
            'rows' => $rows,
            'statuses' => WorkAttendance::STATUSES,
            'date' => $date->toDateString(),
            'dateThai' => $this->thai($date),
            'summary' => $this->countByStatus($rows->pluck('status')),
        ]);
    }

    /** รายงานสรุปรอบเดือน (ทุกคนดูได้) */
    public function monthlyReport(Request $request): Response
    {
        $month = (int) ($request->query('month') ?: Carbon::now()->month);
        $year = (int) ($request->query('year') ?: Carbon::now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();
        $days = (int) $end->day;

        // ดึงทั้งเดือนทีเดียวแล้ว group ตาม user + วันที่
        $records = WorkAttendance::whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($g) => $g->keyBy(fn ($r) => (int) Carbon::parse($r->work_date)->day));

        $rows = $this->personnel()->map(function (User $u) use ($records, $days) {
            $byDay = $records->get($u->id) ?? collect();
            $cells = [];
            $counts = array_fill_keys(array_keys(WorkAttendance::STATUSES), 0);
            for ($d = 1; $d <= $days; $d++) {
                $status = $byDay->get($d)?->status;
                $cells[$d] = $status ? WorkAttendance::ABBR[$status] : '';
                if ($status) {
                    $counts[$status]++;
                }
            }

            return ['name' => $u->name, 'group' => $u->group?->name, 'cells' => $cells, 'counts' => $counts];
        })->values();

        return Inertia::render('Leave::Attendance/Monthly', [
            'rows' => $rows,
            'days' => $days,
            'month' => $month,
            'year' => $year,
            'yearThai' => $year + 543,
            'monthName' => $start->locale('th')->translatedFormat('F'),
            'abbr' => WorkAttendance::ABBR,
            'statuses' => WorkAttendance::STATUSES,
        ]);
    }

    /* ---------- helpers ---------- */

    /** บุคลากรทั้งหมด (ยกเว้น admin) เรียงตามกลุ่ม */
    private function personnel(): Collection
    {
        return User::with('group:id,name')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'system_admin'))
            ->orderBy('group_id')
            ->orderBy('name')
            ->get();
    }

    /** สถานะอัตโนมัติจากใบลา/ไปราชการที่อนุมัติแล้ว (ใช้เป็นค่าเริ่มต้น) */
    private function autoStatusFor(Carbon $date): Collection
    {
        $d = $date->toDateString();
        $start = (clone $date)->startOfDay();
        $end = (clone $date)->endOfDay();

        $status = collect();

        OfficialTrip::where('status', 'approved')
            ->where('depart_at', '<=', $end)->where('return_at', '>=', $start)
            ->pluck('user_id')->each(fn ($id) => $status->put($id, 'trip'));

        LeaveRequest::with('leaveType:id,code')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $d)->whereDate('end_date', '>=', $d)
            ->get()->each(function (LeaveRequest $l) use ($status) {
                $status->put($l->user_id, match ($l->leaveType?->code) {
                    'sick' => 'sick',
                    'personal' => 'personal',
                    'maternity' => 'maternity',
                    default => 'other_leave',
                });
            });

        return $status;
    }

    /** นับจำนวนต่อสถานะ (เฉพาะที่มีค่า) */
    private function countByStatus(Collection $statuses): array
    {
        $counts = array_fill_keys(array_keys(WorkAttendance::STATUSES), 0);
        foreach ($statuses as $s) {
            if ($s && isset($counts[$s])) {
                $counts[$s]++;
            }
        }

        return $counts;
    }

    private function parseDate(?string $raw): Carbon
    {
        try {
            return $raw ? Carbon::parse($raw)->startOfDay() : Carbon::now()->startOfDay();
        } catch (\Throwable) {
            return Carbon::now()->startOfDay();
        }
    }

    private function thai(Carbon $d): string
    {
        return $d->locale('th')->translatedFormat('j F').' '.($d->year + 543);
    }
}

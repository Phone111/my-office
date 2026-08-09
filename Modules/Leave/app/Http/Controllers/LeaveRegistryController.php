<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveType;

/**
 * ทะเบียนลา (ส่วนกลาง) — รายชื่อบุคลากรทั้งหมด กด "ดูใบลา" เพื่อดูใบลารายคน
 * สำหรับเจ้าหน้าที่วันลา / เลขาฯ / ผู้บริหาร
 */
class LeaveRegistryController extends Controller
{
    public function index(Request $request): Response
    {
        // ช่วงปีงบประมาณปัจจุบัน (1 ต.ค.–30 ก.ย.) สำหรับสรุปสถิติพิมพ์
        $today = Carbon::today();
        $ceEnd = $today->month >= 10 ? $today->year + 1 : $today->year;
        $fyStart = Carbon::create($ceEnd - 1, 10, 1)->startOfDay();
        $fyEnd = Carbon::create($ceEnd, 9, 30)->endOfDay();
        $be = $ceEnd + 543;

        // ประเภทการลาจริง — ดัน "ลาพักผ่อน" ไว้ท้ายสุด (มีคอลัมน์คงเหลือ)
        $types = LeaveType::where('name', 'not like', '%ราชการ%')
            ->get(['id', 'name', 'default_days'])
            ->sortBy(fn (LeaveType $t) => str_contains($t->name, 'พักผ่อน') ? 99 : $t->id)
            ->values();

        $agg = LeaveRequest::where('status', 'approved')
            ->whereBetween('start_date', [$fyStart, $fyEnd])
            ->selectRaw('user_id, leave_type_id, count(*) as times, sum(total_days) as days')
            ->groupBy('user_id', 'leave_type_id')
            ->get()
            ->groupBy('user_id');

        // สิทธิวันลาพักผ่อนรายคน (ปีงบฯ นี้) — ใช้ entitled_days ถ้ามี ไม่งั้นใช้ default
        $vacationTypeIds = $types->filter(fn (LeaveType $t) => str_contains($t->name, 'พักผ่อน'))->pluck('id');
        $balances = \Modules\Leave\Models\LeaveBalance::where('year', $be)
            ->whereIn('leave_type_id', $vacationTypeIds)
            ->get()
            ->keyBy('user_id');

        // จำกัดทะเบียนการลาเฉพาะหน่วยงานตน (admin/area_admin เห็นทุกหน่วยงาน)
        $overseer = $request->user()->hasAnyRole(['admin', 'area_admin']);
        $users = User::with(['position:id,name', 'group:id,name', 'department:id,name'])
            ->when(! $overseer, fn ($q) => $q->where('unit_id', $request->user()->unit_id))
            ->orderBy('name')
            ->get();

        $people = $users->map(function (User $u) use ($types, $agg, $balances) {
            $rows = $agg->get($u->id) ?? collect();
            $byType = [];
            $vacationRemaining = null;

            foreach ($types as $t) {
                $r = $rows->firstWhere('leave_type_id', $t->id);
                $days = $r ? (float) $r->days : 0.0;
                $byType[$t->id] = ['times' => $r ? (int) $r->times : 0, 'days' => $days];

                // คงเหลือ = สิทธิลาพักผ่อนรายคน (entitled) - ที่ใช้ไป
                if (str_contains($t->name, 'พักผ่อน') && $t->default_days > 0) {
                    $entitled = (float) ($balances->get($u->id)->entitled_days ?? $t->default_days);
                    $vacationRemaining = max($entitled - $days, 0);
                }
            }

            return [
                'id' => $u->id,
                'name' => $u->name,
                'position' => $u->position?->name,
                'affiliation' => $u->group?->name ?? $u->department?->name,
                'byType' => $byType,
                'remaining' => $vacationRemaining,
            ];
        });

        return Inertia::render('Leave::LeaveRegistry', [
            'people' => $people,
            'types' => $types->map(fn (LeaveType $t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'fiscalYear' => $be,
            'school' => 'โรงเรียนเศรษฐบุตรบำเพ็ญ',
        ]);
    }

    /** วันที่แบบไทย เช่น 17 มิ.ย. 2569 */
    private function thaiDate(?\Illuminate\Support\Carbon $d): ?string
    {
        if (! $d) {
            return null;
        }
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $d->day.' '.$months[$d->month].' '.($d->year + 543);
    }

    public function show(Request $request, User $user): Response
    {
        // ดูใบลาได้เฉพาะบุคลากรในหน่วยงานตน (admin/area_admin ดูได้ทุกหน่วยงาน)
        $me = $request->user();
        abort_unless($me->hasAnyRole(['admin', 'area_admin']) || (int) $user->unit_id === (int) $me->unit_id, 403, 'ดูใบลาได้เฉพาะบุคลากรในหน่วยงานท่าน');

        $leaves = LeaveRequest::with('leaveType:id,name')
            ->where('user_id', $user->id)
            // เฉพาะการลาจริง — ตัดประเภท "ไปราชการ" ออก (อยู่ในระบบไปราชการแยก)
            ->whereHas('leaveType', fn ($q) => $q->where('name', 'not like', '%ราชการ%'))
            ->latest('start_date')
            ->get()
            ->map(function (LeaveRequest $l) {
                $start = $this->thaiDate($l->start_date);
                $end = $this->thaiDate($l->end_date);

                return [
                    'id' => $l->id,
                    'type' => $l->leaveType?->name,
                    'subject' => 'ขอ'.($l->leaveType?->name ?? 'ลา'),
                    'date_range' => $end && $end !== $start ? "{$start} - {$end}" : $start,
                    'days' => $l->total_days,
                    'reason' => $l->reason,
                    'status' => $l->status,
                ];
            });

        return Inertia::render('Leave::LeaveRegistryPerson', [
            'person' => [
                'name' => $user->name,
                'position' => $user->position?->name,
                'affiliation' => $user->group?->name ?? $user->department?->name,
            ],
            'leaves' => $leaves,
        ]);
    }
}

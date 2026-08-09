<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\OfficialTrip;

/**
 * การมาปฏิบัติราชการวันนี้ของบุคลากร — รายชื่อบุคลากรทั้งหมด + สถานะวันนี้ (ไปราชการ/ลา/ปกติ)
 */
class DailyStatusController extends Controller
{
    public function index(): Response
    {
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();
        $today = $start->toDateString();

        $thai = fn ($d) => $d ? $d->locale('th')->translatedFormat('j M').' '.($d->year + 543) : null;

        // ผู้ที่ไปราชการ/ลา วันนี้ (อนุมัติแล้ว) — map ตาม user
        $trips = OfficialTrip::where('status', 'approved')
            ->where('depart_at', '<=', $end)
            ->where('return_at', '>=', $start)
            ->get()
            ->keyBy('user_id');

        $leaves = LeaveRequest::with('leaveType:id,name')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->keyBy('user_id');

        // บุคลากรทั้งหมด (ยกเว้น admin) จัดเรียงตามกลุ่ม
        $users = User::with('group:id,name')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'system_admin'))
            ->orderBy('group_id')
            ->orderBy('name')
            ->get();

        $rows = $users->map(function (User $u) use ($trips, $leaves, $thai) {
            if ($t = $trips->get($u->id)) {
                return ['name' => $u->name, 'group' => $u->group?->name, 'status' => 'ไปราชการ', 'kind' => 'trip',
                    'detail' => $t->destination, 'start' => $thai($t->depart_at), 'end' => $thai($t->return_at)];
            }
            if ($l = $leaves->get($u->id)) {
                return ['name' => $u->name, 'group' => $u->group?->name, 'status' => $l->leaveType?->name ?? 'ลา', 'kind' => 'leave',
                    'detail' => null, 'start' => $thai($l->start_date), 'end' => $thai($l->end_date)];
            }

            return ['name' => $u->name, 'group' => $u->group?->name, 'status' => 'ปฏิบัติงานปกติ', 'kind' => 'normal',
                'detail' => null, 'start' => null, 'end' => null];
        })->values();

        return Inertia::render('Leave::DailyStatus', [
            'rows' => $rows,
            'today' => $thai(Carbon::now()),
            'summary' => [
                'total' => $rows->count(),
                'trip' => $rows->where('kind', 'trip')->count(),
                'leave' => $rows->where('kind', 'leave')->count(),
                'normal' => $rows->where('kind', 'normal')->count(),
            ],
        ]);
    }
}

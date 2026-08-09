<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\OfficialTrip;

/**
 * สถิติการไปราชการของบุคลากร — สรุปจำนวนครั้งรายเดือน (รายคน × 12 เดือน)
 */
class TripStatisticsController extends Controller
{
    public function index(Request $request): Response
    {
        // ปี พ.ศ. (ปฏิทิน ม.ค.–ธ.ค.) — กันค่าเก่าที่เป็น ค.ศ.
        $beYear = (int) ($request->input('year') ?: (now()->year + 543));
        if ($beYear < 2500) {
            $beYear += 543;
        }
        $ceYear = $beYear - 543;

        $trips = OfficialTrip::with(['user:id,name,position_id', 'user.position:id,name'])
            ->where('status', OfficialTrip::STATUS_APPROVED)
            ->whereYear('depart_at', $ceYear)
            ->get(['id', 'user_id', 'depart_at']);

        $rows = $trips->groupBy('user_id')
            ->map(function ($group) {
                $u = $group->first()->user;
                $months = array_fill(1, 12, 0);
                foreach ($group as $t) {
                    if ($t->depart_at) {
                        $months[(int) $t->depart_at->month]++;
                    }
                }

                return [
                    'name' => $u?->name,
                    'position' => $u?->position?->name,
                    'months' => $months,
                    'total' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // ยอดรวมรายเดือน (ทุกคน)
        $monthTotals = array_fill(1, 12, 0);
        foreach ($rows as $r) {
            foreach ($r['months'] as $m => $c) {
                $monthTotals[$m] += $c;
            }
        }

        return Inertia::render('Leave::TripStatistics', [
            'year' => $beYear,
            'rows' => $rows,
            'monthTotals' => $monthTotals,
            'grandTotal' => $trips->count(),
            'people' => $rows->count(),
        ]);
    }
}

<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\OfficialTrip;
use Modules\Saraban\Models\Document;

/**
 * ผลการปฏิบัติงานของกลุ่ม/บุคลากร — สรุปปริมาณงาน (เอกสาร/ลา/ไปราชการ) ต่อคน
 * หัวหน้ากลุ่ม/ธุรการกลุ่ม เห็นเฉพาะกลุ่มตัวเอง · ผู้บริหาร/เลขาฯ เห็นทั้งหมด
 */
class PerformanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $beYear = (int) ($request->input('year') ?: (now()->year + 543));
        if ($beYear < 2500) {
            $beYear += 543;
        }
        $ce = $beYear - 543;

        // ขอบเขต: กลุ่มของตัวเอง (หัวหน้ากลุ่ม/ธุรการ) หรือทั้งหมด (ผู้บริหาร/เลขาฯ/admin)
        $seeAll = $user->hasAnyRole(['director', 'deputy_director', 'secretary', 'admin']);
        $members = $seeAll
            ? User::with('position:id,name')->orderBy('name')->get()
            : User::with('position:id,name')->where('group_id', $user->group_id ?: 0)->orderBy('name')->get();
        $ids = $members->pluck('id');

        // นับเอกสารต่อคน แยกหมวด
        $docs = Document::whereIn('creator_id', $ids)
            ->whereYear('created_at', $ce)
            ->selectRaw('creator_id, category, count(*) as c')
            ->groupBy('creator_id', 'category')
            ->get()
            ->groupBy('creator_id');

        $leaves = LeaveRequest::whereIn('user_id', $ids)
            ->whereYear('start_date', $ce)
            ->where('status', '!=', 'draft')
            ->selectRaw('user_id, count(*) as c')->groupBy('user_id')->pluck('c', 'user_id');

        $trips = OfficialTrip::whereIn('user_id', $ids)
            ->whereYear('depart_at', $ce)
            ->where('status', '!=', 'draft')
            ->selectRaw('user_id, count(*) as c')->groupBy('user_id')->pluck('c', 'user_id');

        $catCount = function ($row, array $cats) {
            return collect($cats)->sum(fn ($c) => (int) ($row->firstWhere('category', $c)->c ?? 0));
        };

        $rows = $members->map(function (User $u) use ($docs, $leaves, $trips, $catCount) {
            $d = $docs->get($u->id) ?? collect();
            $memo = $catCount($d, [Document::CATEGORY_MEMO, Document::CATEGORY_REPORT]);
            $send = $catCount($d, [Document::CATEGORY_OUTGOING, Document::CATEGORY_INTERNAL_OUT, Document::CATEGORY_GENERAL_OUT]);
            $recv = $catCount($d, [Document::CATEGORY_INCOMING, Document::CATEGORY_INTERNAL_IN, Document::CATEGORY_GENERAL_IN]);
            $order = $catCount($d, [Document::CATEGORY_ORDER]);
            $leave = (int) ($leaves[$u->id] ?? 0);
            $trip = (int) ($trips[$u->id] ?? 0);

            return [
                'name' => $u->name,
                'position' => $u->position?->name,
                'memo' => $memo,
                'send' => $send,
                'recv' => $recv,
                'order' => $order,
                'leave' => $leave,
                'trip' => $trip,
                'total' => $memo + $send + $recv + $order + $leave + $trip,
            ];
        })->sortByDesc('total')->values();

        return Inertia::render('Core::Reports/Performance', [
            'year' => $beYear,
            'rows' => $rows,
            'groupName' => $seeAll ? null : $user->group?->name,
        ]);
    }
}

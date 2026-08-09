<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Attendance\Models\Attendance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveRequestRoute;
use Modules\Saraban\Models\DocumentRoute;

class DirectorDashboardController extends Controller
{
    /**
     * แดชบอร์ดผู้บริหาร — เน้นงานที่รอผู้บริหารดำเนินการ + สรุปการลงเวลา
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $totalStaff = User::count();

        // ลงเวลาวันนี้
        $checkedInToday = Attendance::whereDate('date', $today)->count();
        $lateToday = Attendance::whereDate('date', $today)->where('status', 'late')->count();
        $notCheckedIn = max($totalStaff - $checkedInToday, 0);

        // ลา/ไปราชการ วันนี้ (จากใบลาที่อนุมัติแล้ว)
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->count();

        // งานที่ "รอฉันอนุมัติ"
        $myPendingDocs = DocumentRoute::where('approver_id', $user->id)
            ->where('status', DocumentRoute::STATUS_PENDING)->count();
        $myPendingLeave = LeaveRequestRoute::where('approver_id', $user->id)
            ->where('status', 'pending')->count();

        // เอกสารที่รอฉันอนุมัติ (รายการประกอบ)
        $pendingList = DocumentRoute::with('document:id,title')
            ->where('approver_id', $user->id)
            ->where('status', DocumentRoute::STATUS_PENDING)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (DocumentRoute $r) => [
                'id' => $r->document_id,
                'title' => $r->document?->title,
                'step_order' => $r->step_order,
            ]);

        return Inertia::render('Core::DirectorDashboard', [
            'stats' => [
                'checkedInToday' => $checkedInToday,
                'lateToday' => $lateToday,
                'onLeaveToday' => $onLeaveToday,
                'notCheckedIn' => $notCheckedIn,
                'totalStaff' => $totalStaff,
                'myPendingDocs' => $myPendingDocs,
                'myPendingLeave' => $myPendingLeave,
            ],
            'today' => $todayStr,
            'pendingList' => $pendingList,
        ]);
    }
}

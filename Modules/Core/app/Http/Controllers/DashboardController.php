<?php

namespace Modules\Core\Http\Controllers;
use Modules\Leave\Models\LeaveRequest;
use Modules\Booking\Models\Booking;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Announcement\Models\News;
use Modules\Attendance\Models\Attendance;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentRoute;

class DashboardController extends Controller
{
        /**
         * หน้าแรกหลังเข้าสู่ระบบ — สรุปข้อมูลของผู้ใช้
         */
        public function index(Request $request): Response|RedirectResponse
        {

            $user = $request->user();

            // ผู้ดูแลระบบ
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.home');
            }

            // ผู้อำนวยการ
            if ($user->hasRole('director')) {
                return redirect()->route('director.dashboard');
            }

            $today = Carbon::today();

            /*
            |--------------------------------------------------------------------------
            | ลงเวลาวันนี้
            |--------------------------------------------------------------------------
            */

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first(['check_in_time', 'status']);       

            /*
            |--------------------------------------------------------------------------
            | เอกสารรออนุมัติ
            |--------------------------------------------------------------------------
            */

            $pendingApprovals = DocumentRoute::where('approver_id', $user->id)
                ->where('status', DocumentRoute::STATUS_PENDING)
                ->count();

            /*
            |--------------------------------------------------------------------------
            | เอกสารของฉัน
            |--------------------------------------------------------------------------
            */

            $myPendingDocs = Document::where('creator_id', $user->id)
                ->where('status', Document::STATUS_PENDING)
                ->count();
/*
|--------------------------------------------------------------------------
| งานของฉัน
|--------------------------------------------------------------------------
*/

// คำขอลาของฉันที่กำลังดำเนินการ
$pendingLeaves = LeaveRequest::where('user_id', $user->id)
    ->where('status', LeaveRequest::STATUS_PENDING)
    ->count();

// งานจองรถ/ห้องประชุมที่กำลังดำเนินการ
$pendingBookings = Booking::where('user_id', $user->id)
    ->whereIn('status', [
        Booking::STATUS_PENDING,
        Booking::STATUS_SUBMITTED,
        Booking::STATUS_ASSIGNED,
    ])
    ->count();

// แจ้งเตือนที่ยังไม่ได้อ่าน
$unreadNotifications = $user->unreadNotifications()->count();
            /*
            |--------------------------------------------------------------------------
            | ข่าวสารล่าสุด
            |--------------------------------------------------------------------------
            */

            $recentNews = News::with('creator:id,name')
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (News $n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'creator' => $n->creator?->name,
                    'created_at' => $n->created_at->diffForHumans(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | ผู้บริหารปฏิบัติราชการ
            |--------------------------------------------------------------------------
            */

            $dutyExecutives = User::where('duty_active', true)
                ->with(['position:id,name', 'roles:id,name'])
                ->orderBy('duty_order')
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'position' => $u->position?->name,
                    'duty_label' => $u->roles->contains('name', 'director')
                        ? 'ปฏิบัติราชการ'
                        : 'รักษาราชการแทน',
                    'is_director' => $u->roles->contains('name', 'director'),
                ])
                ->sortByDesc('is_director')
                ->values();

            /*
            |--------------------------------------------------------------------------
            | กิจกรรมล่าสุด
            |--------------------------------------------------------------------------
            */

            $recentActivities = collect();

            // ลงเวลา
            if ($attendance) {
                $recentActivities->push([
                    'time'  => substr((string) $attendance->check_in_time, 0, 5),
                    'title' => 'ลงเวลาเข้างาน',
                    'color' => 'bg-emerald-500',
                ]);
            }

            // เอกสารของฉัน
            if ($myPendingDocs > 0) {
                $recentActivities->push([
                    'time'  => now()->format('H:i'),
                    'title' => "มีเอกสารของฉัน {$myPendingDocs} รายการ",
                    'color' => 'bg-violet-500',
                ]);
            }

            // เอกสารรออนุมัติ
            if ($pendingApprovals > 0) {
                $recentActivities->push([
                    'time'  => now()->format('H:i'),
                    'title' => "มีเอกสารรออนุมัติ {$pendingApprovals} รายการ",
                    'color' => 'bg-amber-500',
                ]);
            }

            // ข่าวล่าสุด
            if ($recentNews->count() > 0) {
                $recentActivities->push([
                    'time'  => now()->format('H:i'),
                    'title' => 'มีข่าวประชาสัมพันธ์ใหม่',
                    'color' => 'bg-sky-500',
                ]);
            }

            // ถ้ายังไม่มีกิจกรรมเลย
            if ($recentActivities->isEmpty()) {
                $recentActivities->push([
                    'time'  => now()->format('H:i'),
                    'title' => 'ยังไม่มีกิจกรรมในวันนี้',
                    'color' => 'bg-gray-400',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | ส่งข้อมูลไป Dashboard
            |--------------------------------------------------------------------------
            */

return Inertia::render('Dashboard', [
    'today' => $today->toDateString(),

    'attendanceToday' => $attendance,

    'pendingApprovals' => $pendingApprovals,

    'myPendingDocs' => $myPendingDocs,

    'pendingLeaves' => $pendingLeaves,

    'pendingBookings' => $pendingBookings,

    'unreadNotifications' => $unreadNotifications,

    'recentNews' => $recentNews,

    'dutyExecutives' => $dutyExecutives,

    'recentActivities' => $recentActivities,
]);
        }
    }
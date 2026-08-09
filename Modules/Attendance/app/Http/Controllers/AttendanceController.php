<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Attendance\Models\Attendance;

class AttendanceController extends Controller
{
    /**
     * เวลาเข้างานปกติ (หลังเวลานี้ถือว่ามาสาย)
     */
    private const LATE_AFTER = '08:30:00';

    /**
     * หน้าลงชื่อเข้างาน พร้อมสถานะของวันนี้และประวัติล่าสุด
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $history = Attendance::where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(7)
            ->get(['id', 'date', 'check_in_time', 'check_out_time', 'status']);

        return Inertia::render('Attendance::CheckIn', [
            'today' => $today->toDateString(),
            'todayAttendance' => $todayAttendance,
            'history' => $history,
        ]);
    }

    /**
     * บันทึกเวลาเข้างานด้วย timestamp ปัจจุบัน
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->toDateString();

        // ป้องกันการลงเวลาซ้ำในวันเดียวกัน
        $already = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->exists();

        if ($already) {
            return back()->with('error', 'คุณได้ลงชื่อเข้างานของวันนี้ไปแล้ว');
        }

        $status = $now->format('H:i:s') > self::LATE_AFTER ? 'late' : 'present';

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in_time' => $now->format('H:i:s'),
            'status' => $status,
        ]);

        $message = $status === 'late'
            ? 'ลงชื่อเข้างานสำเร็จ (มาสาย) เวลา '.$now->format('H:i')
            : 'ลงชื่อเข้างานสำเร็จ เวลา '.$now->format('H:i');

        return back()->with('success', $message);
    }

    /**
     * บันทึกเวลาเลิกงานด้วย timestamp ปัจจุบัน
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->first();

        if (! $attendance) {
            return back()->with('error', 'คุณยังไม่ได้ลงชื่อเข้างานของวันนี้');
        }

        if ($attendance->check_out_time) {
            return back()->with('error', 'คุณได้ลงเวลาเลิกงานของวันนี้ไปแล้ว');
        }

        $attendance->update(['check_out_time' => $now->format('H:i:s')]);

        return back()->with('success', 'ลงเวลาเลิกงานสำเร็จ เวลา '.$now->format('H:i'));
    }

    /**
     * สมุดลงเวลาของฉัน — ประวัติการลงเวลาปฏิบัติราชการของผู้ใช้เอง
     */
    public function myLog(Request $request): Response
    {
        $user = $request->user()->load('position:id,name');
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        $records = Attendance::where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('check_in_time')
            ->get();

        $rows = $records->map(fn (Attendance $a) => [
            'date' => $a->date ? $a->date->day.' '.$months[$a->date->month].' '.($a->date->year + 543) : null,
            'status' => $a->status,
            'status_label' => Attendance::STATUS_LABELS[$a->status] ?? $a->status,
            'check_in' => $a->check_in_time ? substr($a->check_in_time, 0, 5) : null,
            'check_out' => $a->check_out_time ? substr($a->check_out_time, 0, 5) : null,
        ]);

        // สรุปยอดแยกตามสถานะ
        $summary = collect(Attendance::STATUS_LABELS)
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'count' => $records->where('status', $key)->count(),
            ])->values();

        return Inertia::render('Attendance::MyLog', [
            'me' => [
                'name' => $user->name,
                'position' => $user->position?->name,
                'photo' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
            ],
            'rows' => $rows,
            'summary' => $summary,
            'total' => $records->count(),
        ]);
    }
}

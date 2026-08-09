<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\Http\Controllers\AttendanceController;
use Modules\Leave\Http\Controllers\DailyStatusController;
use Modules\Leave\Http\Controllers\GroupLeaveController;
use Modules\Leave\Http\Controllers\GroupTripController;
use Modules\Leave\Http\Controllers\LeaveBalanceController;
use Modules\Leave\Http\Controllers\LeaveController;
use Modules\Leave\Http\Controllers\LeaveOfficerDelegateController;
use Modules\Leave\Http\Controllers\TripStatisticsController;
use Modules\Leave\Http\Controllers\LeaveRegistryController;
use Modules\Leave\Http\Controllers\OfficialTripController;

Route::middleware(['auth', 'verified'])
    ->prefix('leave')
    ->name('leave.')
    ->group(function () {
        // ระบบการลา — หน้าแรก (สถิติ) / เขียนคำขอ / แฟ้มการลา
        Route::get('requests', [LeaveController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [LeaveController::class, 'create'])->name('requests.create');
        Route::post('requests', [LeaveController::class, 'store'])->name('requests.store');
        Route::get('requests/folder', [LeaveController::class, 'folder'])->name('requests.folder');
        Route::get('requests/history', [LeaveController::class, 'history'])->name('requests.history');
        Route::get('requests/cancelled', [LeaveController::class, 'cancelled'])->name('requests.cancelled');
        Route::get('requests/{leaveRequest}', [LeaveController::class, 'show'])->name('requests.show');
        Route::get('requests/{leaveRequest}/proposal', [LeaveController::class, 'proposal'])->name('requests.proposal');
        Route::post('requests/{leaveRequest}/submit', [LeaveController::class, 'submit'])->name('requests.submit');
        Route::post('requests/{leaveRequest}/forward', [LeaveController::class, 'forward'])->name('requests.forward');
        Route::post('requests/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('requests.cancel');

        // รับมอบงาน (AMSS ส่วน 9) — ผู้ถูกมอบงานยืนยันรับมอบ
        Route::get('handover', [LeaveController::class, 'handoverInbox'])->name('handover.inbox');
        Route::post('requests/{leaveRequest}/accept-handover', [LeaveController::class, 'acceptHandover'])->name('handover.accept');

        // การมาปฏิบัติราชการวันนี้ของบุคลากร (ไปราชการ/ลา/ปกติ) — อัตโนมัติจากใบลา
        Route::get('today-status', [DailyStatusController::class, 'index'])->name('today-status');

        // ลงเวลาปฏิบัติราชการ — เจ้าหน้าที่บันทึกเอง (มา/มาสาย/ไม่มา/ไปราชการ/ลา) + ย้อนหลัง
        Route::middleware('role:leave_officer|secretary|admin|director|deputy_director')->group(function () {
            Route::get('attendance/entry', [AttendanceController::class, 'entry'])->name('attendance.entry');
            Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        });
        // รายงานการปฏิบัติราชการ (รายวัน/รอบเดือน) — ดูได้ทุกคน
        Route::get('attendance/daily', [AttendanceController::class, 'dailyReport'])->name('attendance.daily');
        Route::get('attendance/monthly', [AttendanceController::class, 'monthlyReport'])->name('attendance.monthly');

        // สถิติการไปราชการ (สรุปรายเดือน) — ผู้บริหาร/เลขาฯ
        Route::middleware('role:director|deputy_director|secretary|admin')
            ->get('trip-statistics', [TripStatisticsController::class, 'index'])->name('trip-statistics');

        // ทะเบียนลาของกลุ่ม (หัวหน้ากลุ่ม/ธุรการกลุ่ม)
        Route::middleware('role:head_of_department|head_of_subject|group_clerk|secretary|admin')
            ->get('group-leaves', [GroupLeaveController::class, 'index'])->name('group-leaves');

        // ทะเบียนไปราชการของกลุ่ม
        Route::middleware('role:head_of_department|head_of_subject|group_clerk|secretary|admin')
            ->get('group-trips', [GroupTripController::class, 'index'])->name('group-trips');

        // ทะเบียนลา (ส่วนกลาง) — รายชื่อบุคลากรทุกคน + ดูใบลารายคน (เจ้าหน้าที่วันลา/เลขาฯ/ผู้บริหาร)
        Route::middleware('role:leave_officer|secretary|admin|director|deputy_director')->group(function () {
            Route::get('registry', [LeaveRegistryController::class, 'index'])->name('registry.index');
            Route::get('registry/{user}', [LeaveRegistryController::class, 'show'])->name('registry.show');

            // บันทึกวันลาสะสม (AMSS ส่วน 9)
            Route::get('balances', [LeaveBalanceController::class, 'index'])->name('balances.index');
            Route::post('balances', [LeaveBalanceController::class, 'save'])->name('balances.save');
        });

        // จัดการผู้ปฏิบัติงานแทน (เจ้าหน้าที่วันลา) — แต่งตั้ง จนท.การลา (เฉพาะเลขาฯ/admin กัน escalation)
        Route::middleware('role:secretary|admin')->group(function () {
            Route::get('officer-delegates', [LeaveOfficerDelegateController::class, 'index'])->name('officer-delegates.index');
            Route::post('officer-delegates', [LeaveOfficerDelegateController::class, 'save'])->name('officer-delegates.save');
        });

        // แฟ้มตรวจสอบวันลา (ผู้อนุมัติ)
        Route::get('inbox', [LeaveController::class, 'inbox'])->name('requests.inbox');
        Route::post('routes/{route}/approve', [LeaveController::class, 'approve'])->name('requests.approve');
        Route::post('routes/{route}/reject', [LeaveController::class, 'reject'])->name('requests.reject');
    });

// ขออนุญาตไปราชการ (ฟอร์มเฉพาะ + เส้นทาง หัวหน้ากลุ่ม → ผอ.)
Route::middleware(['auth', 'verified'])
    ->prefix('official-trips')
    ->name('official-trips.')
    ->group(function () {
        Route::get('/', [OfficialTripController::class, 'index'])->name('index');
        Route::get('create', [OfficialTripController::class, 'create'])->name('create');
        Route::post('/', [OfficialTripController::class, 'store'])->name('store');
        Route::get('inbox', [OfficialTripController::class, 'inbox'])->name('inbox');
        Route::get('{officialTrip}', [OfficialTripController::class, 'show'])->name('show');
        Route::post('routes/{route}/approve', [OfficialTripController::class, 'approve'])->name('approve');
        Route::post('routes/{route}/reject', [OfficialTripController::class, 'reject'])->name('reject');
    });

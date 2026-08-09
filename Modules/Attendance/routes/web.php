<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AbsenceController;
use Modules\Attendance\Http\Controllers\AttendanceController;

Route::middleware(['auth', 'verified'])->group(function () {
    // หน้าลงเวลาเข้างาน
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    // สมุดลงเวลาของฉัน — ประวัติการลงเวลาของตัวเอง
    Route::get('attendance/my-log', [AttendanceController::class, 'myLog'])->name('attendance.my-log');

    // บันทึกผู้ไม่ลงเวลา (เจ้าหน้าที่วันลา/เลขาฯ/แอดมิน)
    Route::middleware('role:leave_officer|secretary|admin')->group(function () {
        Route::get('attendance/absence', [AbsenceController::class, 'form'])->name('attendance.absence');
        Route::post('attendance/absence', [AbsenceController::class, 'store'])->name('attendance.absence.store');
    });
});

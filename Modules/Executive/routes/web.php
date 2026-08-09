<?php

use Illuminate\Support\Facades\Route;
use Modules\Executive\Http\Controllers\DisbursementController;
use Modules\Executive\Http\Controllers\ExecutiveCalendarController;
use Modules\Executive\Http\Controllers\PlanYearController;
use Modules\Executive\Http\Controllers\ProjectBudgetController;
use Modules\Executive\Http\Controllers\StaffAwardController;
use Modules\Executive\Http\Controllers\StaffTrainingController;
use Modules\Executive\Http\Controllers\WorkLogController;

Route::middleware(['auth', 'verified'])
    ->prefix('executive')
    ->name('executive.')
    ->group(function () {
        // ===== ปฏิทินผู้บริหาร =====
        // ดูได้: เลขาฯ, ผอ., รองผอ., แอดมิน (ผอ. = read-only)
        Route::middleware('role:secretary|director|deputy_director|admin')->group(function () {
            Route::get('calendar', [ExecutiveCalendarController::class, 'index'])->name('calendar.index');
        });
        // เพิ่ม/แก้ไข/ลบได้เฉพาะ: เลขาฯ, แอดมิน
        Route::middleware('role:secretary|admin')->group(function () {
            Route::post('calendar', [ExecutiveCalendarController::class, 'store'])->name('calendar.store');
            Route::put('calendar/{event}', [ExecutiveCalendarController::class, 'update'])->name('calendar.update');
            Route::delete('calendar/{event}', [ExecutiveCalendarController::class, 'destroy'])->name('calendar.destroy');
        });

        // ===== บันทึกปฏิบัติงานผู้บริหาร (ของตนเอง) =====
        Route::middleware('role:secretary|director|deputy_director|admin')->group(function () {
            Route::get('work-logs', [WorkLogController::class, 'index'])->name('work-logs.index');
            Route::post('work-logs', [WorkLogController::class, 'store'])->name('work-logs.store');
            Route::delete('work-logs/{workLog}', [WorkLogController::class, 'destroy'])->name('work-logs.destroy');
        });

        // ===== รายงาน HR =====
        // ดู (read-only): เลขาฯ, ผอ., รองผอ., แอดมิน
        Route::middleware('role:secretary|director|deputy_director|admin')->group(function () {
            Route::get('trainings', [StaffTrainingController::class, 'index'])->name('trainings.index');
            Route::get('awards', [StaffAwardController::class, 'index'])->name('awards.index');
        });
        // ผลเบิกจ่ายงบโครงการ — รวมเจ้าหน้าที่แผนงานและงบประมาณ
        Route::middleware('role:secretary|director|deputy_director|budget_officer|admin')->group(function () {
            Route::get('budgets', [ProjectBudgetController::class, 'index'])->name('budgets.index');
        });
        // เพิ่ม/แก้ไข/ลบ: เลขาฯ, รองผอ., แอดมิน
        Route::middleware('role:secretary|deputy_director|admin')->group(function () {
            Route::post('trainings', [StaffTrainingController::class, 'store'])->name('trainings.store');
            Route::put('trainings/{training}', [StaffTrainingController::class, 'update'])->name('trainings.update');
            Route::delete('trainings/{training}', [StaffTrainingController::class, 'destroy'])->name('trainings.destroy');

            Route::post('awards', [StaffAwardController::class, 'store'])->name('awards.store');
            Route::put('awards/{award}', [StaffAwardController::class, 'update'])->name('awards.update');
            Route::delete('awards/{award}', [StaffAwardController::class, 'destroy'])->name('awards.destroy');

        });
        // เพิ่ม/แก้/ลบ งบโครงการ — เลขาฯ/รองผอ./เจ้าหน้าที่งบ/แอดมิน
        Route::middleware('role:secretary|deputy_director|budget_officer|admin')->group(function () {
            Route::post('budgets', [ProjectBudgetController::class, 'store'])->name('budgets.store');
            Route::put('budgets/{budget}', [ProjectBudgetController::class, 'update'])->name('budgets.update');
            Route::delete('budgets/{budget}', [ProjectBudgetController::class, 'destroy'])->name('budgets.destroy');

            // บันทึกการเบิกจ่ายแยกรายการ
            Route::get('disbursements', [DisbursementController::class, 'index'])->name('disbursements.index');
            Route::post('disbursements/{budget}', [DisbursementController::class, 'store'])->name('disbursements.store');
            Route::put('disbursements/entry/{disbursement}', [DisbursementController::class, 'update'])->name('disbursements.update');
            Route::delete('disbursements/entry/{disbursement}', [DisbursementController::class, 'destroy'])->name('disbursements.destroy');

            // จัดการปีจัดทำแผน
            Route::get('plan-year', [PlanYearController::class, 'index'])->name('plan-year.index');
            Route::post('plan-year', [PlanYearController::class, 'setYear'])->name('plan-year.set');
            Route::post('plan-year/system', [PlanYearController::class, 'useSystem'])->name('plan-year.system');
        });
    });

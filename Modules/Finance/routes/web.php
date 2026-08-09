<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\AllocationController;
use Modules\Finance\Http\Controllers\AuditController;
use Modules\Finance\Http\Controllers\FinanceHubController;
use Modules\Finance\Http\Controllers\FinanceSettingController;
use Modules\Finance\Http\Controllers\PaymentController;
use Modules\Finance\Http\Controllers\PetitionCancelController;
use Modules\Finance\Http\Controllers\PetitionController;
use Modules\Finance\Http\Controllers\ProjectReturnController;
use Modules\Finance\Http\Controllers\ReceiptController;
use Modules\Finance\Http\Controllers\ReportController;
use Modules\Finance\Http\Controllers\StatusChangeController;
use Modules\Finance\Http\Controllers\TreasuryReturnController;
use Modules\Finance\Http\Controllers\WithdrawalController;

Route::middleware(['auth', 'verified'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        // ===== หน้ารวมระบบการเงิน (hub) =====
        Route::middleware('role:budget_officer|secretary|admin|director|deputy_director')
            ->get('/', [FinanceHubController::class, 'index'])->name('hub');

        // ===== ตั้งค่าระบบการเงิน (AMSS ส่วนที่ 1) =====
        Route::middleware('role:budget_officer|secretary|admin')->group(function () {
            Route::get('settings', [FinanceSettingController::class, 'index'])->name('settings.index');

            // ปีงบประมาณ
            Route::post('settings/years', [FinanceSettingController::class, 'addYear'])->name('settings.years.add');
            Route::post('settings/years/{fiscalYear}/current', [FinanceSettingController::class, 'setCurrentYear'])->name('settings.years.current');
            Route::delete('settings/years/{fiscalYear}', [FinanceSettingController::class, 'deleteYear'])->name('settings.years.delete');

            // เจ้าหน้าที่การเงิน
            Route::post('settings/officers', [FinanceSettingController::class, 'saveOfficer'])->name('settings.officers.save');
            Route::delete('settings/officers/{officer}', [FinanceSettingController::class, 'deleteOfficer'])->name('settings.officers.delete');

            // master data
            Route::post('settings/masters', [FinanceSettingController::class, 'storeMaster'])->name('settings.masters.store');
            Route::put('settings/masters/{master}', [FinanceSettingController::class, 'updateMaster'])->name('settings.masters.update');
            Route::delete('settings/masters/{master}', [FinanceSettingController::class, 'destroyMaster'])->name('settings.masters.destroy');

            // ===== ทะเบียนรับ (AMSS ส่วนที่ 2) =====
            // 2.1 ทะเบียนเงินงวด (จัดสรรงบประมาณ)
            Route::get('allocations', [AllocationController::class, 'index'])->name('allocations.index');
            Route::post('allocations', [AllocationController::class, 'store'])->name('allocations.store');
            Route::put('allocations/{allocation}', [AllocationController::class, 'update'])->name('allocations.update');
            Route::delete('allocations/{allocation}', [AllocationController::class, 'destroy'])->name('allocations.destroy');

            // 2.2–2.4 ทะเบียนรับเงิน (budget|nonbudget|state_revenue)
            Route::get('receipts/{class}', [ReceiptController::class, 'index'])->name('receipts.index');
            Route::post('receipts/{class}', [ReceiptController::class, 'store'])->name('receipts.store');
            Route::put('receipts/entry/{receipt}', [ReceiptController::class, 'update'])->name('receipts.update');
            Route::delete('receipts/entry/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');

            // ===== ทะเบียนขอเบิก (AMSS ส่วนที่ 3) =====
            // 3.1 ขอเบิก/ขอยืมโครงการ
            Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
            Route::post('withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
            Route::put('withdrawals/{withdrawal}', [WithdrawalController::class, 'update'])->name('withdrawals.update');
            Route::post('withdrawals/{withdrawal}/settle', [WithdrawalController::class, 'settle'])->name('withdrawals.settle');
            Route::delete('withdrawals/{withdrawal}', [WithdrawalController::class, 'destroy'])->name('withdrawals.destroy');

            // 3.2 คืนเงินโครงการ
            Route::get('project-returns', [ProjectReturnController::class, 'index'])->name('project-returns.index');
            Route::post('project-returns', [ProjectReturnController::class, 'store'])->name('project-returns.store');
            Route::delete('project-returns/{projectReturn}', [ProjectReturnController::class, 'destroy'])->name('project-returns.destroy');

            // 3.3 ขอเบิกเงินคงคลัง / 3.6 เงินกันเหลื่อมปี (treasury|carryover)
            Route::get('petitions/{type}', [PetitionController::class, 'index'])->name('petitions.index');
            Route::post('petitions/{type}', [PetitionController::class, 'store'])->name('petitions.store');
            Route::delete('petitions/entry/{petition}', [PetitionController::class, 'destroy'])->name('petitions.destroy');

            // 3.4 คืนเงินคงคลัง
            Route::get('treasury-returns', [TreasuryReturnController::class, 'index'])->name('treasury-returns.index');
            Route::post('treasury-returns', [TreasuryReturnController::class, 'store'])->name('treasury-returns.store');
            Route::delete('treasury-returns/{treasuryReturn}', [TreasuryReturnController::class, 'destroy'])->name('treasury-returns.destroy');

            // 3.5 ยกเลิกฎีกา
            Route::get('petition-cancels', [PetitionCancelController::class, 'index'])->name('petition-cancels.index');
            Route::post('petition-cancels', [PetitionCancelController::class, 'store'])->name('petition-cancels.store');
            Route::delete('petition-cancels/{petitionCancel}', [PetitionCancelController::class, 'destroy'])->name('petition-cancels.destroy');

            // ===== ทะเบียนจ่าย (AMSS ส่วนที่ 4) =====
            // 4.5–4.6 อนุมัติจ่าย / 4.7–4.8 จ่ายเงิน (ประกาศก่อน {class} กันชนกัน)
            Route::get('payment-approvals', [PaymentController::class, 'approvals'])->name('payments.approvals');
            Route::get('payment-payouts', [PaymentController::class, 'payouts'])->name('payments.payouts');
            Route::post('payments/entry/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
            Route::post('payments/entry/{payment}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
            Route::put('payments/entry/{payment}', [PaymentController::class, 'update'])->name('payments.update');
            Route::delete('payments/entry/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
            // 4.1–4.4 สั่งจ่าย (budget|nonbudget|state_revenue|advance)
            Route::get('payments/{class}', [PaymentController::class, 'index'])->name('payments.index');
            Route::post('payments/{class}', [PaymentController::class, 'store'])->name('payments.store');

            // ===== เปลี่ยนแปลงสถานะเงิน (AMSS ส่วนที่ 5) =====
            Route::get('status-changes/{class}', [StatusChangeController::class, 'index'])->name('status-changes.index');
            Route::post('status-changes/{class}', [StatusChangeController::class, 'store'])->name('status-changes.store');
            Route::delete('status-changes/entry/{statusChange}', [StatusChangeController::class, 'destroy'])->name('status-changes.destroy');

            // ===== ตรวจสอบ (ส่วนที่ 6) + รายงาน (ส่วนที่ 7) =====
            Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        });
    });

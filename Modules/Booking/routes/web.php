<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;
use Modules\Booking\Http\Controllers\MeetingRoomController;
use Modules\Booking\Http\Controllers\VehicleController;
use Modules\Booking\Http\Controllers\VehicleFlowController;

Route::middleware(['auth', 'verified'])
    ->prefix('booking')
    ->name('booking.')
    ->group(function () {
        // ระบบจองทรัพยากร (ผู้ใช้ทั่วไป)
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::post('/', [BookingController::class, 'store'])->name('store');

        // แฟ้มขอใช้รถยนต์ (workflow: เสนอ → จัดรถ → อนุมัติ)
        Route::get('vehicle-flow', [VehicleFlowController::class, 'index'])->name('vehicle-flow.index');
        // ทะเบียนการจองรถ / ตรวจสอบทะเบียนรถ (เจ้าหน้าที่/ผู้บริหาร)
        Route::middleware('role:vehicle_booking_officer|director|deputy_director|secretary|admin')->group(function () {
            Route::get('vehicle-registry', [VehicleFlowController::class, 'registry'])->name('vehicle-flow.registry');
            Route::post('vehicle-flow/{booking}/officer-cancel', [VehicleFlowController::class, 'officerCancel'])->name('vehicle-flow.officer-cancel');
        });
        // เมนูใบเบิกน้ำมัน (เจ้าหน้าที่ยานพาหนะ) — AMSS ส่วน 11
        Route::middleware('role:vehicle_booking_officer|admin')
            ->get('vehicle-fuel', [VehicleFlowController::class, 'fuelList'])->name('vehicle-flow.fuel-list');
        // ใบเบิกน้ำมันเชื้อเพลิงและน้ำมันหล่อลื่น (AMSS ส่วน 11)
        Route::get('vehicle-flow/{booking}/fuel', [VehicleFlowController::class, 'fuelForm'])->name('vehicle-flow.fuel');
        Route::post('vehicle-flow/{booking}/fuel', [VehicleFlowController::class, 'saveFuel'])->name('vehicle-flow.fuel.save');

        Route::get('vehicle-flow/{booking}', [VehicleFlowController::class, 'show'])->name('vehicle-flow.show');
        Route::post('vehicle-flow/{booking}/submit', [VehicleFlowController::class, 'submit'])->name('vehicle-flow.submit');
        Route::post('vehicle-flow/{booking}/assign', [VehicleFlowController::class, 'assign'])->name('vehicle-flow.assign');
        Route::post('vehicle-flow/{booking}/approve', [VehicleFlowController::class, 'approve'])->name('vehicle-flow.approve');
        Route::post('vehicle-flow/{booking}/reject', [VehicleFlowController::class, 'reject'])->name('vehicle-flow.reject');

        Route::delete('{booking}', [BookingController::class, 'cancel'])->name('cancel');

        // จัดการทรัพยากร (เฉพาะ admin) — หน้าจัดการเดิม (มี Vue)
        Route::middleware('role:admin')->group(function () {
            Route::resource('vehicles', VehicleController::class)
                ->only(['index', 'store', 'update', 'destroy']);
            Route::resource('meeting-rooms', MeetingRoomController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->parameters(['meeting-rooms' => 'meetingRoom']);
        });
    });

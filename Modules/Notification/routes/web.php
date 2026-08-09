<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;

Route::middleware(['auth', 'verified'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('{id}/open', [NotificationController::class, 'open'])->name('open');
        Route::post('read-all', [NotificationController::class, 'readAll'])->name('read-all');
        Route::post('purge-broken', [NotificationController::class, 'purgeBroken'])->name('purge-broken');
    });

<?php

use Illuminate\Support\Facades\Route;
use Modules\Announcement\Http\Controllers\AnnouncementController;
use Modules\Announcement\Http\Controllers\NewsFeedController;
use Modules\Announcement\Http\Controllers\QuickNewsController;

// หน้าข่าวสาร — อ่านได้ทุกคน
Route::middleware(['auth', 'verified'])->get('news', [NewsFeedController::class, 'index'])->name('news.feed');

// จัดการข่าวสาร — เฉพาะผู้ดูแลระบบ (admin)
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::resource('announcements', AnnouncementController::class)
        ->except(['show'])
        ->names('announcements');
});

// เพิ่ม/ลบข่าว (จากหน้าข่าวสาร) — เฉพาะผู้มีสิทธิ์เขียนข่าว
Route::middleware(['auth', 'verified', 'role:deputy_director|secretary|director|admin|group_clerk'])
    ->prefix('quick-news')->name('quick-news.')->group(function () {
        Route::post('/', [QuickNewsController::class, 'store'])->name('store');
        Route::delete('{news}', [QuickNewsController::class, 'destroy'])->name('destroy');
    });

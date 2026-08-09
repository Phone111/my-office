<?php

use Illuminate\Support\Facades\Route;

// (สงวนไว้สำหรับ API ในอนาคต) — เส้นทางหลักของโมดูลการเงินอยู่ใน web.php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    //
});

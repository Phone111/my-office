<?php

use Illuminate\Support\Facades\Route;
use Modules\Saraban\Http\Controllers\SarabanController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sarabans', SarabanController::class)->names('saraban');
});

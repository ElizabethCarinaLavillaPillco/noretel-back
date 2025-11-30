<?php

use Illuminate\Support\Facades\Route;
use Modules\Public\Http\Controllers\CoverageController;
use Modules\Public\Http\Controllers\CoverageRequestController;
use Modules\Public\Http\Controllers\PlanController;

/*
|--------------------------------------------------------------------------
| API Routes - Público
|--------------------------------------------------------------------------
*/
$planController = 'Modules\Public\Http\Controllers\PlanController';


Route::prefix('public')->name('public.')->group(function () {

    // ==================== COBERTURA ====================
    Route::prefix('coverage')->name('coverage.')->group(function () {
        Route::post('/check', [CoverageController::class, 'check'])->name('check');
        Route::post('/check-address', [CoverageController::class, 'checkByAddress'])->name('check-address');
        Route::get('/zone-stats', [CoverageController::class, 'getZoneStats'])->name('zone-stats');
    });

    Route::prefix('coverage/request')->name('coverage.request.')->group(function () {
        Route::post('/', [CoverageRequestController::class, 'store'])->name('store');
        Route::get('/', [CoverageRequestController::class, 'index'])->name('index');
        Route::get('/{id}', [CoverageRequestController::class, 'show'])->name('show');
    });

    // ==================== PLANES PÚBLICOS ====================
    Route::prefix('plans')->name('plans.')->group(function () use ($planController) {
        Route::get('/', [$planController, 'index'])->name('index');
        Route::get('/by-service', [$planController, 'getByServiceType'])->name('by-service');
        Route::get('/{id}', [$planController, 'show'])->name('show');
    });
});

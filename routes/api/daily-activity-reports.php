<?php

use App\Http\Controllers\Api\DailyActivityReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('daily-activity-reports')->name('daily-activity-reports.')->group(function () {
    Route::get('/', [DailyActivityReportController::class, 'index'])->name('index');
    Route::post('/', [DailyActivityReportController::class, 'store'])->name('store');
    Route::get('/export', [DailyActivityReportController::class, 'export'])->name('export');
    Route::get('/archive', [DailyActivityReportController::class, 'archive'])->name('archive');
    Route::get('/archive/history', [DailyActivityReportController::class, 'cleanupHistory'])->name('archive.history');
    Route::post('/archive/cleanup', [DailyActivityReportController::class, 'cleanup'])->name('archive.cleanup');
    Route::get('/{dailyActivityReport}', [DailyActivityReportController::class, 'show'])->name('show');
    Route::put('/{dailyActivityReport}', [DailyActivityReportController::class, 'update'])->name('update');
    Route::delete('/{dailyActivityReport}', [DailyActivityReportController::class, 'destroy'])->name('destroy');
});

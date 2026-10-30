<?php

use App\Http\Controllers\Api\EmployeeLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees/locations')->name('employees.locations.')->group(function () {
    Route::get('/', [EmployeeLocationController::class, 'index'])->name('index');
    Route::get('/live', [EmployeeLocationController::class, 'live'])->name('live');
    Route::get('/history/daily-summary', [EmployeeLocationController::class, 'dailySummary'])->name('history.daily-summary');
    Route::get('/history', [EmployeeLocationController::class, 'history'])->name('history');
    Route::post('/me', [EmployeeLocationController::class, 'store'])->name('store');
});

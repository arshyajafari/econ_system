<?php

use App\Http\Controllers\Api\EmployeeLocationController;
use App\Http\Controllers\Api\EmployeeDailyLocationController;
use App\Http\Controllers\Api\EmployeeWorkSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees/locations')->name('employees.locations.')->group(function () {
    Route::get('/me/daily-point-count', [EmployeeLocationController::class, 'myDailyPointCount'])->name('me.daily-point-count');
    Route::get('/check-ins/me', [EmployeeDailyLocationController::class, 'today'])->name('check-ins.today');
    Route::post('/check-ins/me', [EmployeeDailyLocationController::class, 'store'])->middleware('throttle:6,1')->name('check-ins.store');
    Route::get('/check-ins', [EmployeeDailyLocationController::class, 'index'])->name('check-ins.index');
    Route::get('/work-sessions', [EmployeeWorkSessionController::class, 'index'])->name('work-sessions.index');
    Route::get('/work-sessions/{sessionId}/locations', [EmployeeWorkSessionController::class, 'locations'])->name('work-sessions.locations');
    Route::get('/work-sessions/me', [EmployeeWorkSessionController::class, 'status'])->name('work-sessions.status');
    Route::post('/work-sessions/start', [EmployeeWorkSessionController::class, 'start'])->name('work-sessions.start');
    Route::post('/work-sessions/end', [EmployeeWorkSessionController::class, 'end'])->name('work-sessions.end');
    Route::patch('/work-sessions/{sessionId}/correct', [EmployeeWorkSessionController::class, 'correct'])->name('work-sessions.correct');

    Route::get('/', [EmployeeLocationController::class, 'index'])->name('index');
    Route::get('/live', [EmployeeLocationController::class, 'live'])->name('live');
    Route::get('/history/daily-summary', [EmployeeLocationController::class, 'dailySummary'])->name('history.daily-summary');
    Route::get('/history', [EmployeeLocationController::class, 'history'])->name('history');
    Route::post('/me', [EmployeeLocationController::class, 'store'])->middleware('throttle:6,1')->name('store');
});

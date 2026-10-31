<?php

use App\Http\Controllers\Api\EmployeeLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees/locations')->name('employees.locations.')->group(function () {\n    Route::get('/work-sessions', [EmployeeWorkSessionController::class, 'index'])->name('work-sessions.index');\n    Route::get('/work-sessions/me', [EmployeeWorkSessionController::class, 'status'])->name('work-sessions.status');\n    Route::post('/work-sessions/start', [EmployeeWorkSessionController::class, 'start'])->name('work-sessions.start');\n    Route::post('/work-sessions/end', [EmployeeWorkSessionController::class, 'end'])->name('work-sessions.end');
    Route::get('/', [EmployeeLocationController::class, 'index'])->name('index');
    Route::get('/live', [EmployeeLocationController::class, 'live'])->name('live');
    Route::get('/history/daily-summary', [EmployeeLocationController::class, 'dailySummary'])->name('history.daily-summary');
    Route::get('/history', [EmployeeLocationController::class, 'history'])->name('history');
    Route::post('/me', [EmployeeLocationController::class, 'store'])->name('store');
});

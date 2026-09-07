<?php

use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
    Route::patch('/{employee}/status', [EmployeeController::class, 'changeStatus'])->name('change-status');
    Route::patch('/{employee}/restore', [EmployeeController::class, 'restore'])
        ->withTrashed()
        ->name('restore');
});

<?php

use App\Http\Controllers\Api\EmployeeLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees/locations')->name('employees.locations.')->group(function () {
    Route::get('/', [EmployeeLocationController::class, 'index'])->name('index');
    Route::post('/me', [EmployeeLocationController::class, 'store'])->name('store');
});

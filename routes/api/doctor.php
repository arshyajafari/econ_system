<?php

    use App\Http\Controllers\Api\DoctorController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('doctors')->name('doctors.')->group(function () {
        Route::get('/', [
            DoctorController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            DoctorController::class,
            'store'
        ])->name('store');

        Route::get('/{doctor}', [
            DoctorController::class,
            'show'
        ])->name('show');

        Route::put('/{doctor}', [
            DoctorController::class,
            'update'
        ])->name('update');

        Route::delete('/{doctor}', [
            DoctorController::class,
            'destroy'
        ])->name('destroy');

        Route::patch('/{doctor}/status', [
            DoctorController::class,
            'changeStatus'
        ])->name('change-status');

        Route::patch('/{doctor}/restore', [
            DoctorController::class,
            'restore'
        ])->withTrashed()->name('restore');
    });

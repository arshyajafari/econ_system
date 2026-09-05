<?php

    use App\Http\Controllers\Api\AuthController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [
            AuthController::class,
            'login',
        ])->name('login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [
            AuthController::class,
            'logout',
        ])->name('auth.logout');
    });

<?php

    use App\Http\Controllers\Api\PaymentController;
    use Illuminate\Support\Facades\Route;

    Route::get('payments', [
        PaymentController::class,
        'index',
    ]);

    Route::get('payments/{payment}', [
        PaymentController::class,
        'show',
    ]);

    Route::post('payments', [
        PaymentController::class,
        'store',
    ]);

    Route::put('payments/{payment}', [
        PaymentController::class,
        'update',
    ]);

    Route::post('payments/{payment}/confirm', [
        PaymentController::class,
        'confirm',
    ]);

    Route::post('payments/{payment}/cancel', [
        PaymentController::class,
        'cancel',
    ]);

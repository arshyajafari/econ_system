<?php

    use App\Http\Controllers\Api\PaymentController;
    use Illuminate\Support\Facades\Route;


    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [
            PaymentController::class,
            'index',
        ]);

        Route::post('/', [
            PaymentController::class,
            'store',
        ]);

        Route::get('/{payment}', [
            PaymentController::class,
            'show',
        ]);

        Route::put('/{payment}', [
            PaymentController::class,
            'update',
        ]);

        Route::post('/{payment}/confirm', [
            PaymentController::class,
            'confirm',
        ]);

        Route::post('/{payment}/cancel', [
            PaymentController::class,
            'cancel',
        ]);
    });










<?php

    use App\Http\Controllers\Api\OrderController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [
            OrderController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            OrderController::class,
            'store'
        ])->name('store');

        Route::get('/{order}', [
            OrderController::class,
            'show'
        ])->name('show');

        Route::put('/{order}', [
            OrderController::class,
            'update'
        ])->name('update');

        Route::post('/{order}/submit', [
            OrderController::class,
            'submit'
        ])->name('submit');

        Route::post('/{order}/confirm', [
            OrderController::class,
            'confirm'
        ])->name('confirm');

        Route::post('/{order}/complete', [
            OrderController::class,
            'complete'
        ])->name('complete');

        Route::post('/{order}/cancel', [
            OrderController::class,
            'cancel'
        ])->name('cancel');
    });

<?php

    use App\Http\Controllers\Api\DeliveryController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [
            DeliveryController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            DeliveryController::class,
            'store'
        ])->name('store');

        Route::get('/{delivery}', [
            DeliveryController::class,
            'show'
        ])->name('show');

        Route::put('/{delivery}', [
            DeliveryController::class,
            'update'
        ])->name('update');

        Route::post('/{delivery}/prepare', [
            DeliveryController::class,
            'prepare'
        ])->name('prepare');

        Route::post('/{delivery}/ship', [
            DeliveryController::class,
            'ship'
        ])->name('ship');

        Route::post('/{delivery}/complete', [
            DeliveryController::class,
            'complete'
        ])->name('complete');

        Route::post('/{delivery}/cancel', [
            DeliveryController::class,
            'cancel'
        ])->name('cancel');
    });

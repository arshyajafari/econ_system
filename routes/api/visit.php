<?php

    use App\Http\Controllers\Api\VisitController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('visits')->name('visits.')->group(function () {
        Route::get('/', [
            VisitController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            VisitController::class,
            'store'
        ])->name('store');

        Route::get('/{visit}', [
            VisitController::class,
            'show'
        ])->name('show');

        Route::put('/{visit}', [
            VisitController::class,
            'update'
        ])->name('update');

        Route::post('/{visit}/complete', [
            VisitController::class,
            'complete'
        ])->name('complete');

        Route::post('/{visit}/cancel', [
            VisitController::class,
            'cancel'
        ])->name('cancel');
    });

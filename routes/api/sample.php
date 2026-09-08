<?php

    use App\Http\Controllers\Api\SampleController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('samples')->name('samples.')->group(function () {
        Route::get('/', [
            SampleController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            SampleController::class,
            'store'
        ])->name('store');

        Route::get('/{sample}', [
            SampleController::class,
            'show'
        ])->name('show');

        Route::put('/{sample}', [
            SampleController::class,
            'update'
        ])->name('update');

        Route::delete('/{sample}', [
            SampleController::class,
            'destroy'
        ])->name('destroy');
    });

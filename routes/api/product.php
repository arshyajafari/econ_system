<?php

    use App\Http\Controllers\Api\ProductController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [
            ProductController::class,
            'index'
        ]);

        Route::post('/', [
            ProductController::class,
            'store'
        ]);

        Route::get('/{product}', [
            ProductController::class,
            'show'
        ]);

        Route::put('/{product}', [
            ProductController::class,
            'update'
        ]);

        Route::delete('/{product}', [
            ProductController::class,
            'destroy'
        ]);

        Route::patch('/{product}/status', [
            ProductController::class,
            'changeStatus'
        ]);
    });

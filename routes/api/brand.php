<?php

    use App\Http\Controllers\Api\BrandController;
    use Illuminate\Support\Facades\Route;

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider and all of them will
    | be assigned to the "api" middleware group. Make something great!
    |
    */

    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [
            BrandController::class,
            'index'
        ]);
        Route::post('/', [
            BrandController::class,
            'store'
        ]);
        Route::get('/{brand}', [
            BrandController::class,
            'show'
        ]);
        Route::put('/{brand}', [
            BrandController::class,
            'update'
        ]);
        Route::delete('/{brand}', [
            BrandController::class,
            'destroy'
        ]);
        Route::patch('/{brand}/activity', [
            BrandController::class,
            'changeActivity'
        ]);
    });

<?php

    use App\Http\Controllers\InventoryAdjustmentController;
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

    Route::prefix('inventory-adjustments')->name('inventory-adjustments.')->group(function () {
        Route::get('/', [
            InventoryAdjustmentController::class,
            'index'
        ])->name('index');
        Route::post('/', [
            InventoryAdjustmentController::class,
            'store'
        ])->name('store');
        Route::get('/{inventoryAdjustment}', [
            InventoryAdjustmentController::class,
            'show'
        ])->name('show');
    });

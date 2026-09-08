<?php

    use App\Http\Controllers\Api\InventoryAdjustmentController;
    use Illuminate\Support\Facades\Route;

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

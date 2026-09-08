<?php

    use App\Http\Controllers\Api\InventoryBatchController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('inventory-batches')->name('inventory-batches.')->group(function () {
        Route::get('/', [
            InventoryBatchController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            InventoryBatchController::class,
            'store'
        ])->name('store');

        Route::get('/{inventoryBatch}', [
            InventoryBatchController::class,
            'show'
        ])->name('show');

        Route::put('/{inventoryBatch}', [
            InventoryBatchController::class,
            'update'
        ])->name('update');

        Route::delete('/{inventoryBatch}', [
            InventoryBatchController::class,
            'destroy'
        ])->name('destroy');
    });

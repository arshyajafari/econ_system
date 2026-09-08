<?php


    use App\Http\Controllers\Api\InventoryMovementController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('inventory-movements')->name('inventory-movements.')->group(function () {
        Route::get('/', [
            InventoryMovementController::class,
            'index'
        ])->name('index');

        Route::get('/{inventoryMovement}', [
            InventoryMovementController::class,
            'show'
        ])->name('show');
    });

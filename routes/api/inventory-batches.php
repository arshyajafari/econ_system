<?php

    use App\Http\Controllers\InventoryBatchController;
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

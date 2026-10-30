<?php

use App\Http\Controllers\Api\ScientificVisitorInventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('scientific-visitor-inventory')->name('scientific-visitor-inventory.')->group(function () {
    Route::get('/', [ScientificVisitorInventoryController::class, 'index'])->name('index');
    Route::get('/transfers', [ScientificVisitorInventoryController::class, 'transfers'])->name('transfers');
    Route::post('/', [ScientificVisitorInventoryController::class, 'store'])->name('store');
    Route::put('/{scientificVisitorInventory}/transfers/{transfer}', [ScientificVisitorInventoryController::class, 'updateTransfer'])->whereNumber('transfer')->name('transfers.update');
    Route::delete('/{scientificVisitorInventory}/transfers/{transfer}', [ScientificVisitorInventoryController::class, 'destroyTransfer'])->whereNumber('transfer')->name('transfers.destroy');
    Route::put('/{scientificVisitorInventory}', [ScientificVisitorInventoryController::class, 'update'])->name('update');
    Route::delete('/{scientificVisitorInventory}', [ScientificVisitorInventoryController::class, 'destroy'])->name('destroy');
});

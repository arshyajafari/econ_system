<?php

use App\Http\Controllers\Api\ScientificVisitorInventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('scientific-visitor-inventory')->name('scientific-visitor-inventory.')->group(function () {
    Route::get('/', [ScientificVisitorInventoryController::class, 'index'])->name('index');\n    Route::get('/transfers', [ScientificVisitorInventoryController::class, 'transfers'])->name('transfers.index');
    Route::post('/', [ScientificVisitorInventoryController::class, 'store'])->name('store');
    Route::put('/{scientificVisitorInventory}/transfers/{transferId}', [ScientificVisitorInventoryController::class, 'updateTransfer'])->whereNumber('transferId')->name('transfers.update');\n    Route::delete('/{scientificVisitorInventory}/transfers/{transferId}', [ScientificVisitorInventoryController::class, 'destroyTransfer'])->whereNumber('transferId')->name('transfers.destroy');\n    Route::put('/{scientificVisitorInventory}', [ScientificVisitorInventoryController::class, 'update'])->name('update');
    Route::delete('/{scientificVisitorInventory}', [ScientificVisitorInventoryController::class, 'destroy'])->name('destroy');
});

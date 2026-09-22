<?php

use App\Http\Controllers\Api\ScientificVisitorInventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('scientific-visitor-inventory')->name('scientific-visitor-inventory.')->group(function () {
    Route::get('/', [ScientificVisitorInventoryController::class, 'index'])->name('index');
    Route::post('/', [ScientificVisitorInventoryController::class, 'store'])->name('store');
    Route::put('/{scientificVisitorInventory}', [ScientificVisitorInventoryController::class, 'update'])->name('update');
});

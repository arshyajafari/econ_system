<?php

use App\Http\Controllers\Api\OrderReturnController;
use Illuminate\Support\Facades\Route;

Route::prefix('order-returns')->name('order-returns.')->group(function () {
    Route::get('/', [OrderReturnController::class, 'index'])->name('index');
    Route::post('/', [OrderReturnController::class, 'store'])->name('store');
    Route::get('/{orderReturn}', [OrderReturnController::class, 'show'])->name('show');
    Route::put('/{orderReturn}', [OrderReturnController::class, 'update'])->name('update');
    Route::post('/{orderReturn}/submit', [OrderReturnController::class, 'submit'])->name('submit');
    Route::post('/{orderReturn}/confirm', [OrderReturnController::class, 'confirm'])->name('confirm');
    Route::post('/{orderReturn}/complete', [OrderReturnController::class, 'complete'])->name('complete');
    Route::post('/{orderReturn}/cancel', [OrderReturnController::class, 'cancel'])->name('cancel');
});

Route::post('/order-return-items/{orderReturnItem}/allocate', [OrderReturnController::class, 'allocate'])
    ->name('order-return-items.allocate');

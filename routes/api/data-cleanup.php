<?php

use App\Http\Controllers\Api\DataCleanupController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/data-cleanup')->name('admin.data-cleanup.')->group(function () {
    Route::get('/preview', [DataCleanupController::class, 'preview'])->name('preview');
    Route::get('/history', [DataCleanupController::class, 'history'])->name('history');
    Route::post('/execute', [DataCleanupController::class, 'execute'])->name('execute');
});

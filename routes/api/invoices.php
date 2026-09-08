<?php

    use App\Http\Controllers\Api\InvoiceController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [
            InvoiceController::class,
            'index',
        ]);

        Route::get('/{invoice}', [
            InvoiceController::class,
            'show',
        ]);

        Route::post('orders/{order}/invoice', [
            InvoiceController::class,
            'store',
        ]);

        Route::put('/{invoice}', [
            InvoiceController::class,
            'update',
        ]);

        Route::post('/{invoice}/issue', [
            InvoiceController::class,
            'issue',
        ]);

        Route::post('/{invoice}/cancel', [
            InvoiceController::class,
            'cancel',
        ]);
    });

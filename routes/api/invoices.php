<?php



    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);

    Route::post(
        'orders/{order}/invoice',
        [InvoiceController::class, 'store']
    );

    Route::put(
        'invoices/{invoice}',
        [InvoiceController::class, 'update']
    );

    Route::post(
        'invoices/{invoice}/issue',
        [InvoiceController::class, 'issue']
    );

    Route::post(
        'invoices/{invoice}/cancel',
        [InvoiceController::class, 'cancel']
    );

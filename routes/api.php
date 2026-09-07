<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/auth.php';

    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__ . '/api/brand.php';
        require __DIR__ . '/api/product-categories.php';
        require __DIR__ . '/api/product.php';

        require __DIR__ . '/api/customer.php';
        require __DIR__ . '/api/employee.php';
        require __DIR__ . '/api/doctor.php';

        require __DIR__ . '/api/inventory-adjustments.php';
        require __DIR__ . '/api/inventory-batches.php';
        require __DIR__ . '/api/inventory-movements.php';

        require __DIR__ . '/api/order.php';
        require __DIR__ . '/api/order-returns.php';

        require __DIR__ . '/api/invoices.php';
        require __DIR__ . '/api/payments.php';

        require __DIR__ . '/api/delivery.php';
        require __DIR__ . '/api/visit.php';
        require __DIR__ . '/api/sample.php';
        require __DIR__ . '/api/report.php';
    });
});

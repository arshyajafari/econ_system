<?php

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

    Route::prefix('v1')->group(function () {

        require __DIR__ . '/api/auth.php';

        Route::middleware('auth:sanctum')->group(function () {

            require __DIR__ . '/api/brand.php';
            require __DIR__ . '/api/doctor.php';

            require __DIR__ . '/api/inventory-adjustments.php';
            require __DIR__ . '/api/inventory-batches.php';
            require __DIR__ . '/api/inventory-movements.php';

            require __DIR__ . '/api/invoices.php';
            require __DIR__ . '/api/payments.php';

            require __DIR__ . '/api/product.php';

            /*
             * اینها بعد از تکمیل routeهایشان فعال شوند:
             *
             * require __DIR__ . '/api/customer.php';
             * require __DIR__ . '/api/employee.php';
             * require __DIR__ . '/api/order.php';
             * require __DIR__ . '/api/report.php';
             * require __DIR__ . '/api/order-return.php';
             * require __DIR__ . '/api/delivery.php';
             * require __DIR__ . '/api/visit.php';
             */
        });
    });

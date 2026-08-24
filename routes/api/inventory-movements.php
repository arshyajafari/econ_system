<?php


    use App\Http\Controllers\InventoryMovementController;
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

    Route::prefix('inventory-movements')->name('inventory-movements.')->group(function () {
        Route::get('/', [
            InventoryMovementController::class,
            'index'
        ])->name('index');
        Route::get('/{inventoryMovement}', [
            InventoryMovementController::class,
            'show'
        ])->name('show');
    });

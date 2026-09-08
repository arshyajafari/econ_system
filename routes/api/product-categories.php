<?php

    use App\Http\Controllers\Api\ProductCategoryController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('product-categories')->name('product-categories.')->group(function () {
        Route::get('/', [
            ProductCategoryController::class,
            'index'
        ])->name('index');

        Route::get('/tree', [
            ProductCategoryController::class,
            'tree'
        ])->name('tree');

        Route::post('/', [
            ProductCategoryController::class,
            'store'
        ])->name('store');

        Route::get('/{productCategory}', [
            ProductCategoryController::class,
            'show'
        ])->name('show');

        Route::put('/{productCategory}', [
            ProductCategoryController::class,
            'update'
        ])->name('update');

        Route::delete('/{productCategory}', [
            ProductCategoryController::class,
            'destroy'
        ])->name('destroy');

        Route::patch('/{productCategory}/activity', [
            ProductCategoryController::class,
            'changeActivity'
        ])->name('change-activity');
    });

<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [
    DashboardController::class,
    'index',
])->name('dashboard.index');

Route::get('/reports/summary', [
    ReportController::class,
    'index',
])->name('reports.summary');

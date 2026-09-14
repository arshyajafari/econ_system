<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])->name('mark-read');
    Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
    Route::get('/recipients', [NotificationController::class, 'recipients'])->name('recipients');
    Route::post('/send', [NotificationController::class, 'send'])->name('send');
});

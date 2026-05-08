<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/conversations', [\App\Http\Controllers\Api\MessagingController::class, 'conversations'])->name('api.conversations.index');
    Route::post('/conversations', [\App\Http\Controllers\Api\MessagingController::class, 'createOrGet'])->name('api.conversations.store');
    Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\MessagingController::class, 'messages'])->name('api.messages.index');
    Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\MessagingController::class, 'store'])->name('api.messages.store');
});

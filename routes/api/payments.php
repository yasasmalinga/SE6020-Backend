<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/payments', [PaymentController::class, 'index'])->name('api.payments.index');
    Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->name('api.payments.initiate');
    Route::patch('/payments/{paymentId}/status', [PaymentController::class, 'updateStatus'])->name('api.payments.status');
});

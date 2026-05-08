<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'index'])->name('api.bookings.index');
    Route::post('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'store'])->name('api.bookings.store');
    Route::get('/bookings/{booking}', [\App\Http\Controllers\Api\BookingController::class, 'show'])->name('api.bookings.show');
    Route::patch('/bookings/{booking}', [\App\Http\Controllers\Api\BookingController::class, 'update'])->name('api.bookings.update');
    Route::get('/availability', [\App\Http\Controllers\Api\AvailabilityController::class, 'index'])->name('api.availability.index');
    Route::post('/availability', [\App\Http\Controllers\Api\AvailabilityController::class, 'store'])->name('api.availability.store');
});

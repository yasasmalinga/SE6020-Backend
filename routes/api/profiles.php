<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/profiles/me', [\App\Http\Controllers\Api\ProfileController::class, 'show'])->name('api.profiles.me');
    Route::put('/profiles/me', [\App\Http\Controllers\Api\ProfileController::class, 'update'])->name('api.profiles.update');
});
Route::get('/interviewers', [\App\Http\Controllers\Api\InterviewerSearchController::class, 'index'])->name('api.interviewers.search');

<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/submissions', [\App\Http\Controllers\Api\SubmissionController::class, 'index'])->name('api.submissions.index');
    Route::post('/submissions', [\App\Http\Controllers\Api\SubmissionController::class, 'store'])->name('api.submissions.store');
    Route::get('/submissions/{submission}', [\App\Http\Controllers\Api\SubmissionController::class, 'show'])->name('api.submissions.show');
    Route::post('/submissions/{submission}/annotations', [\App\Http\Controllers\Api\SubmissionController::class, 'annotate'])->name('api.submissions.annotations.store');
});

<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/interviews', [\App\Http\Controllers\Api\InterviewController::class, 'index'])->name('api.interviews.index');
    Route::get('/interviews/{interview}', [\App\Http\Controllers\Api\InterviewController::class, 'show'])->name('api.interviews.show');
    Route::post('/interviews/{interview}/evaluation', [\App\Http\Controllers\Api\EvaluationController::class, 'store'])->name('api.evaluations.store');
    Route::patch('/interviews/{interview}/recording', [\App\Http\Controllers\Api\InterviewController::class, 'updateRecording'])->name('api.interviews.recording.update');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\InterviewerSearchController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\MessagingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RealtimeSessionController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\AuthController;


Route::middleware(['service.domain'])->group(function (): void {
    Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('api.health');

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
        Route::middleware(['auth.cognito'])->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        });
    });

    require __DIR__.'/api/profiles.php';
    require __DIR__.'/api/bookings.php';
    require __DIR__.'/api/interviews.php';
    require __DIR__.'/api/submissions.php';
    require __DIR__.'/api/messaging.php';
    require __DIR__.'/api/payments.php';
    require __DIR__.'/api/realtime.php';

    /*
    |--------------------------------------------------------------------------
    | Service-prefixed routes (microservice-aligned API surface)
    |--------------------------------------------------------------------------
    | These endpoints keep the current monolith code while exposing clear
    | domain boundaries for users, scheduling, and interaction services.
    */
    Route::prefix('users')->group(function (): void {
        Route::middleware(['auth.cognito'])->group(function (): void {
            Route::get('/profiles/me', [ProfileController::class, 'show'])->name('api.users.profiles.me');
            Route::put('/profiles/me', [ProfileController::class, 'update'])->name('api.users.profiles.update');
        });
        Route::get('/interviewers', [InterviewerSearchController::class, 'index'])->name('api.users.interviewers.search');
    });

    Route::prefix('scheduling')->middleware(['auth.cognito'])->group(function (): void {
        Route::get('/bookings', [BookingController::class, 'index'])->name('api.scheduling.bookings.index');
        Route::post('/bookings', [BookingController::class, 'store'])->name('api.scheduling.bookings.store');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('api.scheduling.bookings.show');
        Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('api.scheduling.bookings.update');

        Route::get('/availability', [AvailabilityController::class, 'index'])->name('api.scheduling.availability.index');
        Route::post('/availability', [AvailabilityController::class, 'store'])->name('api.scheduling.availability.store');

        Route::get('/interviews', [InterviewController::class, 'index'])->name('api.scheduling.interviews.index');
        Route::get('/interviews/{interview}', [InterviewController::class, 'show'])->name('api.scheduling.interviews.show');
        Route::post('/interviews/{interview}/evaluation', [EvaluationController::class, 'store'])->name('api.scheduling.evaluations.store');
    });

    Route::prefix('interaction')->middleware(['auth.cognito'])->group(function (): void {
        Route::get('/submissions', [SubmissionController::class, 'index'])->name('api.interaction.submissions.index');
        Route::post('/submissions', [SubmissionController::class, 'store'])->name('api.interaction.submissions.store');
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('api.interaction.submissions.show');

        Route::get('/conversations', [MessagingController::class, 'conversations'])->name('api.interaction.conversations.index');
        Route::post('/conversations', [MessagingController::class, 'createOrGet'])->name('api.interaction.conversations.store');
        Route::get('/conversations/{conversation}/messages', [MessagingController::class, 'messages'])->name('api.interaction.messages.index');
        Route::post('/conversations/{conversation}/messages', [MessagingController::class, 'store'])->name('api.interaction.messages.store');

        Route::get('/payments', [PaymentController::class, 'index'])->name('api.interaction.payments.index');
        Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->name('api.interaction.payments.initiate');
        Route::patch('/payments/{paymentId}/status', [PaymentController::class, 'updateStatus'])->name('api.interaction.payments.status');

        Route::get('/realtime/ice-servers', [RealtimeSessionController::class, 'iceServers'])->name('api.interaction.realtime.ice-servers');
        Route::post('/realtime/sessions', [RealtimeSessionController::class, 'create'])->name('api.interaction.realtime.create');
        Route::get('/realtime/sessions/{sessionId}', [RealtimeSessionController::class, 'show'])->name('api.interaction.realtime.show');
        Route::post('/realtime/sessions/{sessionId}/offer', [RealtimeSessionController::class, 'offer'])->name('api.interaction.realtime.offer');
        Route::post('/realtime/sessions/{sessionId}/answer', [RealtimeSessionController::class, 'answer'])->name('api.interaction.realtime.answer');
        Route::post('/realtime/sessions/{sessionId}/ice-candidates', [RealtimeSessionController::class, 'addIceCandidate'])->name('api.interaction.realtime.ice');
        Route::post('/realtime/sessions/{sessionId}/end', [RealtimeSessionController::class, 'end'])->name('api.interaction.realtime.end');
    });
});

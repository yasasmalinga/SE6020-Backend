<?php

use App\Http\Controllers\Api\RealtimeSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.cognito'])->group(function (): void {
    Route::get('/realtime/ice-servers', [RealtimeSessionController::class, 'iceServers'])->name('api.realtime.ice-servers');
    Route::post('/realtime/sessions', [RealtimeSessionController::class, 'create'])->name('api.realtime.sessions.create');
    Route::get('/realtime/sessions/{sessionId}', [RealtimeSessionController::class, 'show'])->name('api.realtime.sessions.show');
    Route::post('/realtime/sessions/{sessionId}/offer', [RealtimeSessionController::class, 'offer'])->name('api.realtime.sessions.offer');
    Route::post('/realtime/sessions/{sessionId}/answer', [RealtimeSessionController::class, 'answer'])->name('api.realtime.sessions.answer');
    Route::post('/realtime/sessions/{sessionId}/ice-candidates', [RealtimeSessionController::class, 'addIceCandidate'])->name('api.realtime.sessions.ice');
    Route::post('/realtime/sessions/{sessionId}/end', [RealtimeSessionController::class, 'end'])->name('api.realtime.sessions.end');
});

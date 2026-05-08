<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RealtimeSessionController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'force_new' => 'sometimes|boolean',
        ]);

        $booking = Booking::query()
            ->with(['candidate', 'interviewer'])
            ->findOrFail($validated['booking_id']);
        $participant = $this->participantForBooking($request, $booking);
        if (! $participant) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $existingSession = DB::table('realtime_sessions')
            ->where('booking_id', $booking->id)
            ->where('status', '!=', 'ended')
            ->orderByDesc('id')
            ->first();

        if ($existingSession && ! ($validated['force_new'] ?? false)) {
            return response()->json($existingSession);
        }

        if ($existingSession && ($validated['force_new'] ?? false)) {
            DB::table('realtime_sessions')
                ->where('id', $existingSession->id)
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $sessionId = DB::table('realtime_sessions')->insertGetId([
            'booking_id' => $booking->id,
            'room_token' => Str::random(32),
            'status' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = DB::table('realtime_sessions')->where('id', $sessionId)->first();

        return response()->json($session, 201);
    }

    public function show(Request $request, int $sessionId): JsonResponse
    {
        $sessionRow = DB::table('realtime_sessions')->where('id', $sessionId)->first();
        if (! $sessionRow) {
            return response()->json(['message' => 'Realtime session not found.'], Response::HTTP_NOT_FOUND);
        }

        $booking = Booking::query()
            ->with(['candidate', 'interviewer'])
            ->find($sessionRow->booking_id);
        if (! $booking) {
            return response()->json(['message' => 'Booking not found for this session.'], Response::HTTP_NOT_FOUND);
        }

        $userId = (int) $request->user()->id;
        $candidateUserId = (int) ($booking->candidate?->user_id ?? 0);
        $interviewerUserId = (int) ($booking->interviewer?->user_id ?? 0);
        if ($userId !== $candidateUserId && $userId !== $interviewerUserId) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return response()->json($sessionRow);
    }

    public function offer(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'offer_sdp' => 'required|string',
        ]);

        if (! $this->isParticipant($request, $sessionId)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::table('realtime_sessions')
            ->where('id', $sessionId)
            ->update([
                'offer_sdp' => $validated['offer_sdp'],
                'status' => 'active',
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->show($request, $sessionId);
    }

    public function answer(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'answer_sdp' => 'required|string',
        ]);

        if (! $this->isParticipant($request, $sessionId)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::table('realtime_sessions')
            ->where('id', $sessionId)
            ->update([
                'answer_sdp' => $validated['answer_sdp'],
                'updated_at' => now(),
            ]);

        return $this->show($request, $sessionId);
    }

    public function addIceCandidate(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'candidate' => 'required|array',
        ]);

        if (! $this->isParticipant($request, $sessionId)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $session = DB::table('realtime_sessions')->where('id', $sessionId)->first();
        if (! $session) {
            abort(404, 'Realtime session not found.');
        }

        $currentCandidates = json_decode($session->ice_candidates ?? '[]', true) ?: [];
        $currentCandidates[] = $validated['candidate'];

        DB::table('realtime_sessions')
            ->where('id', $sessionId)
            ->update([
                'ice_candidates' => json_encode($currentCandidates),
                'updated_at' => now(),
            ]);

        return $this->show($request, $sessionId);
    }

    public function end(Request $request, int $sessionId): JsonResponse
    {
        if (! $this->isParticipant($request, $sessionId)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        DB::table('realtime_sessions')
            ->where('id', $sessionId)
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->show($request, $sessionId);
    }

    private function participantForBooking(Request $request, Booking $booking): ?string
    {
        $userId = (int) $request->user()->id;
        $candidateUserId = (int) ($booking->candidate?->user_id ?? 0);
        $interviewerUserId = (int) ($booking->interviewer?->user_id ?? 0);

        if ($userId === $candidateUserId) {
            return 'candidate';
        }
        if ($userId === $interviewerUserId) {
            return 'interviewer';
        }

        return null;
    }

    private function isParticipant(Request $request, int $sessionId): bool
    {
        $sessionRow = DB::table('realtime_sessions')->where('id', $sessionId)->first();
        if (! $sessionRow) {
            return false;
        }

        $booking = Booking::query()
            ->with(['candidate', 'interviewer'])
            ->find($sessionRow->booking_id);
        if (! $booking) {
            return false;
        }

        $userId = (int) $request->user()->id;
        $candidateUserId = (int) ($booking->candidate?->user_id ?? 0);
        $interviewerUserId = (int) ($booking->interviewer?->user_id ?? 0);

        return $userId === $candidateUserId || $userId === $interviewerUserId;
    }
}

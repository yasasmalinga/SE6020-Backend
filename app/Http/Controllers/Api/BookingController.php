<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Booking::query()->with(['candidate.user', 'interviewer.user', 'availabilitySlot']);

        // Include both sides: a booking you made as candidate still appears after you switch to interviewer profile.
        $query->where(function ($q) use ($user): void {
            $q->whereHas('candidate', fn ($c) => $c->where('user_id', $user->id))
                ->orWhereHas('interviewer', fn ($i) => $i->where('user_id', $user->id));
        });

        $bookings = $query->latest()->paginate($request->input('per_page', 10));

        return response()->json($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isCandidate()) {
            return response()->json(['message' => 'Only candidates can create bookings.'], Response::HTTP_FORBIDDEN);
        }

        $candidate = $user->candidate ?? $user->candidate()->create(['user_id' => $user->id]);

        $validated = $request->validate([
            'interviewer_id' => 'required|exists:interviewers,id',
            'availability_slot_id' => 'nullable|exists:availability_slots,id',
            'scheduled_at' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $booking = Booking::query()->create([
            'candidate_id' => $candidate->id,
            'interviewer_id' => $validated['interviewer_id'],
            'availability_slot_id' => $validated['availability_slot_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
            'amount' => $validated['amount'] ?? 0,
            'status' => 'pending',
        ]);

        $booking->load(['interviewer.user', 'availabilitySlot']);

        return response()->json($booking, 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        if (! $this->canAccessBooking($booking, $booking->candidate?->user_id, $booking->interviewer?->user_id, request()->user()->id)) {
            return response()->json(['message' => 'Booking not found.'], Response::HTTP_NOT_FOUND);
        }

        $booking->load(['candidate.user', 'interviewer.user', 'availabilitySlot', 'interviewSession']);

        return response()->json($booking);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        $booking->loadMissing(['candidate', 'interviewer']);

        $candidateUserId = $booking->candidate?->user_id;
        $interviewerUserId = $booking->interviewer?->user_id;

        if (! $this->canAccessBooking($booking, $candidateUserId, $interviewerUserId, $user->id)) {
            return response()->json(['message' => 'Booking not found.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:accepted,rejected,completed,cancelled',
        ]);

        if (array_key_exists('status', $validated) && ! $this->canUpdateStatus($validated['status'], $user->id, $candidateUserId, $interviewerUserId)) {
            return response()->json(['message' => 'You are not allowed to set this status.'], Response::HTTP_FORBIDDEN);
        }

        $booking->update($validated);

        return response()->json($booking->fresh());
    }

    private function canAccessBooking(Booking $booking, ?int $candidateUserId, ?int $interviewerUserId, int $userId): bool
    {
        return $candidateUserId === $userId || $interviewerUserId === $userId;
    }

    private function canUpdateStatus(string $status, int $userId, ?int $candidateUserId, ?int $interviewerUserId): bool
    {
        if ($status === 'cancelled') {
            return $userId === $candidateUserId;
        }

        if (in_array($status, ['accepted', 'rejected', 'completed'], true)) {
            return $userId === $interviewerUserId;
        }

        return false;
    }
}

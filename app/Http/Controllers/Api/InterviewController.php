<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\InterviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = InterviewSession::query()->with(['booking.candidate.user', 'booking.interviewer.user', 'evaluation']);

        if ($user->isCandidate()) {
            $query->whereHas('booking.candidate', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->whereHas('booking.interviewer', fn ($q) => $q->where('user_id', $user->id));
        }

        $interviews = $query->latest()->paginate($request->input('per_page', 10));

        return response()->json($interviews);
    }

    public function show(InterviewSession $interview): JsonResponse
    {
        $interview->load(['booking.candidate.user', 'booking.interviewer.user', 'evaluation']);

        return response()->json($interview);
    }

    public function updateRecording(Request $request, InterviewSession $interview): JsonResponse
    {
        $user = $request->user();
        $belongsToInterviewer = $interview->booking()
            ->whereHas('interviewer', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        if (! $belongsToInterviewer) {
            return response()->json(['message' => 'Only the interviewer can update recording URL.'], 403);
        }

        $validated = $request->validate([
            'recording_url' => 'required|url|max:2000',
        ]);

        $interview->update([
            'recording_url' => $validated['recording_url'],
        ]);

        return response()->json($interview->fresh()->load(['booking.candidate.user', 'booking.interviewer.user', 'evaluation']));
    }
}

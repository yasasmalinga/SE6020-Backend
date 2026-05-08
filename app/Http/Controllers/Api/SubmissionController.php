<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\SubmissionAnnotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Submission::query()->with(['booking', 'candidate.user', 'annotations.interviewer.user']);

        if ($user->isCandidate()) {
            $candidate = $user->candidate;
            if (! $candidate) {
                return response()->json(['data' => []]);
            }
            $query->where('candidate_id', $candidate->id);
        } else {
            $interviewer = $user->interviewer;
            if (! $interviewer) {
                return response()->json(['data' => []]);
            }
            $query->whereHas('booking', fn ($q) => $q->where('interviewer_id', $interviewer->id));
        }

        $submissions = $query->latest()->paginate($request->input('per_page', 10));

        return response()->json($submissions);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $candidate = $user->candidate ?? $user->candidate()->create([]);

        $validated = $request->validate([
            'type' => 'required|in:file,github_link',
            'file' => 'required_if:type,file|file|max:10240',
            'github_url' => 'required_if:type,github_link|url',
            'booking_id' => 'nullable|exists:bookings,id',
            'notes' => 'nullable|string',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions/'.$candidate->id, 'local');
        }

        $submission = Submission::query()->create([
            'candidate_id' => $candidate->id,
            'booking_id' => $validated['booking_id'] ?? null,
            'type' => $validated['type'],
            'file_path' => $filePath,
            'github_url' => $validated['github_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($submission->load(['booking', 'candidate.user', 'annotations.interviewer.user']), 201);
    }

    public function show(Request $request, Submission $submission): JsonResponse
    {
        if (! $this->canAccessSubmission($request->user(), $submission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($submission->load(['booking', 'candidate.user', 'annotations.interviewer.user']));
    }

    public function annotate(Request $request, Submission $submission): JsonResponse
    {
        $user = $request->user();
        $interviewer = $user->interviewer;
        if (! $interviewer) {
            return response()->json(['message' => 'Only interviewers can annotate submissions.'], 403);
        }
        if (! $this->canAccessSubmission($user, $submission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $annotation = SubmissionAnnotation::query()->create([
            'submission_id' => $submission->id,
            'interviewer_id' => $interviewer->id,
            'body' => $validated['body'],
        ]);

        return response()->json($annotation->load('interviewer.user'), 201);
    }

    private function canAccessSubmission($user, Submission $submission): bool
    {
        $submission->loadMissing(['candidate', 'booking']);

        if ($user->isCandidate()) {
            return $submission->candidate?->user_id === $user->id;
        }

        $interviewer = $user->interviewer;

        return (bool) $interviewer && $submission->booking?->interviewer_id === $interviewer->id;
    }
}

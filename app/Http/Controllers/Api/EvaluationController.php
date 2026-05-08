<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\InterviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function store(Request $request, InterviewSession $interview): JsonResponse
    {
        $user = $request->user();
        $interviewer = $user->interviewer;
        if (! $interviewer) {
            return response()->json(['message' => 'Only interviewers can submit evaluations.'], 403);
        }

        $validated = $request->validate([
            'scores' => 'nullable|array',
            'feedback' => 'required|string',
        ]);

        $evaluation = Evaluation::query()->updateOrCreate(
            [
                'interview_session_id' => $interview->id,
                'interviewer_id' => $interviewer->id,
            ],
            [
                'candidate_id' => $interview->booking->candidate_id,
                'scores' => $validated['scores'] ?? [],
                'feedback' => $validated['feedback'],
                'submitted_at' => now(),
            ]
        );

        return response()->json($evaluation->load('candidate'), 201);
    }
}
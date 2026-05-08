<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Conversation::query()->with(['candidate.user', 'interviewer.user']);

        if ($user->isCandidate()) {
            $query->whereHas('candidate', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->whereHas('interviewer', fn ($q) => $q->where('user_id', $user->id));
        }

        $conversations = $query->latest('updated_at')->paginate($request->input('per_page', 20));

        return response()->json($conversations);
    }

    public function createOrGet(Request $request): JsonResponse
    {
        $user = $request->user();
        $candidate = $user->candidate ?? $user->candidate()->create([]);
        $validated = $request->validate(['interviewer_id' => 'required|exists:interviewers,id']);
        $conversation = Conversation::query()->firstOrCreate(
            [
                'candidate_id' => $candidate->id,
                'interviewer_id' => $validated['interviewer_id'],
            ],
            ['candidate_id' => $candidate->id, 'interviewer_id' => $validated['interviewer_id']]
        );
        $conversation->load(['candidate.user', 'interviewer.user']);

        return response()->json($conversation, 201);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $isParticipant = $user->isCandidate()
            ? $conversation->candidate_id === $user->candidate?->id
            : $conversation->interviewer_id === $user->interviewer?->id;
        if (! $isParticipant) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()->with('user:id,name')->latest()->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $isParticipant = $user->isCandidate()
            ? $conversation->candidate_id === $user->candidate?->id
            : $conversation->interviewer_id === $user->interviewer?->id;
        if (! $isParticipant) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate(['body' => 'required|string|max:5000']);
        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);
        $message->load('user:id,name');

        return response()->json($message, 201);
    }
}

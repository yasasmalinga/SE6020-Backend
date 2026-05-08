<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $interviewer = $user->interviewer;
        if (! $interviewer) {
            return response()->json(['data' => []]);
        }
        $slots = AvailabilitySlot::query()
            ->where('interviewer_id', $interviewer->id)
            ->where('is_available', true)
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($slots);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $interviewer = $user->interviewer ?? $user->interviewer()->create([]);

        $validated = $request->validate([
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
        ]);

        $slot = AvailabilitySlot::query()->create([
            'interviewer_id' => $interviewer->id,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'is_available' => true,
        ]);

        return response()->json($slot, 201);
    }
}

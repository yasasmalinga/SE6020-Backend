<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interviewer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewerSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Interviewer::query()->with('user:id,name,email');

        if ($request->filled('domain')) {
            $query->whereJsonContains('domains', $request->input('domain'));
        }
        if ($request->filled('interview_type')) {
            $query->whereJsonContains('interview_types', $request->input('interview_type'));
        }
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->input('experience_level'));
        }
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->input('min_rating'));
        }
        if ($request->filled('badge')) {
            $query->whereJsonContains('badges', $request->input('badge'));
        }
        if ($request->filled('availability_from')) {
            $query->whereHas('availabilitySlots', function ($q) use ($request): void {
                $q->where('is_available', true)
                    ->where('start_at', '>=', $request->input('availability_from'))
                    ->where('end_at', '<=', $request->input('availability_to', now()->addMonths(1)));
            });
        }

        $interviewers = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json($interviewers);
    }
}

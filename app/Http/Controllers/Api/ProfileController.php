<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Interviewer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->isCandidate() ? $user->candidate : $user->interviewer;
        if (! $profile) {
            return response()->json([
                'user' => $user->only(['id', 'name', 'email', 'profile_type']),
                'profile' => null,
            ]);
        }
        $profile->load('user:id,name,email,profile_type');

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'profile_type']),
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profileType = $request->input('profile_type', $user->profile_type);

        if ($profileType === 'interviewer') {
            $user->update(['profile_type' => 'interviewer']);
            $profile = $user->interviewer()->firstOrCreate([], [
                'bio' => $request->input('bio'),
                'domains' => $request->input('domains'),
                'interview_types' => $request->input('interview_types'),
                'experience_level' => $request->input('experience_level'),
                'hourly_rate' => $request->input('hourly_rate', 0),
                'rating' => $request->input('rating'),
                'badges' => $request->input('badges'),
            ]);
            $profile->update($request->only(['bio', 'domains', 'interview_types', 'experience_level', 'hourly_rate', 'rating', 'badges']));
        } else {
            $user->update(['profile_type' => 'candidate']);
            $profile = $user->candidate()->firstOrCreate([], [
                'bio' => $request->input('bio'),
                'preferred_domains' => $request->input('preferred_domains'),
                'experience_level' => $request->input('experience_level'),
            ]);
            $profile->update($request->only(['bio', 'preferred_domains', 'experience_level']));
        }

        return response()->json(['profile' => $profile->fresh()->load('user')]);
    }
}

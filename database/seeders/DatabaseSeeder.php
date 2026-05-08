<?php

namespace Database\Seeders;

use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Candidate;
use App\Models\Interviewer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $candidateUser = User::query()->updateOrCreate(
            ['email' => 'candidate@hiresphere.test'],
            [
                'name' => 'Demo Candidate',
                'password' => 'password123',
                'profile_type' => 'candidate',
            ]
        );

        $candidate = Candidate::query()->updateOrCreate(
            ['user_id' => $candidateUser->id],
            [
                'bio' => 'Final year student preparing for backend interviews.',
                'preferred_domains' => ['Backend', 'DevOps'],
                'experience_level' => 'junior',
            ]
        );

        $interviewerUser = User::query()->updateOrCreate(
            ['email' => 'interviewer@hiresphere.test'],
            [
                'name' => 'Demo Interviewer',
                'password' => 'password123',
                'profile_type' => 'interviewer',
            ]
        );

        $interviewer = Interviewer::query()->updateOrCreate(
            ['user_id' => $interviewerUser->id],
            [
                'bio' => 'Senior backend engineer with 8+ years of interview experience.',
                'domains' => ['Backend', 'System Design'],
                'interview_types' => ['DSA', 'System Design', 'Behavioral'],
                'experience_level' => 'Senior',
                'hourly_rate' => 35,
                'rating' => 4.8,
                'badges' => ['Top Mentor', 'System Design Expert'],
            ]
        );

        $slotStart = Carbon::now()->addDays(1)->setTime(10, 0);
        $slot = AvailabilitySlot::query()->updateOrCreate(
            [
                'interviewer_id' => $interviewer->id,
                'start_at' => $slotStart,
            ],
            [
                'end_at' => (clone $slotStart)->addHour(),
                'is_available' => true,
            ]
        );

        Booking::query()->updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'interviewer_id' => $interviewer->id,
                'scheduled_at' => $slotStart,
            ],
            [
                'availability_slot_id' => $slot->id,
                'status' => 'pending',
                'amount' => 35,
            ]
        );

        User::query()->updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'password' => 'password123',
            'profile_type' => 'candidate',
        ]);
    }
}

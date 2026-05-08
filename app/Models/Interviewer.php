<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interviewer extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'domains',
        'interview_types',
        'experience_level',
        'hourly_rate',
        'rating',
        'badges',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'interview_types' => 'array',
            'badges' => 'array',
            'hourly_rate' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}

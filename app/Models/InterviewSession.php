<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InterviewSession extends Model
{
    protected $fillable = [
        'booking_id',
        'started_at',
        'ended_at',
        'recording_url',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class, 'interview_session_id');
    }
}

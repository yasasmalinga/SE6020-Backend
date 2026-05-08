<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $fillable = [
        'interview_session_id',
        'interviewer_id',
        'candidate_id',
        'scores',
        'feedback',
        'submitted_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(Interviewer::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}

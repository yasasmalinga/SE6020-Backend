<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAnnotation extends Model
{
    protected $fillable = [
        'submission_id',
        'interviewer_id',
        'body',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(Interviewer::class);
    }
}

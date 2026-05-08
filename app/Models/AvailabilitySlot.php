<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilitySlot extends Model
{
    protected $fillable = [
        'interviewer_id',
        'start_at',
        'end_at',
        'is_available',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_available' => 'boolean',
        ];
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(Interviewer::class);
    }
}

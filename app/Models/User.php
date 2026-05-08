<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cognito_id',
        'profile_type',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function candidate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    public function interviewer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Interviewer::class);
    }

    public function isCandidate(): bool
    {
        return $this->profile_type === 'candidate';
    }

    public function isInterviewer(): bool
    {
        return $this->profile_type === 'interviewer';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

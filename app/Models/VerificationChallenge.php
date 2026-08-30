<?php

namespace App\Models;

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use Illuminate\Database\Eloquent\Model;

class VerificationChallenge extends Model
{
    protected $fillable = [
        'first_name_fa',
        'last_name_fa',
        'nationality_type',
        'identity',
        'mobile',
        'purpose',
        'verification_code',
        'fingerprint',
        'ip',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'nationality_type' => NationalityType::class,
            'purpose' => VerificationPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use Illuminate\Database\Eloquent\Builder;
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
        'sms_sent_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => VerificationPurpose::class,
            'nationality_type' => NationalityType::class,

            'attempts' => 'integer',

            'expires_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }
}

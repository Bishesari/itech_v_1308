<?php

namespace App\Exceptions\Verification;

class ActiveVerificationChallengeException extends VerificationException
{
    public function __construct()
    {
        parent::__construct(
            'ارسال مجدد کد تا پایان زمان انتظار امکان‌پذیر نیست.'
        );
    }
}

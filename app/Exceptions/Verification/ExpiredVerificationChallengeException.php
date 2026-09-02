<?php

namespace App\Exceptions\Verification;

use RuntimeException;

class ExpiredVerificationChallengeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'کد تأیید منقضی شده است.'
        );
    }
}

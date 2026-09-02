<?php

namespace App\Exceptions\Verification;

use RuntimeException;

class InvalidVerificationCodeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'کد تأیید وارد شده صحیح نیست.'
        );
    }
}

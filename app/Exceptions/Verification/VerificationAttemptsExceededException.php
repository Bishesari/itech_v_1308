<?php

namespace App\Exceptions\Verification;

use RuntimeException;

class VerificationAttemptsExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'تعداد تلاش‌های وارد کردن کد تأیید به پایان رسیده است.'
        );
    }
}

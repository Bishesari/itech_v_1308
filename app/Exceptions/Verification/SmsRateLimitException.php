<?php

namespace App\Exceptions\Verification;

use Exception;

class SmsRateLimitException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'تعداد درخواست‌های ارسال پیامک بیش از حد مجاز است.'
        );
    }
}

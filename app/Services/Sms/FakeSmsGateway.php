<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;

class FakeSmsGateway implements SmsGateway
{
    public function sendOtp(
        string $mobile,
        string $verificationCode,
    ): void {

        logger()->info('OTP', [
            'mobile' => $mobile,
            'code' => $verificationCode,
        ]);

    }
}

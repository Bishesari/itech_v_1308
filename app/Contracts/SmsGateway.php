<?php

namespace App\Contracts;

interface SmsGateway
{
    /**
     * @throws \Throwable
     */
    public function sendOtp(
        string $mobile,
        string $verificationCode,
    ): void;
}

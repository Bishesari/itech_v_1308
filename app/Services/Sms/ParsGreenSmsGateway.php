<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Exceptions\Verification\SmsDeliveryException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ParsGreenSmsGateway implements SmsGateway
{
    public function sendOtp(string $mobile, string $verificationCode): void
    {
        try {
            $response = Http::baseUrl(config('services.pars_green.url'))
                ->withHeaders([
                    'authorization' => 'BASIC APIKEY:'.config('services.pars_green.api_key'),
                    'Content-Type' => 'application/json;charset=utf-8',
                ])
                ->post('/Apiv2/Message/SendOtp', [
                    'Mobile' => $mobile,
                    'SmsCode' => $verificationCode,
                    'TemplateId' => config('services.pars_green.otp_template_id'),
                    'AddName' => false,
                ]);

            $result = $response->json();

            if (! $response->successful() || ! ($result['R_Success'] ?? false)) {
                throw new SmsDeliveryException(
                    $result['R_Message'] ?? 'ارسال پیامک با خطا مواجه شد.'
                );
            }
        } catch (SmsDeliveryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SmsDeliveryException(
                'ارتباط با سرویس پیامک برقرار نشد.',
                previous: $e,
            );
        }
    }
}

<?php

use App\Exceptions\Verification\SmsDeliveryException;
use App\Services\Sms\ParsGreenSmsGateway;
use Illuminate\Support\Facades\Http;

it('sends otp through pars green api', function () {
    Http::fake([
        'https://sms.parsgreen.ir/Apiv2/Message/SendOtp' => Http::response([
            'R_Success' => true,
            'R_Code' => 0,
            'R_Message' => 'success',
        ]),
    ]);

    $gateway = app(ParsGreenSmsGateway::class);

    $gateway->sendOtp('09123456789', '123456');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sms.parsgreen.ir/Apiv2/Message/SendOtp'
            && $request->header('authorization') === [
                'BASIC APIKEY:'.config('services.pars_green.api_key'),
            ]
            && $request['Mobile'] === '09123456789'
            && $request['SmsCode'] === '123456'
            && $request['TemplateId'] === config('services.pars_green.otp_template_id')
            && $request['AddName'] === false;
    });
});

it('throws sms delivery exception when pars green rejects the otp', function () {
    Http::fake([
        'https://sms.parsgreen.ir/Apiv2/Message/SendOtp' => Http::response([
            'R_Success' => false,
            'R_Code' => 123,
            'R_Message' => 'خطا در ارسال پیامک',
        ]),
    ]);

    $gateway = app(ParsGreenSmsGateway::class);

    expect(fn () => $gateway->sendOtp('09123456789', '123456'))
        ->toThrow(SmsDeliveryException::class, 'خطا در ارسال پیامک');
});

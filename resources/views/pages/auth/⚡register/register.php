<?php

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Exceptions\Verification\ActiveVerificationChallengeException;
use App\Exceptions\Verification\ExpiredVerificationChallengeException;
use App\Exceptions\Verification\InvalidVerificationCodeException;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Exceptions\Verification\VerificationAttemptsExceededException;
use App\Exceptions\Verification\VerificationChallengeNotFoundException;
use App\Rules\NationalCode;
use App\Rules\NotIranianNationalCode;
use App\Rules\PersianName;
use App\Services\Verification\VerificationChallengeService;
use App\Support\Digits;
use App\Support\PersianText;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('ثبت نام')]
class extends Component
{
    public string $first_name_fa = '';

    public string $last_name_fa = '';

    public NationalityType $nationality_type = NationalityType::Iranian;

    public string $identity = '';

    public string $mobile = '';

    public string $otp = '';
    public ?string $otp_expires_at = null;

    public ?string $fingerprint = null;

    protected array $normalizers = [
        'first_name_fa' => [PersianText::class, 'name'],
        'last_name_fa' => [PersianText::class, 'name'],
        'identity' => [Digits::class, 'onlyDigits'],
        'mobile' => [Digits::class, 'onlyDigits'],
        'otp' => [Digits::class, 'onlyDigits'],
    ];

    public function updated($property): void
    {
        $this->normalizeField($property);
    }

    protected function normalizeField(string $field): void
    {
        if (! isset($this->normalizers[$field])) {
            return;
        }

        $callable = $this->normalizers[$field];

        $value = (string) ($this->$field ?? '');

        $this->$field = (string) $callable($value);
    }

    public function normalizeAll(): void
    {
        foreach (array_keys($this->normalizers) as $field) {
            $this->normalizeField($field);
        }
    }

    protected function rules(): array
    {
        return [
            'first_name_fa' => [
                'bail',
                'required',
                'string',
                'min:2',
                'max:30',
                new PersianName,
            ],

            'last_name_fa' => [
                'bail',
                'required',
                'string',
                'min:2',
                'max:40',
                new PersianName,
            ],

            'nationality_type' => [
                'required',
                Rule::enum(NationalityType::class),
            ],

            'identity' => [
                'bail',
                'required',
                'string',

                Rule::when(
                    $this->nationality_type === NationalityType::Iranian,
                    [
                        'digits:10',
                        new NationalCode,
                    ],
                    [
                        'digits_between:6,20',
                        new NotIranianNationalCode,
                    ],
                ),

                'unique:people,identity',
            ],

            'mobile' => [
                'bail',
                'required',
                'digits:11',
                'starts_with:09',
            ],
        ];
    }

    public function continueRegister(
        VerificationChallengeService $service
    ): void {

        $this->normalizeAll();
        $this->validate();

        try {
            $challenge = $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: $this->first_name_fa,
                lastNameFa: $this->last_name_fa,
                nationalityType: $this->nationality_type,
                identity: $this->identity,
                mobile: $this->mobile,
                fingerprint: $this->fingerprint,
                ip: request()->ip(),
            );
        } catch (SmsDeliveryException) {
            $this->addError(
                'verification',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً چند لحظه دیگر دوباره تلاش کنید.'
            );

            return;
        }

        $this->otp = '';
        $this->otp_expires_at = $challenge->expires_at->toISOString();

        $this->modal('verify-otp')->show();
    }

    public function resendOtp(
        VerificationChallengeService $service
    ): void {
        try {
            $challenge = $service->resend(
                purpose: VerificationPurpose::Registration,
                mobile: $this->mobile,
                fingerprint: $this->fingerprint,
                ip: request()->ip(),
            );
        } catch (VerificationChallengeNotFoundException) {
            $this->addError(
                'otp',
                'کد تأیید معتبر یا فعالی برای این شماره وجود ندارد. لطفاً کد جدید درخواست کنید.'
            );
            return;

        } catch (ActiveVerificationChallengeException) {
            $this->addError(
                'otp',
                'کد تأیید فعلی هنوز معتبر است.'
            );
            return;
        } catch (SmsDeliveryException) {
            $this->addError(
                'otp',
                'ارسال مجدد کد با مشکل مواجه شد. لطفاً دوباره تلاش کنید.'
            );
            return;
        }

        $this->resetErrorBag('otp');

        $this->otp = '';
        $this->otp_expires_at = $challenge->expires_at->toISOString();
    }

    public function verifyOtp(
        VerificationChallengeService $service
    ): void {
        $this->normalizeField('otp');

        $this->validate([
            'otp' => [
                'required',
                'digits:6',
            ],
        ]);

        try {
            $service->verify(
                purpose: VerificationPurpose::Registration,
                mobile: $this->mobile,
                verificationCode: $this->otp,
            );
        } catch (InvalidVerificationCodeException) {
            $this->addError(
                'otp',
                'کد تأیید وارد شده صحیح نیست.'
            );

            return;
        } catch (ExpiredVerificationChallengeException) {
            $this->addError(
                'otp',
                'کد تأیید منقضی شده است. لطفاً کد جدید درخواست کنید.'
            );

            return;
        } catch (VerificationAttemptsExceededException) {
            $this->addError(
                'otp',
                'تعداد تلاش‌های مجاز به پایان رسیده است. لطفاً کد جدید درخواست کنید.'
            );

            return;
        } catch (VerificationChallengeNotFoundException) {
            $this->addError(
                'otp',
                'کد تأیید معتبر یا فعالی برای این شماره وجود ندارد. لطفاً کد جدید درخواست کنید.'
            );

            return;
        }


        $this->resetErrorBag('otp');
        $this->otp = '';
        $this->otp_expires_at = null;

        // مرحله ثبت نهایی کاربر بعداً اینجا قرار می‌گیرد.
    }
};

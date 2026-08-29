<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class NotIranianNationalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return; // قانون string خودش گزارش می‌دهد
        }

        // فقط ورودی دقیقاً ۱۰ رقمی می‌تواند کد ملی باشد؛ بقیه اصلاً بررسی نمی‌شوند
        if (strlen($value) !== 10 || ! ctype_digit($value)) {
            return;
        }

        if (NationalCode::isValid($value)) {
            $fail('این شماره یک کد ملی ایرانی است. اگر تابعیت شما ایرانی است، نوع تابعیت را اصلاح کنید.');
        }
    }
}

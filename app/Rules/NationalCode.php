<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class NationalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail('کد ملی وارد شده معتبر نیست.');
        }
    }

    /**
     * بررسی چک‌سام کد ملی ایرانی.
     * ورودی باید از قبل نرمال شده باشد (ارقام انگلیسی، بدون فاصله و خط تیره).
     */
    public static function isValid(string $value): bool
    {
        // دقیقاً ۱۰ رقم
        if (strlen($value) !== 10 || ! ctype_digit($value)) {
            return false;
        }

        // ارقام تکراری (۰۰۰۰۰۰۰۰۰۰ تا ۹۹۹۹۹۹۹۹۹۹) چک‌سام را پاس می‌کنند اما معتبر نیستند
        if (preg_match('/^(\d)\1{9}$/', $value) === 1) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $value[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $checkDigit = (int) $value[9];

        return $remainder < 2
            ? $checkDigit === $remainder
            : $checkDigit === 11 - $remainder;
    }
}

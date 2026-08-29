<?php

declare(strict_types=1);

namespace App\Support;

final class Digits
{
    /** ارقام فارسی */
    private const array PERSIAN = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /** ارقام عربی */
    private const array ARABIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    /** ارقام انگلیسی */
    private const array ENGLISH = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * ارقام فارسی و عربی را به ارقام انگلیسی تبدیل می‌کند.
     *
     * مثال:
     * ۱۲٣۴۵ → 12345
     */
    public static function toEnglish(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return str_replace([...self::PERSIAN, ...self::ARABIC], [...self::ENGLISH, ...self::ENGLISH], $value);
    }

    /**
     * فقط ارقام انگلیسی را نگه می‌دارد.
     *
     * ابتدا ارقام فارسی و عربی به انگلیسی تبدیل می‌شوند،
     * سپس تمام کاراکترهای غیرعددی حذف می‌شوند.
     *
     * مثال:
     * ۱۲۳-۴۵۶ ۷۸۹۰ → 1234567890
     */
    public static function onlyDigits(?string $value): string
    {
        return preg_replace('/\D+/', '', self::toEnglish($value)) ?? '';
    }
}

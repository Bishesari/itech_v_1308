<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PersianName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // چون قبلاً نرمال‌سازی شده، فقط کاراکترهای مجاز را چک می‌کنیم
        if (!preg_match('/^[آ-یء\s\x{200C}]+$/u', $value)) {
            $fail('فیلد :attribute باید فقط شامل حروف فارسی باشد.');
        }
    }
}

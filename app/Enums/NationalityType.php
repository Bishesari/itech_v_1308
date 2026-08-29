<?php

declare(strict_types=1);

namespace App\Enums;

enum NationalityType: int
{
    case Iranian = 1;
    case Foreign = 2;

    public function label(): string
    {
        return match ($this) {
            self::Iranian => 'ایرانی',
            self::Foreign => 'اتباع خارجی',
        };
    }
}

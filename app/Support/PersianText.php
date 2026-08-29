<?php

declare(strict_types=1);

namespace App\Support;

final class PersianText
{
    /** نیم‌فاصله */
    public const string ZWNJ = "\u{200C}";

    private const int MAX_LENGTH = 100;

    /**
     * فرم‌های نمایشی عربی (Presentation Forms A/B) → حروف پایه.
     * قالب: [از، تا، جایگزین]
     */
    private const array FORM_RANGES = [
        // ---- Forms-B : اعراب چسبیده (حذف) ----
        [0xFE70, 0xFE7F, ''],

        // ---- Forms-B : حروف ----
        [0xFE80, 0xFE80, 'ء'],
        [0xFE81, 0xFE82, 'آ'],   // مدّ حفظ می‌شود
        [0xFE83, 0xFE84, 'ا'],   // أ
        [0xFE85, 0xFE86, 'ؤ'],
        [0xFE87, 0xFE88, 'ا'],   // إ
        [0xFE89, 0xFE8C, 'ئ'],
        [0xFE8D, 0xFE8E, 'ا'],
        [0xFE8F, 0xFE92, 'ب'],
        [0xFE93, 0xFE94, 'ه'],   // ة
        [0xFE95, 0xFE98, 'ت'],
        [0xFE99, 0xFE9C, 'ث'],
        [0xFE9D, 0xFEA0, 'ج'],
        [0xFEA1, 0xFEA4, 'ح'],
        [0xFEA5, 0xFEA8, 'خ'],
        [0xFEA9, 0xFEAA, 'د'],
        [0xFEAB, 0xFEAC, 'ذ'],
        [0xFEAD, 0xFEAE, 'ر'],
        [0xFEAF, 0xFEB0, 'ز'],
        [0xFEB1, 0xFEB4, 'س'],
        [0xFEB5, 0xFEB8, 'ش'],
        [0xFEB9, 0xFEBC, 'ص'],
        [0xFEBD, 0xFEC0, 'ض'],
        [0xFEC1, 0xFEC4, 'ط'],
        [0xFEC5, 0xFEC8, 'ظ'],
        [0xFEC9, 0xFECC, 'ع'],
        [0xFECD, 0xFED0, 'غ'],
        [0xFED1, 0xFED4, 'ف'],
        [0xFED5, 0xFED8, 'ق'],
        [0xFED9, 0xFEDC, 'ک'],   // ك
        [0xFEDD, 0xFEE0, 'ل'],
        [0xFEE1, 0xFEE4, 'م'],
        [0xFEE5, 0xFEE8, 'ن'],
        [0xFEE9, 0xFEEC, 'ه'],
        [0xFEED, 0xFEEE, 'و'],
        [0xFEEF, 0xFEF0, 'ی'],   // ى
        [0xFEF1, 0xFEF4, 'ی'],   // ي

        // ---- Forms-B : لیگاتور لام‌الف ----
        [0xFEF5, 0xFEF6, 'لآ'],
        [0xFEF7, 0xFEF8, 'لا'],  // لأ
        [0xFEF9, 0xFEFA, 'لا'],  // لإ
        [0xFEFB, 0xFEFC, 'لا'],

        // ---- Forms-A : حروف فارسی/اردو ----
        [0xFB50, 0xFB51, 'ا'],   // ٱ
        [0xFB56, 0xFB59, 'پ'],
        [0xFB66, 0xFB69, 'ت'],   // ٹ
        [0xFB7A, 0xFB7D, 'چ'],
        [0xFB88, 0xFB89, 'د'],   // ڈ
        [0xFB8A, 0xFB8B, 'ژ'],
        [0xFB8C, 0xFB8D, 'ر'],   // ڑ
        [0xFB8E, 0xFB91, 'ک'],
        [0xFB92, 0xFB95, 'گ'],
        [0xFB9E, 0xFB9F, 'ن'],   // ں
        [0xFBA6, 0xFBA9, 'ه'],   // ہ
        [0xFBAA, 0xFBAD, 'ه'],   // ھ
        [0xFBAE, 0xFBAF, 'ی'],   // ے
        [0xFBFC, 0xFBFF, 'ی'],   // ی فارسی
        [0xFDF2, 0xFDF2, 'الله'],
    ];

    /** ترکیب همزه/مدّ جدا افتاده (کاری که NFC می‌کرد) */
    private const array PRECOMPOSE = [
        "ا\u{0653}" => 'آ',
        "ا\u{0654}" => 'ا',   // أ
        "ا\u{0655}" => 'ا',   // إ
        "و\u{0654}" => 'ؤ',
        "ی\u{0654}" => 'ئ',
        "ي\u{0654}" => 'ئ',
        "ه\u{0654}" => 'ه',   // ۀ
    ];

    /** یکسان‌سازی حروف عربی/اردو/پشتو به فارسی */
    private const array LETTER_MAP = [
        // ی
        "\u{064A}" => 'ی', "\u{0649}" => 'ی', "\u{06CD}" => 'ی',
        "\u{06D0}" => 'ی', "\u{06D2}" => 'ی', "\u{06D3}" => 'ی',

        // ک
        "\u{0643}" => 'ک', "\u{06AA}" => 'ک',

        // ه
        "\u{0629}" => 'ه', "\u{06C0}" => 'ه', "\u{06C1}" => 'ه',
        "\u{06C2}" => 'ه', "\u{06D5}" => 'ه',

        // ا  (آ عمداً حفظ می‌شود)
        "\u{0623}" => 'ا', "\u{0625}" => 'ا', "\u{0671}" => 'ا',
        "\u{0672}" => 'ا', "\u{0673}" => 'ا', "\u{0675}" => 'ا',

        // ؤ و ئ عمداً دست‌نخورده (مؤمنی / رئیسی)
    ];

    /** @var array<string,string>|null */
    private static ?array $formMap = null;

    public static function name(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // 0) سقف طول، قبل از پردازش سنگین
        $value = mb_substr($value, 0, self::MAX_LENGTH * 3, 'UTF-8');

        // 1) فرم‌های نمایشی → حروف استاندارد
        $value = strtr($value, self::formMap());

        // 2) ترکیب همزه/مدّ جدا افتاده (قبل از حذف اعراب!)
        $value = strtr($value, self::PRECOMPOSE);

        // 3) یکسان‌سازی حروف
        $value = strtr($value, self::LETTER_MAP);

        // 4) حذف اعراب، کشیده، کنترل و نامرئی‌ها (به‌جز نیم‌فاصله)
        $value = (string) preg_replace(
            [
                '/[\p{Mn}\p{Me}\x{0640}\p{Cc}]/u',
                '/[^\P{Cf}\x{200C}]/u',
            ],
            '',
            $value
        );

        // 5) هر نوع فاصله → فاصله معمولی
        $value = (string) preg_replace('/[\p{Z}\s]+/u', ' ', $value);

        // 6) بهداشت نیم‌فاصله — ترتیب مهم است
        $value = (string) preg_replace(
            [
                '/ *'.self::ZWNJ.' */u',
                '/'.self::ZWNJ.'{2,}/u',
                '/ {2,}/u',
            ],
            [self::ZWNJ, self::ZWNJ, ' '],
            $value
        );

        // 7) trim فاصله و نیم‌فاصله از دو سر
        $value = (string) preg_replace(
            '/^[ '.self::ZWNJ.']+|[ '.self::ZWNJ.']+$/u',
            '',
            $value
        );

        $value = mb_substr($value, 0, self::MAX_LENGTH, 'UTF-8');

        return $value === '' ? null : $value;
    }

    /** @return array<string,string> */
    private static function formMap(): array
    {
        if (self::$formMap !== null) {
            return self::$formMap;
        }

        $map = [];

        foreach (self::FORM_RANGES as [$from, $to, $replacement]) {
            for ($cp = $from; $cp <= $to; $cp++) {
                $map[mb_chr($cp, 'UTF-8')] = $replacement;
            }
        }

        return self::$formMap = $map;
    }
}

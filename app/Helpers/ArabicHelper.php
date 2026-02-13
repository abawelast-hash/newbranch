<?php

namespace App\Helpers;

class ArabicHelper
{
    /**
     * Map Western digits → Eastern Arabic digits.
     */
    private static array $arabicDigits = [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ];

    /**
     * Convert Western digits to Eastern Arabic numerals.
     */
    public static function toArabicDigits(mixed $value): string
    {
        return strtr((string) $value, self::$arabicDigits);
    }
}

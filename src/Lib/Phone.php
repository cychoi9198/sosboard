<?php
declare(strict_types=1);

namespace App\Lib;

final class Phone
{
    /**
     * Mask a phone number for public listing pages, to slow down naive scraping.
     * Keeps the first 3 and last 2 characters, replaces the middle with a fixed-length
     * mask (not the real middle length, so the mask doesn't leak the number's length).
     */
    public static function mask(string $phone): string
    {
        $len = mb_strlen($phone, 'UTF-8');
        if ($len <= 5) {
            return mb_substr($phone, 0, 1, 'UTF-8') . '****';
        }
        $head = mb_substr($phone, 0, 3, 'UTF-8');
        $tail = mb_substr($phone, -2, null, 'UTF-8');
        return $head . '****' . $tail;
    }

    /** National (trunk) prefix "0" is dropped when a number is written in international form. */
    public static function stripLeadingZero(string $localNumber): string
    {
        $trimmed = trim($localNumber);
        if (str_starts_with($trimmed, '0')) {
            return substr($trimmed, 1);
        }
        return $trimmed;
    }
}

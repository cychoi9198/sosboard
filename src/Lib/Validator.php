<?php
declare(strict_types=1);

namespace App\Lib;

final class Validator
{
    public static function isUtf8(string $value): bool
    {
        return mb_check_encoding($value, 'UTF-8');
    }

    public static function mbLenBetween(string $value, int $min, int $max): bool
    {
        $len = mb_strlen($value, 'UTF-8');
        return $len >= $min && $len <= $max;
    }

    public static function inList(string $value, array $list): bool
    {
        return in_array($value, $list, true);
    }

    /** Reject raw angle brackets so users can't submit HTML/script tags, even though output is always escaped too. */
    public static function noTags(string $value): bool
    {
        return !str_contains($value, '<') && !str_contains($value, '>');
    }

    public static function loginId(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]{3,20}$/', $value);
    }

    /** bcrypt silently ignores bytes beyond 72, so cap length explicitly. */
    public static function password(string $value): bool
    {
        $len = strlen($value);
        return $len >= 8 && $len <= 72;
    }

    public static function nickname(string $value): bool
    {
        return self::isUtf8($value) && self::mbLenBetween($value, 1, 30) && !str_contains($value, "\n");
    }

    public static function title(string $value, int $maxChars): bool
    {
        return self::isUtf8($value) && self::mbLenBetween(trim($value), 1, $maxChars) && !str_contains($value, "\n");
    }

    public static function postBody(string $value, int $maxChars): bool
    {
        return self::isUtf8($value) && self::mbLenBetween(trim($value), 1, $maxChars);
    }

    public static function phone(string $value, int $maxChars): bool
    {
        return (bool) preg_match('/^[0-9+\-\s()]{7,' . $maxChars . '}$/', $value);
    }

    /** The part after the country dial code — no leading "+" expected here. */
    public static function localPhoneNumber(string $value, int $maxChars): bool
    {
        return (bool) preg_match('/^[0-9\-\s()]{4,' . $maxChars . '}$/', $value);
    }

    public static function contactBody(string $value, int $maxChars): bool
    {
        return self::isUtf8($value) && self::mbLenBetween(trim($value), 1, $maxChars);
    }
}

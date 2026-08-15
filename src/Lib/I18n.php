<?php
declare(strict_types=1);

namespace App\Lib;

final class I18n
{
    private static string $locale = 'ko';
    private static array $strings = [];

    public static function init(string $locale): void
    {
        $supported = Config::get('app')['supported_langs'];
        if (!in_array($locale, $supported, true)) {
            $locale = Config::get('app')['default_lang'];
        }
        self::$locale = $locale;
        $file = __DIR__ . '/../../lang/' . $locale . '.php';
        self::$strings = is_file($file) ? require $file : [];
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function t(string $key, array $vars = []): string
    {
        $text = self::$strings[$key] ?? $key;
        if ($vars) {
            $text = strtr($text, $vars);
        }
        return $text;
    }

    /**
     * Negotiate a supported locale from the Accept-Language header.
     * Used only once, at the "/" entry point, to redirect into a /{lang}/ URL.
     */
    public static function negotiate(string $acceptLanguageHeader, array $supported, string $default): string
    {
        $best = $default;
        $bestQ = 0.0;

        foreach (explode(',', $acceptLanguageHeader) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $pieces = explode(';q=', $part);
            $tag = strtolower(trim($pieces[0]));
            $q = isset($pieces[1]) ? (float) $pieces[1] : 1.0;
            $short = substr($tag, 0, 2);

            if (in_array($short, $supported, true) && $q > $bestQ) {
                $best = $short;
                $bestQ = $q;
            }
        }

        return $best;
    }
}

<?php
declare(strict_types=1);

namespace App\Lib;

final class Url
{
    /** Build a same-language URL, e.g. Url::to('board/write') => /ko/board/write */
    public static function to(string $path = ''): string
    {
        $base = rtrim((string) Config::get('app')['base_path'], '/');
        $lang = I18n::locale();
        $path = ltrim($path, '/');
        $url = $base . '/' . $lang . ($path !== '' ? '/' . $path : '');
        return $url === '' ? '/' : $url;
    }

    /** Build the same current path but under a different language prefix. */
    public static function withLocale(string $lang, string $currentPathAfterLocale): string
    {
        $base = rtrim((string) Config::get('app')['base_path'], '/');
        $currentPathAfterLocale = ltrim($currentPathAfterLocale, '/');
        $url = $base . '/' . $lang . ($currentPathAfterLocale !== '' ? '/' . $currentPathAfterLocale : '');
        return $url === '' ? '/' : $url;
    }
}

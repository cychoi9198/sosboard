<?php
declare(strict_types=1);

namespace App\Lib;

final class Config
{
    private static ?array $data = null;

    private static function load(): array
    {
        if (self::$data === null) {
            self::$data = require __DIR__ . '/../../config/config.php';
        }
        return self::$data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $data = self::load();
        return $data[$key] ?? $default;
    }
}

<?php
declare(strict_types=1);

namespace App\Lib;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = Config::get('session');
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_name($config['name']);
        session_set_cookie_params([
            'lifetime' => $config['lifetime_seconds'],
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');

        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}

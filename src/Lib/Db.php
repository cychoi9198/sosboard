<?php
declare(strict_types=1);

namespace App\Lib;

use PDO;

final class Db
{
    private static ?PDO $instance = null;

    public static function conn(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = Config::get('db');

        $pdo = new PDO($config['dsn'], $config['user'] ?? null, $config['pass'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        if ($config['driver'] === 'mysql') {
            $pdo->exec("SET time_zone = '+00:00'");
        } elseif ($config['driver'] === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
            // WAL lets reads and writes proceed concurrently instead of blocking on one file
            // lock — matters on small/low-power hardware where SQLite is the whole database.
            $pdo->exec('PRAGMA journal_mode = WAL');
        }

        self::$instance = $pdo;
        return $pdo;
    }

    public static function driver(): string
    {
        return Config::get('db')['driver'];
    }
}

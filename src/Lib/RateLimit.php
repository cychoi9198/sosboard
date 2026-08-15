<?php
declare(strict_types=1);

namespace App\Lib;

use App\Lib\Db;

final class RateLimit
{
    /** HMAC-hashed client IP (binary, 16 bytes) — never store the raw address. */
    public static function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $pepper = Config::get('security')['ip_pepper'];
        return substr(hash_hmac('sha256', $ip, $pepper, true), 0, 16);
    }

    public static function recordLoginAttempt(string $identifier, bool $success): void
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO login_attempts (identifier, success) VALUES (:identifier, :success)'
        );
        $stmt->execute(['identifier' => $identifier, 'success' => $success ? 1 : 0]);
    }

    public static function tooManyLoginAttempts(string $identifier, int $maxAttempts, int $windowMinutes): bool
    {
        $stmt = Db::conn()->prepare(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE identifier = :identifier AND success = 0
               AND attempted_at > (NOW() - INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue('identifier', $identifier);
        $stmt->bindValue('minutes', $windowMinutes, \PDO::PARAM_INT);
        $stmt->execute();
        $count = (int) $stmt->fetch()['c'];
        return $count >= $maxAttempts;
    }

    public static function recordPostAttempt(string $ipHash): void
    {
        self::recordAttempt('post_attempts', $ipHash);
    }

    public static function tooManyPosts(string $ipHash, int $maxPosts, int $windowMinutes): bool
    {
        return self::tooManyAttempts('post_attempts', $ipHash, $maxPosts, $windowMinutes);
    }

    public static function recordContactAttempt(string $ipHash): void
    {
        self::recordAttempt('contact_attempts', $ipHash);
    }

    public static function tooManyContacts(string $ipHash, int $maxAttempts, int $windowMinutes): bool
    {
        return self::tooManyAttempts('contact_attempts', $ipHash, $maxAttempts, $windowMinutes);
    }

    public static function recordRegistrationAttempt(string $ipHash): void
    {
        self::recordAttempt('registration_attempts', $ipHash);
    }

    public static function tooManyRegistrations(string $ipHash, int $maxAttempts, int $windowMinutes): bool
    {
        return self::tooManyAttempts('registration_attempts', $ipHash, $maxAttempts, $windowMinutes);
    }

    /** $table is always one of our own fixed table name constants below, never user input. */
    private static function recordAttempt(string $table, string $ipHash): void
    {
        $stmt = Db::conn()->prepare("INSERT INTO {$table} (ip_hash) VALUES (:ip)");
        $stmt->execute(['ip' => $ipHash]);
    }

    private static function tooManyAttempts(string $table, string $ipHash, int $maxAttempts, int $windowMinutes): bool
    {
        $stmt = Db::conn()->prepare(
            "SELECT COUNT(*) AS c FROM {$table}
             WHERE ip_hash = :ip AND attempted_at > (NOW() - INTERVAL :minutes MINUTE)"
        );
        $stmt->bindValue('ip', $ipHash);
        $stmt->bindValue('minutes', $windowMinutes, \PDO::PARAM_INT);
        $stmt->execute();
        $count = (int) $stmt->fetch()['c'];
        return $count >= $maxAttempts;
    }
}

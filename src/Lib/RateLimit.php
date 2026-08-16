<?php
declare(strict_types=1);

namespace App\Lib;

use App\Lib\Db;

final class RateLimit
{
    /**
     * The client's IP address, stored as-is (not hashed). Needed so an admin can review recent
     * posts and ban an abusive source after the fact — see IpBan. This board is for emergencies,
     * so we deliberately don't lean on tight preemptive rate limits that could delay a genuine
     * urgent post; moderation is reactive instead.
     */
    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
               AND attempted_at > :cutoff'
        );
        $stmt->bindValue('identifier', $identifier);
        $stmt->bindValue('cutoff', self::cutoff($windowMinutes));
        $stmt->execute();
        $count = (int) $stmt->fetch()['c'];
        return $count >= $maxAttempts;
    }

    public static function recordPostAttempt(string $ip): void
    {
        self::recordAttempt('post_attempts', $ip);
    }

    public static function tooManyPosts(string $ip, int $maxPosts, int $windowMinutes): bool
    {
        return self::tooManyAttempts('post_attempts', $ip, $maxPosts, $windowMinutes);
    }

    public static function recordContactAttempt(string $ip): void
    {
        self::recordAttempt('contact_attempts', $ip);
    }

    public static function tooManyContacts(string $ip, int $maxAttempts, int $windowMinutes): bool
    {
        return self::tooManyAttempts('contact_attempts', $ip, $maxAttempts, $windowMinutes);
    }

    public static function recordRegistrationAttempt(string $ip): void
    {
        self::recordAttempt('registration_attempts', $ip);
    }

    public static function tooManyRegistrations(string $ip, int $maxAttempts, int $windowMinutes): bool
    {
        return self::tooManyAttempts('registration_attempts', $ip, $maxAttempts, $windowMinutes);
    }

    /** $table is always one of our own fixed table name constants below, never user input. */
    private static function recordAttempt(string $table, string $ip): void
    {
        $stmt = Db::conn()->prepare("INSERT INTO {$table} (ip) VALUES (:ip)");
        $stmt->execute(['ip' => $ip]);
    }

    private static function tooManyAttempts(string $table, string $ip, int $maxAttempts, int $windowMinutes): bool
    {
        $stmt = Db::conn()->prepare(
            "SELECT COUNT(*) AS c FROM {$table}
             WHERE ip = :ip AND attempted_at > :cutoff"
        );
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('cutoff', self::cutoff($windowMinutes));
        $stmt->execute();
        $count = (int) $stmt->fetch()['c'];
        return $count >= $maxAttempts;
    }

    /**
     * Computed here instead of with SQL date arithmetic (MySQL's "NOW() - INTERVAL n MINUTE" has
     * no SQLite equivalent) so the same query works on both drivers — attempted_at is always
     * stored as a UTC "Y-m-d H:i:s" string on both, which compares correctly as text.
     */
    private static function cutoff(int $windowMinutes): string
    {
        return gmdate('Y-m-d H:i:s', time() - $windowMinutes * 60);
    }
}

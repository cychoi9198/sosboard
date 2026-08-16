<?php
declare(strict_types=1);

namespace App\Lib;

use PDO;

final class IpBan
{
    /**
     * Range comparison is done in PHP (via ip2long) rather than in SQL: the ban list is small
     * (admin-managed) and this keeps the query itself trivially portable across MySQL/SQLite.
     * IPv6 addresses aren't range-checked (ip2long() returns false for them) — only exact-value
     * ranges (start === end) still match an IPv6 address via a plain string comparison.
     */
    public static function isBanned(string $ip): bool
    {
        $stmt = Db::conn()->query('SELECT start_ip, end_ip FROM banned_ip_ranges');
        $target = ip2long($ip);

        foreach ($stmt->fetchAll() as $row) {
            if ($row['start_ip'] === $row['end_ip'] && $row['start_ip'] === $ip) {
                return true;
            }
            if ($target === false) {
                continue;
            }
            $start = ip2long($row['start_ip']);
            $end = ip2long($row['end_ip']);
            if ($start !== false && $end !== false && $target >= $start && $target <= $end) {
                return true;
            }
        }

        return false;
    }

    public static function ban(string $startIp, string $endIp, ?int $adminId, ?string $reason): void
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO banned_ip_ranges (start_ip, end_ip, banned_by, reason) VALUES (:start, :end, :admin, :reason)'
        );
        $stmt->bindValue('start', $startIp);
        $stmt->bindValue('end', $endIp);
        $stmt->bindValue('admin', $adminId, $adminId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('reason', $reason);
        $stmt->execute();
    }

    public static function unban(int $id): void
    {
        $stmt = Db::conn()->prepare('DELETE FROM banned_ip_ranges WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function list(): array
    {
        return Db::conn()->query('SELECT * FROM banned_ip_ranges ORDER BY id DESC')->fetchAll();
    }
}

<?php
declare(strict_types=1);

namespace App\Repository;

use App\Lib\Db;
use PDO;

final class ContactRepository
{
    /** Same separator-stripping applied to both sides so "010-1234" matches a stored "010 1234" too. */
    private const NORMALIZE_SQL = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '')";

    /** $exactNormalizedPhone must already be normalize()d by the caller — this does an exact match, not a substring search. */
    public function listPage(?string $exactNormalizedPhone, ?int $beforeId, int $limit): array
    {
        $sql = 'SELECT * FROM contacts WHERE status = 1';
        $params = [];

        if ($exactNormalizedPhone !== null) {
            $sql .= ' AND ' . self::NORMALIZE_SQL . ' = :q';
            $params['q'] = $exactNormalizedPhone;
        }
        if ($beforeId !== null) {
            $sql .= ' AND id < :before_id';
            $params['before_id'] = $beforeId;
        }

        $sql .= ' ORDER BY id DESC LIMIT :limit';

        $stmt = Db::conn()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Strips the separators a user might type/store ('-', ' ', '+', '(', ')') so formatting differences don't affect matching. */
    public static function normalize(string $value): string
    {
        return str_replace(['-', ' ', '+', '(', ')'], '', $value);
    }

    public function find(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM contacts WHERE id = :id AND status = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $phone, string $body, string $ipHash): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO contacts (phone, body, ip_hash, status) VALUES (:phone, :body, :ip_hash, 1)'
        );
        $stmt->bindValue('phone', $phone);
        $stmt->bindValue('body', $body);
        $stmt->bindValue('ip_hash', $ipHash, PDO::PARAM_LOB);
        $stmt->execute();

        return (int) Db::conn()->lastInsertId();
    }

    /** Soft delete: status 2 = removed by admin. Row is kept for moderation history. */
    public function softDelete(int $id): void
    {
        $stmt = Db::conn()->prepare('UPDATE contacts SET status = 2 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

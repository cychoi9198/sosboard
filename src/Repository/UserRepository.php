<?php
declare(strict_types=1);

namespace App\Repository;

use App\Lib\Db;
use PDO;

final class UserRepository
{
    public function findByLoginId(string $loginId): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE login_id = :login_id');
        $stmt->execute(['login_id' => $loginId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function loginIdExists(string $loginId): bool
    {
        return $this->findByLoginId($loginId) !== null;
    }

    public function nicknameExists(string $nickname): bool
    {
        $stmt = Db::conn()->prepare('SELECT 1 FROM users WHERE nickname = :nickname');
        $stmt->execute(['nickname' => $nickname]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $loginId, string $passwordHash, string $nickname): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO users (login_id, password_hash, nickname, role) VALUES (:login_id, :password_hash, :nickname, 0)'
        );
        $stmt->execute([
            'login_id' => $loginId,
            'password_hash' => $passwordHash,
            'nickname' => $nickname,
        ]);
        return (int) Db::conn()->lastInsertId();
    }
}

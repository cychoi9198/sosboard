<?php
declare(strict_types=1);

namespace App\Lib;

use App\Repository\UserRepository;

final class Auth
{
    private const SESSION_KEY = 'auth_user';

    /** A pre-computed bcrypt hash of a random value, used to equalize timing when a login_id does not exist. */
    private const DUMMY_HASH = '$2y$10$wwF8Wc10RbZ3/jTlSpMha.0ew3UlDuM5c1vbxDObctSp3Y4A7BFZm';

    public static function attempt(string $loginId, string $password): bool
    {
        $repo = new UserRepository();
        $user = $repo->findByLoginId($loginId);

        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash);

        if (!$user || !$valid) {
            return false;
        }

        Session::regenerate();
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'nickname' => $user['nickname'],
            'role' => (int) $user['role'],
        ];

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        Session::regenerate();
    }

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? 0) >= 9;
    }
}

<?php
declare(strict_types=1);

namespace App\Repository;

use App\Lib\Db;
use PDO;

final class PostRepository
{
    /**
     * Keyset-paginated listing (no OFFSET — stays fast as the table grows).
     * Returns up to $limit+1 rows so the caller can tell whether a next page exists.
     */
    public function listPage(?string $category, ?string $searchQuery, ?int $beforeId, int $limit): array
    {
        // A leading-wildcard LIKE can't use an index and forces a full table scan as posts grow,
        // so search goes through a real full-text index instead — MySQL/MariaDB's FULLTEXT in
        // boolean mode, or SQLite's FTS5 virtual table (posts_fts, kept in sync by triggers —
        // see the *.sqlite.sql migrations). Both build an AND-of-prefixes query: every word
        // becomes a required prefix term, so this only matches from the START of a word (neither
        // engine here has a CJK ngram tokenizer for true substring search).
        $isSqlite = Db::driver() === 'sqlite';
        $ftsJoin = ($isSqlite && $searchQuery !== null) ? ' JOIN posts_fts ON posts_fts.rowid = p.id' : '';

        $sql = 'SELECT p.*, u.nickname AS user_nickname
                FROM posts p LEFT JOIN users u ON u.id = p.user_id' . $ftsJoin . '
                WHERE p.status = 1';
        $params = [];

        if ($category !== null) {
            $sql .= ' AND p.category = :category';
            $params['category'] = $category;
        }
        if ($searchQuery !== null) {
            if ($isSqlite) {
                $ftsQuery = self::buildSqliteFtsQuery($searchQuery);
                if ($ftsQuery !== '') {
                    $sql .= ' AND posts_fts MATCH :q';
                    $params['q'] = $ftsQuery;
                }
            } else {
                $booleanQuery = self::buildBooleanPrefixQuery($searchQuery);
                if ($booleanQuery !== '') {
                    $sql .= ' AND MATCH(p.title, p.body) AGAINST(:q IN BOOLEAN MODE)';
                    $params['q'] = $booleanQuery;
                }
            }
        }
        if ($beforeId !== null) {
            $sql .= ' AND p.id < :before_id';
            $params['before_id'] = $beforeId;
        }

        $sql .= ' ORDER BY p.id DESC LIMIT :limit';

        $stmt = Db::conn()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * MySQL/MariaDB boolean-mode MATCH...AGAINST: every word becomes a required prefix term
     * ("+word*"), so a multi-word search is an AND of prefixes. Strips boolean-mode operator
     * characters out of the raw words first so a user typing "-" or "+" or "*" can't change the
     * query's meaning (e.g. turn it into a NOT search).
     */
    private static function buildBooleanPrefixQuery(string $rawQuery): string
    {
        $cleaned = preg_replace('/[+\-<>()~*"@]/u', ' ', $rawQuery);
        $words = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_map(static fn (string $w): string => '+' . $w . '*', $words);
        return implode(' ', $terms);
    }

    /**
     * SQLite FTS5 query: each word is quoted (so it can't be mistaken for a reserved operator
     * like AND/OR/NOT, and embedded FTS5 syntax characters in it are inert) with a trailing "*"
     * for prefix matching. Space-separated quoted terms are ANDed together by default in FTS5.
     */
    private static function buildSqliteFtsQuery(string $rawQuery): string
    {
        $cleaned = preg_replace('/["*]/u', ' ', $rawQuery);
        $words = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_map(static fn (string $w): string => '"' . $w . '"*', $words);
        return implode(' ', $terms);
    }

    public function find(int $id): ?array
    {
        $stmt = Db::conn()->prepare(
            'SELECT p.*, u.nickname AS user_nickname
             FROM posts p LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id = :id AND p.status = 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO posts (user_id, guest_nickname, guest_password_hash, category, title, body, lang, ip, status)
             VALUES (:user_id, :guest_nickname, :guest_password_hash, :category, :title, :body, :lang, :ip, 1)'
        );
        $stmt->bindValue('user_id', $data['user_id'], $data['user_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('guest_nickname', $data['guest_nickname']);
        $stmt->bindValue('guest_password_hash', $data['guest_password_hash']);
        $stmt->bindValue('category', $data['category']);
        $stmt->bindValue('title', $data['title']);
        $stmt->bindValue('body', $data['body']);
        $stmt->bindValue('lang', $data['lang']);
        $stmt->bindValue('ip', $data['ip']);
        $stmt->execute();

        return (int) Db::conn()->lastInsertId();
    }

    /** Soft delete: status 2 = removed by author/admin. Row is kept for moderation history. */
    public function softDelete(int $id): void
    {
        $stmt = Db::conn()->prepare('UPDATE posts SET status = 2 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** For the admin moderation view — most recent posts regardless of category, with the poster's IP. */
    public function recentForModeration(int $limit): array
    {
        $stmt = Db::conn()->prepare(
            'SELECT p.id, p.title, p.category, p.ip, p.guest_nickname, p.user_id, p.created_at, u.nickname AS user_nickname
             FROM posts p LEFT JOIN users u ON u.id = p.user_id
             WHERE p.status = 1
             ORDER BY p.id DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Bulk moderation action: soft-delete every active post from an exact IP. Returns how many. */
    public function softDeleteByIp(string $ip): int
    {
        $stmt = Db::conn()->prepare('UPDATE posts SET status = 2 WHERE ip = :ip AND status = 1');
        $stmt->execute(['ip' => $ip]);
        return $stmt->rowCount();
    }
}

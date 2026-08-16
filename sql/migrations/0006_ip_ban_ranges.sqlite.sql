-- Switches from HMAC-hashed IPs to storing the IP address itself, and adds admin-managed
-- start/end IP range bans. See 0006_ip_ban_ranges.mysql.sql for the full rationale.
-- Apply with: sqlite3 var/data/sosboard.sqlite < 0006_ip_ban_ranges.sqlite.sql
--
-- Requires SQLite 3.35+ for ALTER TABLE ... DROP COLUMN (bundled PHP pdo_sqlite here is 3.39).

-- SQLite refuses to drop a column that an index still references (unlike MySQL, which drops
-- the index implicitly) — idx_posts_abuse from 0001_init.sqlite.sql covers ip_hash, so it has
-- to go first.
DROP INDEX IF EXISTS idx_posts_abuse;
ALTER TABLE posts DROP COLUMN ip_hash;
ALTER TABLE posts ADD COLUMN ip VARCHAR(45) NULL;
CREATE INDEX IF NOT EXISTS idx_posts_ip ON posts(ip, created_at);

ALTER TABLE contacts DROP COLUMN ip_hash;
ALTER TABLE contacts ADD COLUMN ip VARCHAR(45) NULL;

DROP TABLE IF EXISTS post_attempts;
CREATE TABLE post_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_post_attempts ON post_attempts(ip, attempted_at);

DROP TABLE IF EXISTS contact_attempts;
CREATE TABLE contact_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_contact_attempts ON contact_attempts(ip, attempted_at);

DROP TABLE IF EXISTS registration_attempts;
CREATE TABLE registration_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_registration_attempts ON registration_attempts(ip, attempted_at);

CREATE TABLE IF NOT EXISTS banned_ip_ranges (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  start_ip VARCHAR(45) NOT NULL,
  end_ip VARCHAR(45) NOT NULL,
  banned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  banned_by INTEGER NULL,
  reason VARCHAR(255) NULL,
  FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT OR IGNORE INTO schema_migrations (version) VALUES ('0006_ip_ban_ranges');

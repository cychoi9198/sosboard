-- SOSBoard initial schema (SQLite) — mirrors 0001_init.mysql.sql for small/low-power hardware
-- where running a separate MySQL/MariaDB server isn't practical.
-- Apply with: sqlite3 var/data/sosboard.sqlite < 0001_init.sqlite.sql
--
-- Notable differences from the MySQL version (SQLite has no ENGINE/CHARSET/UNSIGNED, and no
-- inline KEY/UNIQUE KEY syntax inside CREATE TABLE — indexes are separate CREATE INDEX
-- statements below):
--   INT UNSIGNED AUTO_INCREMENT -> INTEGER PRIMARY KEY AUTOINCREMENT (SQLite's own rowid alias)
--   BINARY(16)                  -> BLOB
--   "ON UPDATE CURRENT_TIMESTAMP" has no SQLite equivalent; posts.updated_at is kept for schema
--   parity but isn't auto-touched (the app doesn't read/write it today either way).

CREATE TABLE IF NOT EXISTS schema_migrations (
  version TEXT PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  login_id VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nickname VARCHAR(30) NOT NULL,
  role INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_login_id ON users(login_id);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_nickname ON users(nickname);

CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  guest_nickname VARCHAR(30) NULL,
  guest_password_hash VARCHAR(255) NULL,
  category VARCHAR(20) NOT NULL,
  body VARCHAR(500) NOT NULL,
  lang CHAR(2) NOT NULL DEFAULT 'ko',
  ip_hash BLOB NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_posts_list ON posts(status, id);
CREATE INDEX IF NOT EXISTS idx_posts_cat ON posts(status, category, id);
CREATE INDEX IF NOT EXISTS idx_posts_abuse ON posts(ip_hash, created_at);

CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  identifier VARCHAR(100) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_attempts ON login_attempts(identifier, attempted_at);

CREATE TABLE IF NOT EXISTS post_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip_hash BLOB NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_post_attempts ON post_attempts(ip_hash, attempted_at);

INSERT OR IGNORE INTO schema_migrations (version) VALUES ('0001_init');

-- Default admin account: login_id=admin / password=ChangeMe123!
-- CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN.
INSERT OR IGNORE INTO users (login_id, password_hash, nickname, role)
VALUES ('admin', '$2y$10$t2jqLPK3FKlZajjNjkLYfeSjIlJLX6NACFbz7Zml6AIfbpziPw/Ca', '관리자', 9);

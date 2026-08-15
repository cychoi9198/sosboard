-- Adds a title/subject to posts, and a separate emergency contact board (SQLite).
-- Apply with: sqlite3 var/data/sosboard.sqlite < 0002_title_and_contacts.sqlite.sql
--
-- Note: SQLite's ADD COLUMN always appends at the end (no "AFTER category" positioning like
-- MySQL) — harmless here since the app always uses named columns, never positional ones.

ALTER TABLE posts ADD COLUMN title VARCHAR(100) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS contacts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  phone VARCHAR(20) NOT NULL,
  body VARCHAR(200) NOT NULL,
  ip_hash BLOB NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_contacts_list ON contacts(status, id);

CREATE TABLE IF NOT EXISTS contact_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip_hash BLOB NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_contact_attempts ON contact_attempts(ip_hash, attempted_at);

INSERT OR IGNORE INTO schema_migrations (version) VALUES ('0002_title_and_contacts');

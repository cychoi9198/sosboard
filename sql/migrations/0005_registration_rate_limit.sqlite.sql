-- Registration rate limiting (SQLite).
-- Apply with: sqlite3 var/data/sosboard.sqlite < 0005_registration_rate_limit.sqlite.sql

CREATE TABLE IF NOT EXISTS registration_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip_hash BLOB NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_registration_attempts ON registration_attempts(ip_hash, attempted_at);

INSERT OR IGNORE INTO schema_migrations (version) VALUES ('0005_registration_rate_limit');

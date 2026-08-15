-- No-op for SQLite: column type declarations like VARCHAR(20) are only advisory (type affinity,
-- not an enforced length limit), so contacts.phone already accepts values longer than 20 chars —
-- nothing to widen. Kept as its own file purely so migration numbering stays in sync with the
-- MySQL side. Apply with: sqlite3 var/data/sosboard.sqlite < 0003_widen_contact_phone.sqlite.sql

INSERT OR IGNORE INTO schema_migrations (version) VALUES ('0003_widen_contact_phone');

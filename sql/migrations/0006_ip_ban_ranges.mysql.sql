-- Switches from HMAC-hashed IPs to storing the IP address itself, and adds admin-managed
-- start/end IP range bans.
--
-- Why the reversal: a cryptographic hash can't be range- or wildcard-matched (that's the whole
-- point of a hash), so there was no way to let an admin ban "everything in this /24" without
-- being able to see and compare real IP values. This board is for emergencies — we deliberately
-- avoid tight *preemptive* rate limits that could delay a genuine urgent post, so abuse handling
-- is reactive: an admin reviews recent activity (now showing real IPs) and bans a source after
-- spotting it. This is a real privacy trade-off from the previous "never store raw IPs" design;
-- an admin can now see posters' IP addresses.
--
-- Apply with: mysql --default-character-set=utf8mb4 -u root sosboard < 0006_ip_ban_ranges.mysql.sql

ALTER TABLE posts DROP COLUMN ip_hash, ADD COLUMN ip VARCHAR(45) NULL AFTER lang, ADD KEY idx_posts_ip (ip, created_at);
ALTER TABLE contacts DROP COLUMN ip_hash, ADD COLUMN ip VARCHAR(45) NULL AFTER body;

-- These three only ever hold short-lived rate-limit rows (irrelevant within minutes), so it's
-- simplest and safest to just recreate them rather than ALTER in place.
DROP TABLE IF EXISTS post_attempts;
CREATE TABLE post_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_post_attempts (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS contact_attempts;
CREATE TABLE contact_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contact_attempts (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS registration_attempts;
CREATE TABLE registration_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_registration_attempts (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS banned_ip_ranges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  start_ip VARCHAR(45) NOT NULL,
  end_ip VARCHAR(45) NOT NULL,
  banned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  banned_by INT UNSIGNED NULL,
  reason VARCHAR(255) NULL,
  CONSTRAINT fk_banned_ranges_admin FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('0006_ip_ban_ranges');

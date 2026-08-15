-- Registration had no rate limiting at all (unlike login/post/contact), allowing unlimited
-- username-enumeration attempts and unbounded bcrypt-cost consumption. Add an IP-based limit.
-- Apply with: mysql --default-character-set=utf8mb4 -u root sosboard < 0005_registration_rate_limit.mysql.sql

CREATE TABLE IF NOT EXISTS registration_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_hash BINARY(16) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_registration_attempts (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('0005_registration_rate_limit');

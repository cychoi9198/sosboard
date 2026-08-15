-- Adds a title/subject to posts, and a separate emergency contact board.
-- Apply with: mysql --default-character-set=utf8mb4 -u root sosboard < 0002_title_and_contacts.mysql.sql

ALTER TABLE posts
  ADD COLUMN title VARCHAR(100) NOT NULL DEFAULT '' AFTER category;

CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(20) NOT NULL,
  body VARCHAR(200) NOT NULL,
  ip_hash BINARY(16) NULL,
  status TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contacts_list (status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_hash BINARY(16) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contact_attempts (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('0002_title_and_contacts');

-- SOSBoard initial schema (MySQL / MariaDB)
-- Apply with: mysql -u root sosboard < 0001_init.mysql.sql

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(20) PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  login_id VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nickname VARCHAR(30) NOT NULL,
  role TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_login_id (login_id),
  UNIQUE KEY uq_users_nickname (nickname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  guest_nickname VARCHAR(30) NULL,
  guest_password_hash VARCHAR(255) NULL,
  category VARCHAR(20) NOT NULL,
  body VARCHAR(500) NOT NULL,
  lang CHAR(2) NOT NULL DEFAULT 'ko',
  ip_hash BINARY(16) NULL,
  status TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_posts_list (status, id),
  KEY idx_posts_cat (status, category, id),
  KEY idx_posts_abuse (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(100) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_attempts (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_hash BINARY(16) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_post_attempts (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('0001_init');

-- Default admin account: login_id=admin / password=ChangeMe123!
-- CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN.
INSERT IGNORE INTO users (login_id, password_hash, nickname, role)
VALUES ('admin', '$2y$10$t2jqLPK3FKlZajjNjkLYfeSjIlJLX6NACFbz7Zml6AIfbpziPw/Ca', '관리자', 9);

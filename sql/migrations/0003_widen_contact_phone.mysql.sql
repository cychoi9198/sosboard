-- Contact numbers now always include a country dial code prefix (e.g. "+82 10-1234-5678"),
-- so widen the column a bit to keep headroom.
-- Apply with: mysql --default-character-set=utf8mb4 -u root sosboard < 0003_widen_contact_phone.mysql.sql

ALTER TABLE contacts MODIFY COLUMN phone VARCHAR(25) NOT NULL;

INSERT IGNORE INTO schema_migrations (version) VALUES ('0003_widen_contact_phone');

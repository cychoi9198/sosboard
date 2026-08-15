-- FULLTEXT index for title/content search. A plain "LIKE '%term%'" can't use a B-tree
-- index (leading wildcard forces a full table scan), which gets slow as posts grow.
-- MariaDB here has no ngram parser (checked: `SHOW PLUGINS` has no ngram entry), so we
-- can't do true CJK substring search — instead we use boolean-mode PREFIX search
-- ("+term*"), which the index can serve directly. See PostRepository::listPage().
-- Apply with: mysql --default-character-set=utf8mb4 -u root sosboard < 0004_post_fulltext_search.mysql.sql

ALTER TABLE posts ADD FULLTEXT INDEX ft_title_body (title, body);

INSERT IGNORE INTO schema_migrations (version) VALUES ('0004_post_fulltext_search');

-- Title/body search for SQLite, via an FTS5 virtual table instead of MySQL's FULLTEXT index
-- (SQLite has no FULLTEXT INDEX / MATCH...AGAINST — FTS5 is its equivalent mechanism).
-- Apply with: sqlite3 var/data/sosboard.sqlite < 0004_post_fulltext_search.sqlite.sql
--
-- Uses the "external content" pattern: posts_fts stores no data of its own, it indexes
-- posts.title/posts.body directly (content_rowid='id' ties FTS5 rowids to posts.id), and three
-- triggers keep it in sync on insert/update/delete. See PostRepository::listPage() for how this
-- gets queried (JOIN posts_fts ON posts_fts.rowid = p.id, WHERE posts_fts MATCH :q).

CREATE VIRTUAL TABLE IF NOT EXISTS posts_fts USING fts5(
  title, body, content='posts', content_rowid='id'
);

CREATE TRIGGER IF NOT EXISTS posts_fts_ai AFTER INSERT ON posts BEGIN
  INSERT INTO posts_fts(rowid, title, body) VALUES (new.id, new.title, new.body);
END;

CREATE TRIGGER IF NOT EXISTS posts_fts_ad AFTER DELETE ON posts BEGIN
  INSERT INTO posts_fts(posts_fts, rowid, title, body) VALUES ('delete', old.id, old.title, old.body);
END;

CREATE TRIGGER IF NOT EXISTS posts_fts_au AFTER UPDATE ON posts BEGIN
  INSERT INTO posts_fts(posts_fts, rowid, title, body) VALUES ('delete', old.id, old.title, old.body);
  INSERT INTO posts_fts(rowid, title, body) VALUES (new.id, new.title, new.body);
END;

-- Backfill: index any posts that existed before this migration ran.
INSERT INTO posts_fts(rowid, title, body) SELECT id, title, body FROM posts;

INSERT OR IGNORE INTO schema_migrations (version) VALUES ('0004_post_fulltext_search');

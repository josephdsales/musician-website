-- =============================================
-- Songbook - PostgreSQL schema (Neon / Render)
-- Import ONCE (install.php does this automatically).
-- Redeploying files never touches data.
-- =============================================

CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE TABLE IF NOT EXISTS songs (
  id SERIAL PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  artist VARCHAR(150) NOT NULL DEFAULT '',
  original_key VARCHAR(10) NOT NULL DEFAULT '',
  tempo INT NULL,
  content TEXT NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_songs_title ON songs(title);
CREATE INDEX IF NOT EXISTS idx_songs_artist ON songs(artist);
CREATE INDEX IF NOT EXISTS idx_songs_title_gin ON songs USING gin(title gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_songs_artist_gin ON songs USING gin(artist gin_trgm_ops);

CREATE TABLE IF NOT EXISTS playlists (
  id SERIAL PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS playlist_songs (
  id SERIAL PRIMARY KEY,
  playlist_id INT NOT NULL REFERENCES playlists(id) ON DELETE CASCADE,
  song_id INT NOT NULL REFERENCES songs(id) ON DELETE CASCADE,
  sort_order INT NOT NULL DEFAULT 0,
  transpose_steps INT NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_ps_playlist ON playlist_songs(playlist_id, sort_order);

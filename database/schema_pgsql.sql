-- =============================================
-- Songbook - PostgreSQL schema (Neon / Render)
-- Import ONCE (install.php does this automatically).
-- Redeploying files never touches data.
-- =============================================

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

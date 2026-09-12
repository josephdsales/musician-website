-- =============================================
-- Songbook - MySQL / MariaDB schema
-- Import ONCE (via install.php or phpMyAdmin).
-- Redeploying PHP files never touches data.
-- =============================================
CREATE DATABASE IF NOT EXISTS songbook
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE songbook;

CREATE TABLE IF NOT EXISTS songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  artist VARCHAR(150) NOT NULL DEFAULT '',
  original_key VARCHAR(10) NOT NULL DEFAULT '',
  tempo INT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_songs_title (title)
) ENGINE=InnoDB;

-- Demo songs (inserted only if table is empty - see install.php)

CREATE TABLE IF NOT EXISTS playlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS playlist_songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT NOT NULL,
  song_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  transpose_steps INT NOT NULL DEFAULT 0,
  INDEX idx_ps_playlist (playlist_id, sort_order),
  CONSTRAINT fk_ps_playlist FOREIGN KEY (playlist_id)
    REFERENCES playlists(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_song FOREIGN KEY (song_id)
    REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

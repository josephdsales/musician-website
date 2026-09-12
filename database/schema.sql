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

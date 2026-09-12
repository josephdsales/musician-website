<?php
// =============================================
// Musician Songbook - configuration (CBA-style)
// Works BOTH ways:
//  - Classic hosting / XAMPP: MySQL via DB_HOST/DB_NAME/DB_USER/DB_PASS.
//  - Render + Neon: set DATABASE_URL (Neon connection string) -> Postgres.
// Files can be redeployed any time; data is safe
// because it lives in the database, not in files.
// =============================================
define('APP_NAME', 'Songbook');
define('BASE_URL', ''); // leave empty = auto-detect

// Admin password: set ADMIN_PASSWORD env var on Render.
// Default '123' for local use — change it in production!
function admin_password(): string {
    $p = getenv('ADMIN_PASSWORD');
    return ($p === false || $p === '') ? '123' : $p;
}

function db_driver(): string {
    if (getenv('DATABASE_URL')) return 'pgsql';
    return 'mysql';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        if (getenv('DATABASE_URL')) {
            // Neon Postgres, e.g. postgresql://user:pass@ep-xxx.neon.tech/db?sslmode=require
            $raw = getenv('DATABASE_URL');
            // Allow postgres:// or postgresql:// schemes
            $u = parse_url($raw);
            $host = $u['host'] ?? 'localhost';
            $port = $u['port'] ?? 5432;
            $dbname = ltrim($u['path'] ?? '/songbook', '/');
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            if (!empty($u['query']) && strpos($u['query'], 'sslmode=') !== false) {
                parse_str($u['query'], $q);
                $dsn .= ';sslmode=' . ($q['sslmode'] ?? 'require');
            }
            $pdo = new PDO($dsn, $u['user'] ?? '', $u['pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $host = getenv('DB_HOST') ?: 'localhost';
            $name = getenv('DB_NAME') ?: 'songbook';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    }
    return $pdo;
}

function base_url(string $path = ''): string {
    if (BASE_URL !== '') return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    return $path === '' ? '' : $path;
}

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

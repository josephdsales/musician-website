<?php
// Session + password-only admin auth (CBA-style helpers).
if (session_status() === PHP_SESSION_NONE) session_start();

function is_admin(): bool {
    return !empty($_SESSION['is_admin']);
}

function require_admin(): void {
    if (!is_admin()) { header('Location: login.php'); exit; }
}

function flash(string $key = 'msg'): ?string {
    if (isset($_SESSION['flash_' . $key])) {
        $m = $_SESSION['flash_' . $key];
        unset($_SESSION['flash_' . $key]);
        return $m;
    }
    return null;
}

function set_flash(string $msg, string $key = 'msg'): void {
    $_SESSION['flash_' . $key] = $msg;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
            http_response_code(419);
            exit('Invalid request token. Go back and try again.');
        }
    }
}

<?php
// Password-only admin login. Password = ADMIN_PASSWORD env (default '123').
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

if (is_admin()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pass = $_POST['password'] ?? '';
    if (hash_equals(admin_password(), $pass)) {
        $_SESSION['is_admin'] = true;
        header('Location: dashboard.php'); exit;
    }
    $error = 'Wrong password.';
}
$title = 'Admin Login'; include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;margin:20px auto;">
  <h2 style="margin-top:0">Admin login 🔐</h2>
  <?php if ($error): ?><p style="color:#b91c1c"><b><?= e($error) ?></b></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Password</label>
    <input type="password" name="password" required autofocus autocomplete="current-password">
    <div class="btnrow"><button class="btn" type="submit">Login</button>
    <a class="btn ghost" href="index.php">Back to Songs</a></div>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

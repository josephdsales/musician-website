<?php
// Site access gate — enter password to view the songbook.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
if (is_member() || is_admin()) { header('Location: index.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pw = $_POST['password'] ?? '';
    if ($pw === site_password()) {
        $_SESSION['is_member'] = true;
        header('Location: index.php');
        exit;
    } else {
        $msg = 'Wrong password.';
    }
}
$title = 'Access'; include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
  <h3 style="margin-top:0;text-align:center">Enter password to access the songbook</h3>
  <?php if ($msg): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="password" name="password" placeholder="Password..." required autofocus style="width:100%">
    <div class="btnrow" style="justify-content:center"><button class="btn" type="submit">Enter</button></div>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// Shared page shell. Expects $title.
if (!isset($title)) $title = APP_NAME;
$flash_msg = flash();
$admin = is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/transpose.js"></script>
</head>
<body>
<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="index.php">🎸 <?= e(APP_NAME) ?></a>
    <nav class="nav">
      <a href="index.php">Songs</a>
      <?php if ($admin): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin_songs.php">Manage Songs</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Admin</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
  <?php if ($flash_msg): ?><div class="flash"><?= e($flash_msg) ?></div><?php endif; ?>
  <h1 class="page-title"><?= e($title) ?></h1>

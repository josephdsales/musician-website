<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
unset($_SESSION['is_admin']);
set_flash('Logged out.');
header('Location: index.php');

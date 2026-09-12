<?php
// First-run installer (CBA-style): creates tables + seeds demo songs.
// Admin login is password-only (ADMIN_PASSWORD env, default '123'),
// so no account creation here. Delete this file after setup.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$msg = ''; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $schema = db_driver() === 'pgsql' ? 'schema_pgsql.sql' : 'schema.sql';
        $sql = file_get_contents(__DIR__ . '/database/' . $schema);
        $sql = preg_replace('/^--[^\n]*$/m', '', $sql);
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($stmts as $s) {
            if ($s === '') continue;
            db()->exec($s);
        }
        // Seed demo songs only if table is empty
        $c = (int)db()->query('SELECT COUNT(*) AS c FROM songs')->fetch()['c'];
        if ($c === 0) {
            $demo1 = "[Verse 1]\nG              C\nAmazing grace how sweet\nG          D          G\nthe sound that saved a soul\n\n[Verse 2]\nG              C\nI once was lost but now\nG        D       G\nam found was blind but see";
            $demo2 = "[Verse]\nC               Am\nI heard there was a secret chord\nC              Am\nthat David played and it pleased the Lord\nF                G              C\nbut you don't really care for music do you";
            $st = db()->prepare('INSERT INTO songs (title, artist, original_key, tempo, content) VALUES (?, ?, ?, ?, ?)');
            $st->execute(['Amazing Grace', 'Traditional', 'G', 72, $demo1]);
            $st->execute(['Hallelujah', 'L. Cohen', 'C', 66, $demo2]);
        }
        $msg = 'Setup complete. Tables ready (+ demo songs if empty). DELETE install.php now, then open Songs.';
        $done = true;
    } catch (Throwable $ex) {
        $msg = 'Setup failed: ' . $ex->getMessage();
    }
}
$title = 'Install'; include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:520px;margin:20px auto;">
  <p class="hint"><b>Run once</b>: creates the <code class="inline">songs</code> + <code class="inline">playlists</code> tables in your <?= e(db_driver() === 'pgsql' ? 'Neon Postgres' : 'MySQL') ?> database. Safe to re-run (never deletes data). Afterwards <b>delete this file</b>.</p>
  <?php if ($msg): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
  <?php if (!$done): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="btnrow"><button class="btn" type="submit">Run Setup</button></div>
  </form>
  <?php else: ?>
    <div class="btnrow"><a class="btn" href="index.php">Open Songbook</a></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

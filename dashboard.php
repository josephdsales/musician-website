<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require_admin();
try {
    $n = (int)db()->query('SELECT COUNT(*) AS c FROM songs')->fetch()['c'];
    try { $np = (int)db()->query('SELECT COUNT(*) AS c FROM playlists')->fetch()['c']; }
    catch (Throwable $e2) { $np = 0; }
} catch (Throwable $ex) { $n = 0; $np = 0; }
$title = 'Dashboard'; include __DIR__ . '/includes/header.php';
?>
<div class="grid two">
  <div class="card stat"><div class="n"><?= $n ?></div><div class="l">Songs</div>
    <div class="btnrow" style="justify-content:center"><a class="btn small" href="admin_songs.php">Manage Songs</a><a class="btn small ghost" href="admin_songs.php?action=new">+ New Song</a></div></div>
  <div class="card stat"><div class="n"><?= $np ?></div><div class="l">Playlists</div>
    <div class="btnrow" style="justify-content:center"><a class="btn small" href="admin_playlists.php">Manage Playlists</a><a class="btn small ghost" href="playlists.php">View</a></div></div>
  <div class="card"><h3 style="margin-top:0">🎸 Public page</h3>
    <p class="hint">Clients open the songbook, pick a song, transpose chords. No login needed.</p>
    <div class="btnrow"><a class="btn" href="index.php">Open Songbook</a></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// Public songbook home: search + A–Z song list only. Click opens song.php.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$list = []; $error = '';
try {
    if ($q !== '') {
        if (db_driver() === 'pgsql') {
            $st = db()->prepare("SELECT id, title, artist FROM songs WHERE title ILIKE ? OR artist ILIKE ? ORDER BY title LIMIT 100");
            $st->execute(["%$q%", "%$q%"]);
        } else {
            $st = db()->prepare("SELECT id, title, artist FROM songs WHERE title LIKE ? OR artist LIKE ? ORDER BY title LIMIT 100");
            $st->execute(["%$q%", "%$q%"]);
        }
        $list = $st->fetchAll();
    } else {
        $list = db()->query('SELECT id, title, artist FROM songs ORDER BY title LIMIT 500')->fetchAll();
    }
} catch (Throwable $ex) {
    $error = 'Database not ready. Set DATABASE_URL (Neon) then open install.php. (' . $ex->getMessage() . ')';
}
$title = 'Songs'; include __DIR__ . '/includes/header.php';
?>
<div class="center-stage">
  <?php if ($error): ?><div class="card"><p style="color:#b91c1c"><b><?= e($error) ?></b></p></div><?php endif; ?>

  <div class="card no-print">
    <form method="get" class="pickrow">
      <span class="suggest-wrap">
        <input type="text" name="q" data-suggest="song" placeholder="Type song title or artist..." value="<?= e($q) ?>" autofocus>
      </span>
      <button class="btn small" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="btn small ghost" href="index.php">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h3 style="margin:0 0 8px;text-align:center">
      <?= $q !== '' ? (count($list) . ' match(es) for "' . e($q) . '"') : 'All songs (A–Z)' ?>
    </h3>
    <?php if ($list): ?>
    <div class="songlist" style="max-height:none">
      <?php $letter = ''; foreach ($list as $s): ?>
        <?php $L = strtoupper(substr(trim($s['title']), 0, 1)); if ($L !== $letter): $letter = $L; ?>
          <div class="az-letter"><?= e($letter) ?></div>
        <?php endif; ?>
        <a class="song-item" href="song.php?id=<?= (int)$s['id'] ?>">
          <b><?= e($s['title']) ?></b><?= $s['artist'] !== '' ? '<br><small>' . e($s['artist']) . '</small>' : '' ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php elseif (!$error): ?>
      <p class="hint" style="text-align:center"><?= $q !== '' ? 'No match. Try another title or artist.' : 'No songs yet. Admin → Manage Songs to add one.' ?></p>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

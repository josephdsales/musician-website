<?php
// Public songbook: list + viewer, chords ABOVE lyrics per line, transpose.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$songs = []; $current = null; $error = '';
try {
    if ($q !== '') {
        if (db_driver() === 'pgsql') {
            $st = db()->prepare("SELECT id, title, artist, original_key, tempo FROM songs WHERE title ILIKE ? OR artist ILIKE ? ORDER BY title LIMIT 200");
            $st->execute(["%$q%", "%$q%"]);
        } else {
            $st = db()->prepare("SELECT id, title, artist, original_key, tempo FROM songs WHERE title LIKE ? OR artist LIKE ? ORDER BY title LIMIT 200");
            $st->execute(["%$q%", "%$q%"]);
        }
    } else {
        $st = db()->query("SELECT id, title, artist, original_key, tempo FROM songs ORDER BY title LIMIT 200");
    }
    $songs = $st->fetchAll();
    if ($id > 0) {
        $st = db()->prepare('SELECT * FROM songs WHERE id=?');
        $st->execute([$id]);
        $current = $st->fetch() ?: null;
    }
    if (!$current && count($songs) > 0) {
        // default to first song so the page isn't empty
        $st = db()->prepare('SELECT * FROM songs WHERE id=?');
        $st->execute([$songs[0]['id']]);
        $current = $st->fetch() ?: null;
    }
} catch (Throwable $ex) {
    $error = 'Database not ready. Import database/schema.sql (local MySQL) or set DATABASE_URL (Neon) then open install.php. (' . $ex->getMessage() . ')';
}
$title = 'Songs'; include __DIR__ . '/includes/header.php';
?>
<div class="grid two songbook">
  <div>
    <div class="card">
      <form method="get" class="searchrow">
        <input type="text" name="q" placeholder="Search title or artist..." value="<?= e($q) ?>">
        <button class="btn small" type="submit">Search</button>
      </form>
      <p class="hint"><?= count($songs) ?> song(s)</p>
      <?php if ($error): ?><p style="color:#b91c1c"><b><?= e($error) ?></b></p><?php endif; ?>
      <div class="songlist">
      <?php foreach ($songs as $s): ?>
        <a class="song-item <?= ($current && (int)$current['id'] === (int)$s['id']) ? 'active' : '' ?>"
           href="index.php?q=<?= urlencode($q) ?>&id=<?= (int)$s['id'] ?>">
          <b><?= e($s['title']) ?></b><br>
          <small><?= e(trim(($s['artist'] ?? '') . (!empty($s['original_key']) ? ' • Key ' . $s['original_key'] : ''))) ?></small>
        </a>
      <?php endforeach; ?>
      <?php if (!$songs && !$error): ?><p class="hint">No songs yet. Admin → Manage Songs to add one.</p><?php endif; ?>
      </div>
    </div>
  </div>
  <div>
    <?php if ($current): ?>
    <div class="sheet">
      <h2 style="margin:0"><?= e($current['title']) ?></h2>
      <div class="meta"><?= e(trim(implode(' • ', array_filter([$current['artist'] ?? '', !empty($current['original_key']) ? 'Orig. key ' . $current['original_key'] : '', !empty($current['tempo']) ? $current['tempo'] . ' BPM' : ''])))) ?></div>
      <div class="transpose-bar no-print">
        <button class="btn small ghost" id="tDown" type="button">−</button>
        <b id="tVal">+0</b>
        <button class="btn small ghost" id="tUp" type="button">+</button>
        <button class="btn small ghost" id="tReset" type="button">Reset</button>
        <span class="hint">Transpose chords</span>
      </div>
      <div id="sheetBody" data-content="<?= e($current['content']) ?>"></div>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var steps = 0;
          var body = document.getElementById('sheetBody');
          function draw() {
            body.innerHTML = renderSong(body.getAttribute('data-content'), steps);
            document.getElementById('tVal').textContent = (steps >= 0 ? '+' : '') + steps;
          }
          document.getElementById('tUp').onclick = function () { steps++; draw(); };
          document.getElementById('tDown').onclick = function () { steps--; draw(); };
          document.getElementById('tReset').onclick = function () { steps = 0; draw(); };
          draw();
        });
      </script>
    </div>
    <?php else: ?><div class="card"><p class="hint">Select a song.</p></div><?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// Public songbook: search bar + single centered song, no list. Chords ABOVE lyrics, transpose.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$matches = []; $current = null; $error = '';
try {
    if ($q !== '') {
        if (db_driver() === 'pgsql') {
            $st = db()->prepare("SELECT id, title, artist FROM songs WHERE title ILIKE ? OR artist ILIKE ? ORDER BY title LIMIT 50");
            $st->execute(["%$q%", "%$q%"]);
        } else {
            $st = db()->prepare("SELECT id, title, artist FROM songs WHERE title LIKE ? OR artist LIKE ? ORDER BY title LIMIT 50");
            $st->execute(["%$q%", "%$q%"]);
        }
        $matches = $st->fetchAll();
        // current = requested id if it's in matches, else first match
        $wanted = null;
        foreach ($matches as $m) if ((int)$m['id'] === $id) $wanted = $m;
        if (!$wanted && count($matches) > 0) $wanted = $matches[0];
        if ($wanted) {
            $st = db()->prepare('SELECT * FROM songs WHERE id=?');
            $st->execute([$wanted['id']]);
            $current = $st->fetch() ?: null;
        }
    } else {
        if ($id > 0) {
            $st = db()->prepare('SELECT * FROM songs WHERE id=?');
            $st->execute([$id]);
            $current = $st->fetch() ?: null;
        }
        if (!$current) {
            $row = db()->query('SELECT * FROM songs ORDER BY title LIMIT 1')->fetch();
            $current = $row ?: null;
        }
    }
} catch (Throwable $ex) {
    $error = 'Database not ready. Set DATABASE_URL (Neon) then open install.php. (' . $ex->getMessage() . ')';
}
$title = $current ? $current['title'] : 'Songbook'; include __DIR__ . '/includes/header.php';
?>
<div class="center-stage">
  <?php if ($error): ?><div class="card"><p style="color:#b91c1c"><b><?= e($error) ?></b></p></div><?php endif; ?>

  <div class="card no-print">
    <form method="get" class="pickrow">
      <input type="text" name="q" placeholder="Type song title or artist..." value="<?= e($q) ?>" autofocus style="flex:1;min-width:200px">
      <button class="btn small" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="btn small ghost" href="index.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($q !== ''): ?>
      <?php if (count($matches) > 1): ?>
        <p class="hint" style="text-align:center;margin:8px 0 0">
          <?= count($matches) ?> matches — showing <b><?= e($current ? $current['title'] : '') ?></b>.
          <a href="index.php?q=<?= urlencode($q) ?>&id=<?= (int)prev_match_id($matches, $current ? (int)$current['id'] : 0) ?>">← Prev</a>
          ·
          <a href="index.php?q=<?= urlencode($q) ?>&id=<?= (int)next_match_id($matches, $current ? (int)$current['id'] : 0) ?>">Next →</a>
          (keep typing to narrow down)
        </p>
      <?php elseif (count($matches) === 0): ?>
        <p class="hint" style="text-align:center;margin:8px 0 0">No match for "<b><?= e($q) ?></b>". Try another title or artist.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($current): ?>
  <div class="sheet centered">
    <h2 style="margin:0"><?= e($current['title']) ?></h2>
    <div class="meta"><?= e(trim(implode(' • ', array_filter([$current['artist'] ?? '', !empty($current['original_key']) ? 'Orig. key ' . $current['original_key'] : '', !empty($current['tempo']) ? $current['tempo'] . ' BPM' : ''])))) ?></div>
    <div class="transpose-bar no-print">
      <button class="btn small ghost" id="tDown" type="button">−</button>
      <b id="tVal">+0</b>
      <button class="btn small ghost" id="tUp" type="button">+</button>
      <button class="btn small ghost" id="tReset" type="button">Reset</button>
      <span class="hint">Transpose</span>
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
  <?php elseif (!$error): ?><div class="card"><p class="hint">No songs yet. Admin → Manage Songs to add one.</p></div><?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
// Prev/next match helpers (no list shown, just cycle through matches).
function match_ids(array $matches): array { return array_map(function ($m) { return (int)$m['id']; }, $matches); }
function next_match_id(array $matches, int $cur): int {
    $ids = match_ids($matches);
    if (!$ids) return 0;
    $i = array_search($cur, $ids, true);
    return $ids[$i === false || $i >= count($ids) - 1 ? 0 : $i + 1];
}
function prev_match_id(array $matches, int $cur): int {
    $ids = match_ids($matches);
    if (!$ids) return 0;
    $i = array_search($cur, $ids, true);
    return $ids[$i === false || $i <= 0 ? count($ids) - 1 : $i - 1];
}
?>

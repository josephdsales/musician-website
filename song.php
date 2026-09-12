<?php
// Public: single centered song with transpose. Opened from the A–Z list.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current = null; $error = '';
try {
    if ($id > 0) {
        $st = db()->prepare('SELECT * FROM songs WHERE id=?');
        $st->execute([$id]);
        $current = $st->fetch() ?: null;
    }
    if (!$current) {
        $current = db()->query('SELECT * FROM songs ORDER BY title LIMIT 1')->fetch() ?: null;
    }
} catch (Throwable $ex) {
    $error = 'Database not ready. Set DATABASE_URL (Neon) then open install.php. (' . $ex->getMessage() . ')';
}
$title = $current ? $current['title'] : 'Song'; include __DIR__ . '/includes/header.php';
?>
<div class="center-stage">
  <?php if ($error): ?><div class="card"><p style="color:#b91c1c"><b><?= e($error) ?></b></p></div><?php endif; ?>

  <div class="card no-print">
    <div class="pickrow">
      <a class="btn small ghost" href="index.php">← All songs</a>
      <span class="suggest-wrap">
        <input type="text" id="songJump" data-suggest="song" placeholder="Type another song...">
      </span>
    </div>
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

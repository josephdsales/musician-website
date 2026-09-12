<?php
// Public: playlists — pick a playlist, view its songs in order with saved transpose.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$playlists = []; $plist = null; $items = []; $error = '';
try {
    $playlists = db()->query('SELECT p.*, (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id=p.id) AS song_count FROM playlists p ORDER BY p.title')->fetchAll();
    if ($id > 0) {
        $st = db()->prepare('SELECT * FROM playlists WHERE id=?');
        $st->execute([$id]);
        $plist = $st->fetch() ?: null;
        if ($plist) {
            $st = db()->prepare('SELECT ps.transpose_steps, s.* FROM playlist_songs ps JOIN songs s ON s.id=ps.song_id WHERE ps.playlist_id=? ORDER BY ps.sort_order, ps.id');
            $st->execute([$id]);
            $items = $st->fetchAll();
        }
    }
} catch (Throwable $ex) {
    $error = 'Playlists not ready yet (admin: re-run install.php). (' . $ex->getMessage() . ')';
}
$title = $plist ? $plist['title'] : 'Playlists'; include __DIR__ . '/includes/header.php';
?>
<div class="center-stage">
  <?php if ($error): ?><div class="card"><p style="color:#b91c1c"><b><?= e($error) ?></b></p></div><?php endif; ?>

  <?php if (!$plist): ?>
    <?php foreach ($playlists as $p): ?>
      <div class="card">
        <h3 style="margin:0"><a href="playlists.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h3>
        <p class="hint"><?= (int)$p['song_count'] ?> song(s)</p>
        <div class="btnrow"><a class="btn small" href="playlists.php?id=<?= (int)$p['id'] ?>">Open playlist</a></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$playlists && !$error): ?><div class="card"><p class="hint">No playlists yet.</p></div><?php endif; ?>
  <?php else: ?>
    <div class="card no-print">
      <div class="pickrow">
        <a class="btn small ghost" href="playlists.php">← All playlists</a>
        <span class="hint"><?= count($items) ?> song(s) in order</span>
      </div>
    </div>
    <?php $n = 1; foreach ($items as $song): ?>
      <div class="sheet centered" id="psong<?= $n ?>">
        <h2 style="margin:0"><?= $n ?>. <?= e($song['title']) ?></h2>
        <div class="meta"><?= e(trim(implode(' • ', array_filter([$song['artist'] ?? '', !empty($song['original_key']) ? 'Orig. key ' . $song['original_key'] : '', !empty($song['tempo']) ? $song['tempo'] . ' BPM' : ''])))) ?></div>
        <div class="transpose-bar no-print">
          <button class="btn small ghost" data-down="<?= $n ?>" type="button">−</button>
          <b id="tVal<?= $n ?>"></b>
          <button class="btn small ghost" data-up="<?= $n ?>" type="button">+</button>
          <button class="btn small ghost" data-reset="<?= $n ?>" type="button">Reset</button>
          <span class="hint">Transpose</span>
        </div>
        <div id="sheetBody<?= $n ?>" data-content="<?= e($song['content']) ?>" data-steps="<?= (int)$song['transpose_steps'] ?>"></div>
      </div>
    <?php $n++; endforeach; ?>
    <?php if (!$items): ?><div class="card"><p class="hint">This playlist is empty.</p></div><?php endif; ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var total = <?= count($items) ?>;
        for (var k = 1; k <= total; k++) (function (k) {
          var body = document.getElementById('sheetBody' + k);
          var saved = parseInt(body.getAttribute('data-steps') || '0', 10) || 0;
          var steps = saved;
          function draw() {
            body.innerHTML = renderSong(body.getAttribute('data-content'), steps);
            document.getElementById('tVal' + k).textContent = (steps >= 0 ? '+' : '') + steps;
          }
          document.querySelector('[data-up="' + k + '"]').onclick = function () { steps++; draw(); };
          document.querySelector('[data-down="' + k + '"]').onclick = function () { steps--; draw(); };
          document.querySelector('[data-reset="' + k + '"]').onclick = function () { steps = saved; draw(); };
          draw();
        })(k);
      });
    </script>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

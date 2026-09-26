<?php
// Public songbook home: search + A-Z song list only. Click opens song.php.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

$list = []; $error = '';
try {
    $list = db()->query('SELECT id, title, artist FROM songs ORDER BY title')->fetchAll();
} catch (Throwable $ex) {
    $error = 'Database not ready. Set DATABASE_URL (Neon) then open install.php. (' . $ex->getMessage() . ')';
}
$title = 'Songs'; include __DIR__ . '/includes/header.php';
?>
<div class="center-stage">
  <?php if ($error): ?><div class="card"><p style="color:#b91c1c"><b><?= e($error) ?></b></p></div><?php endif; ?>

  <div class="card no-print">
    <div class="pickrow">
      <input type="text" id="songSearch" placeholder="Type song title or artist..." autofocus>
    </div>
  </div>

  <div class="card">
    <h3 style="margin:0 0 8px;text-align:center" id="songCount"></h3>
    <div class="songlist" id="songList" style="max-height:none">
      <?php if ($list): ?>
        <?php $letter = ''; foreach ($list as $s): ?>
          <?php $L = strtoupper(substr(trim($s['title']), 0, 1)); if ($L !== $letter): $letter = $L; ?>
            <div class="az-letter" data-letter="<?= e($letter) ?>"><?= e($letter) ?></div>
          <?php endif; ?>
          <a class="song-item" href="song.php?id=<?= (int)$s['id'] ?>" data-title="<?= e(mb_strtolower($s['title'])) ?>" data-artist="<?= e(mb_strtolower($s['artist'])) ?>">
            <b><?= e($s['title']) ?></b><?= $s['artist'] !== '' ? '<br><small>' . e($s['artist']) . '</small>' : '' ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php if (!$list && !$error): ?>
      <p class="hint" style="text-align:center">No songs yet. Admin → Manage Songs to add one.</p>
    <?php endif; ?>
    <p class="hint" style="text-align:center;display:none" id="noMatch">No match. Try another title or artist.</p>
  </div>
</div>
<script>
(function () {
  var input = document.getElementById('songSearch');
  var items = document.querySelectorAll('.song-item');
  var letters = document.querySelectorAll('.az-letter');
  var countEl = document.getElementById('songCount');
  var noMatch = document.getElementById('noMatch');
  var songList = document.getElementById('songList');
  if (!input) return;
  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();
    var visible = 0;
    var lastLetter = '';
    for (var i = 0; i < items.length; i++) {
      var it = items[i];
      var show = q === '' || it.getAttribute('data-title').indexOf(q) === 0 || it.getAttribute('data-artist').indexOf(q) === 0;
      it.style.display = show ? '' : 'none';
      if (show) visible++;
    }
    for (var j = 0; j < letters.length; j++) {
      var lg = letters[j].getAttribute('data-letter');
      var next = letters[j].nextElementSibling;
      var hasVisible = false;
      while (next && !next.classList.contains('az-letter')) {
        if (next.classList.contains('song-item') && next.style.display !== 'none') { hasVisible = true; break; }
        next = next.nextElementSibling;
      }
      letters[j].style.display = hasVisible ? '' : 'none';
    }
    if (q !== '') {
      countEl.textContent = '';
    } else {
      countEl.textContent = 'All songs (A–Z)';
    }
    noMatch.style.display = (q !== '' && visible === 0) ? 'block' : 'none';
  });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

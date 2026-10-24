<?php
// Admin: playlists — create/rename/delete, add songs, set per-song transpose, reorder.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require_admin();

$sel = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Resulting key after transposition (same sharp convention as assets/transpose.js).
function transposed_key(?string $orig, int $steps): string
{
    $orig = trim((string)$orig);
    if (!preg_match('/^([A-G](?:#|b)?)(.*)$/', $orig, $m)) return '';
    if ($steps === 0) return $orig;
    $sharps = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    $flats  = ['Db' => 'C#', 'Eb' => 'D#', 'Gb' => 'F#', 'Ab' => 'G#', 'Bb' => 'A#'];
    $root = $flats[$m[1]] ?? $m[1];
    $i = array_search($root, $sharps, true);
    if ($i === false) return $orig;
    return $sharps[(($i + $steps) % 12 + 12) % 12] . $m[2];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $op = $_POST['op'] ?? '';
    try {
        if ($op === 'create_playlist') {
            $t = trim($_POST['title'] ?? '');
            if ($t === '') throw new RuntimeException('Playlist title is required.');
            $st = db()->prepare('INSERT INTO playlists (title) VALUES (?)');
            $st->execute([$t]);
            $sel = (int)db()->lastInsertId();
            set_flash('Playlist created.');
        } elseif ($op === 'rename') {
            $t = trim($_POST['title'] ?? '');
            if ($t === '') throw new RuntimeException('Title is required.');
            $st = db()->prepare('UPDATE playlists SET title=? WHERE id=?');
            $st->execute([$t, (int)$_POST['playlist_id']]);
            $sel = (int)$_POST['playlist_id'];
            set_flash('Playlist renamed.');
        } elseif ($op === 'delete_playlist') {
            $st = db()->prepare('DELETE FROM playlists WHERE id=?');
            $st->execute([(int)$_POST['playlist_id']]);
            $sel = 0;
            set_flash('Playlist deleted.');
        } elseif ($op === 'add_song') {
            $pid = (int)$_POST['playlist_id']; $sid = (int)$_POST['song_id'];
            if ($sid <= 0) throw new RuntimeException('Pick a song to add.');
            $mx = db()->prepare('SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM playlist_songs WHERE playlist_id=?');
            $mx->execute([$pid]);
            $n = (int)$mx->fetch()['n'];
            $st = db()->prepare('INSERT INTO playlist_songs (playlist_id, song_id, sort_order, transpose_steps) VALUES (?,?,?,0)');
            $st->execute([$pid, $sid, $n]);
            $sel = $pid;
            set_flash('Song added to playlist.');
        } elseif ($op === 'save_playlist') {
            $pid = (int)$_POST['playlist_id'];
            $itemIds = $_POST['item_ids'] ?? [];
            $transposes = $_POST['transpose'] ?? [];
            
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $sortOrder = 1;
                $stUpdate = $pdo->prepare('UPDATE playlist_songs SET sort_order=?, transpose_steps=? WHERE id=? AND playlist_id=?');
                foreach ($itemIds as $iid) {
                    $iid = (int)$iid;
                    if ($iid <= 0) continue;
                    $steps = max(-11, min(11, (int)($transposes[$iid] ?? 0)));
                    $stUpdate->execute([$sortOrder++, $steps, $iid, $pid]);
                }
                $pdo->commit();
                $sel = $pid;
                set_flash('Playlist saved.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        } elseif ($op === 'remove_item') {
            $st = db()->prepare('DELETE FROM playlist_songs WHERE id=?');
            $st->execute([(int)$_POST['item_id']]);
            $sel = (int)$_POST['playlist_id'];
            set_flash('Song removed from playlist.');
        }
    } catch (Throwable $ex) {
        set_flash('Error: ' . $ex->getMessage());
        if (!empty($_POST['playlist_id'])) $sel = (int)$_POST['playlist_id'];
    }
    header('Location: admin_playlists.php' . ($sel ? '?id=' . $sel : ''));
    exit;
}

$playlists = []; $items = []; $plist = null; $allSongs = [];
try {
    $playlists = db()->query('SELECT p.*, (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id=p.id) AS song_count FROM playlists p ORDER BY p.title')->fetchAll();
    $allSongs = db()->query('SELECT id, title, artist FROM songs ORDER BY title LIMIT 500')->fetchAll();
    if ($sel > 0) {
        $st = db()->prepare('SELECT * FROM playlists WHERE id=?');
        $st->execute([$sel]);
        $plist = $st->fetch() ?: null;
        if ($plist) {
            $st = db()->prepare('SELECT ps.*, s.title, s.artist, s.original_key FROM playlist_songs ps JOIN songs s ON s.id=ps.song_id WHERE ps.playlist_id=? ORDER BY ps.sort_order, ps.id');
            $st->execute([$sel]);
            $items = $st->fetchAll();
        } else $sel = 0;
    }
} catch (Throwable $ex) {
    set_flash('Database not ready: re-run install.php to add playlist tables. (' . $ex->getMessage() . ')');
}
$title = 'Manage Playlists'; include __DIR__ . '/includes/header.php';
?>
<div class="grid two">
  <div class="card">
    <h3 style="margin-top:0">Playlists</h3>
    <form method="post" class="pickrow">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="op" value="create_playlist">
      <input type="text" name="title" placeholder="New playlist title..." required style="flex:1;min-width:160px">
      <button class="btn small" type="submit">+ Create</button>
    </form>
    <div class="table-wrap"><table>
      <tr><th>Title</th><th>Songs</th><th></th></tr>
      <?php foreach ($playlists as $p): ?>
      <tr>
        <td><b><?= e($p['title']) ?></b></td>
        <td><?= (int)$p['song_count'] ?></td>
        <td style="white-space:nowrap"><a class="btn small <?= $sel === (int)$p['id'] ? '' : 'ghost' ?>" href="admin_playlists.php?id=<?= (int)$p['id'] ?>">Open</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$playlists): ?><tr><td colspan="3" class="hint">No playlists yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
  <div class="card">
    <?php if ($plist): ?>
      <h3 style="margin-top:0"><?= e($plist['title']) ?> <span class="hint">(<?= count($items) ?> songs)</span></h3>
      <form method="post" class="pickrow">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="rename">
        <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
        <input type="text" name="title" value="<?= e($plist['title']) ?>" required style="flex:1;min-width:160px">
        <button class="btn small" type="submit">Rename</button>
      </form>
      <h3>Add song</h3>
      <form method="post" class="pickrow">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="add_song">
        <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
        <select name="song_id" style="flex:1;min-width:160px">
          <option value="0">— pick a song —</option>
          <?php foreach ($allSongs as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= e($s['title'] . ($s['artist'] !== '' ? ' — ' . $s['artist'] : '')) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn small" type="submit">Add</button>
      </form>
      <h3>Songs in order</h3>
      <?php if ($items): ?>
      <form method="post" id="playlistForm">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" id="formOp" value="save_playlist">
        <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">

        <div id="songsList">
        <?php foreach ($items as $it): ?>
          <div class="card song-card" style="margin:8px 0" data-item-id="<?= (int)$it['id'] ?>">
            <input type="hidden" name="item_ids[]" value="<?= (int)$it['id'] ?>">
            <b><?= e($it['title']) ?></b>
            <span class="hint"><?= e(trim(($it['artist'] ?? '') . (!empty($it['original_key']) ? ' • Key ' . $it['original_key'] : ''))) ?></span>
            
            <div class="pickrow" style="margin-top:8px">
              <label style="margin:0">Transpose</label>
              <input type="number" class="ts-input" name="transpose[<?= (int)$it['id'] ?>]" min="-11" max="11" value="<?= (int)$it['transpose_steps'] ?>" style="width:80px">
              <?php $ok = trim((string)($it['original_key'] ?? '')); $ksteps = (int)$it['transpose_steps']; $kto = transposed_key($ok, $ksteps); ?>
              <?php if ($ok !== '' && $kto !== ''): ?>
              <span class="hint ts-key" data-orig="<?= e($ok) ?>" style="white-space:nowrap"><?= $ksteps !== 0 ? 'Key ' . e($ok) . ' → <b>' . e($kto) . '</b>' : 'Key ' . e($ok) ?></span>
              <?php endif; ?>
            </div>
            
            <div class="btnrow" style="margin-top:8px">
              <button class="btn small ghost move-btn" data-dir="up" type="button">↑ Up</button>
              <button class="btn small ghost move-btn" data-dir="down" type="button">↓ Down</button>
              <button class="btn small danger remove-btn" data-item-id="<?= (int)$it['id'] ?>" type="button">Remove</button>
              <a class="btn small ghost" href="playlists.php?id=<?= (int)$plist['id'] ?>" target="_blank">View</a>
            </div>
          </div>
        <?php endforeach; ?>
        </div>

        <div class="btnrow" style="margin-top:16px">
          <button class="btn" type="submit">Save Playlist Changes</button>
        </div>
      </form>
      <?php else: ?>
        <p class="hint">Empty — add songs above.</p>
      <?php endif; ?>
      <hr>
      <form method="post" onsubmit="return confirm('Delete this playlist? Songs are kept.')">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="delete_playlist">
        <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
        <button class="btn danger small" type="submit">Delete playlist</button>
      </form>
    <?php else: ?>
      <p class="hint">Select <b>Open</b> on a playlist, or create one.</p>
    <?php endif; ?>
  </div>
</div>
<script>
(function () {
  function keyAfter(orig, steps) {
    orig = String(orig || '').trim();
    var m = orig.match(/^([A-G](?:#|b)?)(.*)$/);
    if (!m) return '';
    if (!steps) return orig;
    var sharp = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    var flats = { Db: 'C#', Eb: 'D#', Gb: 'F#', Ab: 'G#', Bb: 'A#' };
    var root = flats[m[1]] || m[1];
    var i = sharp.indexOf(root);
    if (i === -1) return orig;
    return sharp[(((i + steps) % 12) + 12) % 12] + m[2];
  }
  document.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.remove-btn');
    if (removeBtn) {
      if (!confirm('Remove this song from the playlist?')) return;
      var itemId = removeBtn.getAttribute('data-item-id');
      var form = document.getElementById('playlistForm');
      document.getElementById('formOp').value = 'remove_item';
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'item_id';
      input.value = itemId;
      form.appendChild(input);
      form.submit();
      return;
    }
    
    var moveBtn = e.target.closest('.move-btn');
    if (moveBtn) {
      var card = moveBtn.closest('.song-card');
      if (!card) return;
      var dir = moveBtn.getAttribute('data-dir');
      if (dir === 'up') {
        var prev = card.previousElementSibling;
        if (prev) card.parentNode.insertBefore(card, prev);
      } else if (dir === 'down') {
        var next = card.nextElementSibling;
        if (next) card.parentNode.insertBefore(next, card);
      }
    }
  });

  document.addEventListener('input', function (e) {
    var el = e.target;
    if (!el || !el.classList || !el.classList.contains('ts-input')) return;
    var span = el.parentElement ? el.parentElement.querySelector('.ts-key') : null;
    if (!span) return;
    var orig = span.getAttribute('data-orig') || '';
    var steps = parseInt(el.value, 10) || 0;
    var to = keyAfter(orig, steps);
    if (!orig || !to) { span.textContent = ''; return; }
    span.textContent = '';
    if (steps) {
      span.appendChild(document.createTextNode('Key ' + orig + ' \u2192 '));
      var b = document.createElement('b');
      b.textContent = to;
      span.appendChild(b);
    } else {
      span.textContent = 'Key ' + orig;
    }
  });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

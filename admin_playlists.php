<?php
// Admin: playlists — create/rename/delete, add songs, set per-song transpose, reorder.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require_admin();

$sel = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
        } elseif ($op === 'save_item') {
            $iid = (int)$_POST['item_id'];
            $steps = max(-11, min(11, (int)$_POST['transpose_steps']));
            $st = db()->prepare('UPDATE playlist_songs SET transpose_steps=? WHERE id=?');
            $st->execute([$steps, $iid]);
            $sel = (int)$_POST['playlist_id'];
            set_flash('Transpose saved (' . ($steps >= 0 ? '+' : '') . $steps . ').');
        } elseif ($op === 'remove_item') {
            $st = db()->prepare('DELETE FROM playlist_songs WHERE id=?');
            $st->execute([(int)$_POST['item_id']]);
            $sel = (int)$_POST['playlist_id'];
            set_flash('Song removed from playlist.');
        } elseif ($op === 'move') {
            $iid = (int)$_POST['item_id']; $pid = (int)$_POST['playlist_id'];
            $dir = $_POST['dir'] ?? 'up';
            $items = db()->prepare('SELECT id, sort_order FROM playlist_songs WHERE playlist_id=? ORDER BY sort_order, id');
            $items->execute([$pid]);
            $rows = $items->fetchAll();
            $idx = null;
            foreach ($rows as $i => $r) if ((int)$r['id'] === $iid) $idx = $i;
            if ($idx !== null) {
                $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
                if (isset($rows[$swap])) {
                    $a = $rows[$idx]; $b = $rows[$swap];
                    $u = db()->prepare('UPDATE playlist_songs SET sort_order=? WHERE id=?');
                    $u->execute([$b['sort_order'], $a['id']]);
                    $u->execute([$a['sort_order'], $b['id']]);
                }
            }
            $sel = $pid;
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
      <?php $pos = 1; foreach ($items as $it): ?>
        <div class="card" style="margin:8px 0">
          <b><?= $pos++ ?>. <?= e($it['title']) ?></b>
          <span class="hint"><?= e(trim(($it['artist'] ?? '') . (!empty($it['original_key']) ? ' • Key ' . $it['original_key'] : ''))) ?></span>
          <form method="post" class="pickrow" style="margin-top:8px">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="op" value="save_item">
            <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
            <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
            <label style="margin:0">Transpose</label>
            <input type="number" name="transpose_steps" min="-11" max="11" value="<?= (int)$it['transpose_steps'] ?>" style="width:80px">
            <button class="btn small" type="submit">Save</button>
          </form>
          <div class="btnrow">
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="op" value="move">
              <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
              <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
              <input type="hidden" name="dir" value="up">
              <button class="btn small ghost" type="submit">↑</button>
            </form>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="op" value="move">
              <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
              <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
              <input type="hidden" name="dir" value="down">
              <button class="btn small ghost" type="submit">↓</button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Remove this song from the playlist?')">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="op" value="remove_item">
              <input type="hidden" name="playlist_id" value="<?= (int)$plist['id'] ?>">
              <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
              <button class="btn small danger" type="submit">Remove</button>
            </form>
            <a class="btn small ghost" href="playlists.php?id=<?= (int)$plist['id'] ?>" target="_blank">View</a>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$items): ?><p class="hint">Empty — add 4–5 songs above.</p><?php endif; ?>
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
<?php include __DIR__ . '/includes/footer.php'; ?>

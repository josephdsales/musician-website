<?php
// Admin: add / edit / delete songs (CBA-style CRUD).
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require_admin();

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $op = $_POST['op'] ?? '';
    $title_f = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $key = trim($_POST['original_key'] ?? '');
    $tempo = trim($_POST['tempo'] ?? '');
    $content = $_POST['content'] ?? '';
    $tempoVal = $tempo === '' ? null : (int)$tempo;
    try {
        if ($op === 'delete') {
            $st = db()->prepare('DELETE FROM songs WHERE id=?');
            $st->execute([(int)$_POST['id']]);
            set_flash('Song deleted.');
            header('Location: admin_songs.php'); exit;
        }
        if ($title_f === '') throw new RuntimeException('Title is required.');
        if ($op === 'update') {
            $st = db()->prepare('UPDATE songs SET title=?, artist=?, original_key=?, tempo=?, content=?' .
                (db_driver() === 'pgsql' ? ', updated_at=NOW() WHERE id=?' : ' WHERE id=?'));
            $st->execute([$title_f, $artist, $key, $tempoVal, $content, (int)$_POST['id']]);
            set_flash('Song updated.');
            header('Location: admin_songs.php?id=' . (int)$_POST['id'] . '&action=edit'); exit;
        }
        $st = db()->prepare('INSERT INTO songs (title, artist, original_key, tempo, content) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$title_f, $artist, $key, $tempoVal, $content]);
        set_flash('Song added.');
        header('Location: admin_songs.php'); exit;
    } catch (Throwable $ex) {
        set_flash('Error: ' . $ex->getMessage());
        header('Location: admin_songs.php' . ($op === 'update' ? '?id=' . (int)$_POST['id'] . '&action=edit' : '?action=new')); exit;
    }
}

$songs = []; $edit = null;
try {
    $songs = db()->query('SELECT id, title, artist, original_key, tempo FROM songs ORDER BY title LIMIT 200')->fetchAll();
    if ($action === 'edit' && $editId > 0) {
        $st = db()->prepare('SELECT * FROM songs WHERE id=?');
        $st->execute([$editId]);
        $edit = $st->fetch() ?: null;
    }
} catch (Throwable $ex) {
    set_flash('Database not ready: open install.php first. (' . $ex->getMessage() . ')');
}
if ($action === 'new') $edit = ['id' => 0, 'title' => '', 'artist' => '', 'original_key' => '', 'tempo' => '', 'content' => "[Verse 1]\nG              C\nYour lyric line here\n"];
$title = 'Manage Songs'; include __DIR__ . '/includes/header.php';
?>
<div class="grid two">
  <div class="card">
    <div class="btnrow" style="margin-top:0"><a class="btn small" href="admin_songs.php?action=new">+ New song</a></div>
    <div class="table-wrap"><table>
      <tr><th>Title</th><th></th></tr>
      <?php foreach ($songs as $s): ?>
      <tr>
        <td><b><?= e($s['title']) ?></b><br><span class="hint"><?= e($s['artist']) ?></span></td>
        <td style="white-space:nowrap"><a class="btn small ghost" href="admin_songs.php?id=<?= (int)$s['id'] ?>&action=edit">Edit</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$songs): ?><tr><td colspan="2" class="hint">No songs yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
  <div class="card">
    <?php if ($edit): ?>
      <h3 style="margin-top:0"><?= $edit['id'] ? 'Edit song' : 'New song' ?></h3>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="<?= $edit['id'] ? 'update' : 'create' ?>">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label>Title *</label><input type="text" name="title" required value="<?= e($edit['title']) ?>">
        <label>Artist</label><input type="text" name="artist" value="<?= e($edit['artist']) ?>">
        <div class="grid two">
          <div><label>Original key (G, Am...)</label><input type="text" name="original_key" value="<?= e($edit['original_key']) ?>"></div>
          <div><label>Tempo BPM</label><input type="number" name="tempo" value="<?= e($edit['tempo']) ?>"></div>
        </div>
        <label>Content — chord line ABOVE lyric line · blank line = gap · [Verse] = section</label>
        <textarea name="content" rows="14" style="font-family:ui-monospace,Consolas,monospace"><?= e($edit['content']) ?></textarea>
        <div class="btnrow">
          <button class="btn" type="submit">Save</button>
          <?php if ($edit['id']): ?>
          <button class="btn danger" type="submit" name="op" value="delete" onclick="this.form.op.value='delete';return confirm('Delete this song?')">Delete</button>
          <?php endif; ?>
        </div>
      </form>
      <?php if ($edit['id']): ?>
      <h3>Preview</h3>
      <div class="sheet"><div id="prevBody" data-content="<?= e($edit['content']) ?>"></div></div>
      <script>document.addEventListener('DOMContentLoaded',function(){var b=document.getElementById('prevBody');if(b&&window.renderSong)b.innerHTML=renderSong(b.getAttribute('data-content'),0);});</script>
      <?php endif; ?>
    <?php else: ?>
      <p class="hint">Select <b>Edit</b> or <b>+ New song</b>.</p>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// JSON suggestions for search boxes: ?type=song|playlist&q=...
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? 'song';
$q = trim($_GET['q'] ?? '');
if ($q === '') { echo '[]'; exit; }

try {
    if ($type === 'playlist') {
        if (db_driver() === 'pgsql') {
            $st = db()->prepare("SELECT p.id, p.title, (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id=p.id) AS song_count FROM playlists p WHERE p.title ILIKE ? ORDER BY p.title LIMIT 8");
        } else {
            $st = db()->prepare("SELECT p.id, p.title, (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id=p.id) AS song_count FROM playlists p WHERE p.title LIKE ? ORDER BY p.title LIMIT 8");
        }
        $st->execute(["%$q%"]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'],
                'sub' => ((int)$r['song_count']) . ' song(s)',
                'url' => 'playlists.php?id=' . (int)$r['id'],
            ];
        }
        echo json_encode($out);
    } else {
        if (db_driver() === 'pgsql') {
            $st = db()->prepare("SELECT id, title, artist FROM songs WHERE title ILIKE ? OR artist ILIKE ? ORDER BY title LIMIT 8");
        } else {
            $st = db()->prepare("SELECT id, title, artist FROM songs WHERE title LIKE ? OR artist LIKE ? ORDER BY title LIMIT 8");
        }
        $st->execute(["%$q%", "%$q%"]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'],
                'sub' => $r['artist'] ?? '',
                'url' => 'index.php?id=' . (int)$r['id'],
            ];
        }
        echo json_encode($out);
    }
} catch (Throwable $ex) {
    echo '[]';
}

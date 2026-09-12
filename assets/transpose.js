// Songbook chord tools: detect chord lines, transpose, render chords-above-lyrics.
var SHARP = ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'];
var FLAT_TO_SHARP = { 'Db':'C#','Eb':'D#','Gb':'F#','Ab':'G#','Bb':'A#' };
var CHORD_RE = /^[A-G](?:#|b)?(?:m(?!aj)|maj|min|dim|aug|sus|add|m|°|ø)?[0-9]*(?:[#b]?[0-9]+)*(?:\/[A-G](?:#|b)?)?$/;

function escHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}
function isChordToken(tok) {
  if (!tok || tok.charAt(0) === '[') return false;
  var clean = tok.replace(/[()\[\]]/g, '');
  return CHORD_RE.test(clean);
}
function isChordLine(line) {
  var toks = String(line).trim().split(/\s+/).filter(Boolean);
  if (!toks.length) return false;
  if (/^\[.*\]$/.test(String(line).trim())) return false;
  if (isLabelLine(line)) return false;
  var hits = 0;
  for (var i = 0; i < toks.length; i++) if (isChordToken(toks[i])) hits++;
  return hits / toks.length >= 0.6;
}
// Short "Intro:" / "Verse 1:" style labels render as section headers.
function isLabelLine(line) {
  return /^[A-Za-z0-9][A-Za-z0-9 '\-()]{0,28}:$/.test(String(line).trim());
}
function transposeNote(note, steps) {
  var n = FLAT_TO_SHARP[note] || note;
  var i = SHARP.indexOf(n);
  if (i === -1) return note;
  return SHARP[(((i + steps) % 12) + 12) % 12];
}
function transposeChord(token, steps) {
  return String(token).replace(/[A-G](?:#|b)?/g, function (m, offset, full) {
    var before = full.slice(0, offset);
    var isBass = before.indexOf('/') !== -1;
    var stripped = before.replace(/[[(]/g, '');
    var isRoot = offset <= 2 && !/[A-G]/.test(stripped);
    if (isRoot || isBass) return transposeNote(m, steps);
    return m;
  });
}
function transposeChordLine(line, steps) {
  if (!steps) return line;
  return String(line).split(/(\s+)/).map(function (part) {
    if (/^\s*$/.test(part) || part === '') return part;
    if (isChordToken(part)) return transposeChord(part, steps);
    return part;
  }).join('');
}
// Returns HTML string: chords ABOVE lyrics per line.
function renderSong(content, steps) {
  var lines = String(content || '').replace(/\r/g, '').split('\n');
  var html = '';
  for (var i = 0; i < lines.length; i++) {
    var line = lines[i];
    if (!line.trim()) { html += '<div style="height:10px"></div>'; continue; }
    if (/^\[.*\]/.test(line.trim())) { html += '<div class="section">' + escHtml(line.trim()) + '</div>'; continue; }
    var next = (i + 1 < lines.length) ? lines[i + 1] : '';
    if (isChordLine(line)) {
      // Standalone chord lines (Intro / instrumental, no lyric below) must
      // still transpose — render them as a chords-only row.
      if (next.trim() && !isChordLine(next) && !/^\[.*\]/.test(next.trim()) && !isLabelLine(next)) {
        html += '<div class="line"><div class="chords">' + escHtml(transposeChordLine(line, steps)) + '</div><div class="lyrics">' + escHtml(next) + '</div></div>';
        i++;
      } else {
        html += '<div class="line"><div class="chords">' + escHtml(transposeChordLine(line, steps)) + '</div><div class="lyrics"></div></div>';
      }
    } else if (isLabelLine(line)) {
      html += '<div class="section">' + escHtml(line.trim()) + '</div>';
    } else {
      html += '<div class="line"><div class="chords"></div><div class="lyrics">' + escHtml(line) + '</div></div>';
    }
  }
  return html;
}

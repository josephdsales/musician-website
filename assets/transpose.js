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
        html += '<div class="line"><div class="chords">' + chordSpansHtml(line, steps) + '</div><div class="lyrics">' + escHtml(next) + '</div></div>';
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

// Chord tokens with their start column (tabs expanded to 8) so chords can be
// placed by column instead of by trusting the spaces between them.
function chordColumns(line) {
  var s = String(line), out = [], i = 0, col = 0;
  while (i < s.length) {
    var c = s.charAt(i);
    if (c === '\t') { col += 8 - (col % 8); i++; continue; }
    if (/\s/.test(c)) { col++; i++; continue; }
    var start = i, startCol = col;
    while (i < s.length && !/\s/.test(s.charAt(i))) { i++; col++; }
    out.push({ col: startCol, text: s.slice(start, i) });
  }
  return out;
}
function chordSpansHtml(line, steps) {
  var toks = chordColumns(line), html = '';
  for (var i = 0; i < toks.length; i++) {
    var t = toks[i];
    var txt = (steps && isChordToken(t.text)) ? transposeChord(t.text, steps) : t.text;
    html += '<span data-col="' + t.col + '">' + escHtml(txt) + '</span>';
  }
  return html;
}

function rangeRect(node, start, end) {
  var r = document.createRange();
  r.setStart(node, start);
  r.setEnd(node, end);
  return r.getBoundingClientRect();
}
// Exact x (relative to the line's left edge) of column `col` in the LYRICS row.
// Measured from the rendered lyric text itself, so it is correct for any font,
// size, or device; tabs are expanded to 8 with interpolation inside the tab.
function chordLeftFor(lyEl, col) {
  var node = lyEl.firstChild;
  if (!node || node.nodeType !== 3 || !node.data) return 0;
  var text = node.data;
  try {
    var lyLeft = lyEl.getBoundingClientRect().left;
    var c = 0, i = 0;
    while (i < text.length && c < col) {
      var ch = text.charAt(i);
      if (ch === '\t') {
        var adv = 8 - (c % 8);
        if (col <= c + adv) {
          var x0 = rangeRect(node, 0, i).right - lyLeft;
          var x1 = rangeRect(node, 0, i + 1).right - lyLeft;
          return x0 + (x1 - x0) * ((col - c) / adv);
        }
        c += adv; i++;
      } else { c++; i++; }
    }
    if (i >= text.length && col > c && c > 0) {
      var full = rangeRect(node, 0, text.length).right - lyLeft;
      return full + (col - c) * (full / c);
    }
    return (i > 0 ? rangeRect(node, 0, i).right : rangeRect(node, 0, 0).left) - lyLeft;
  } catch (e) { return 0; }
}
function layoutChords(root) {
  if (!root || !root.querySelectorAll) return;
  var lines = root.querySelectorAll('.line');
  for (var i = 0; i < lines.length; i++) {
    var ch = lines[i].querySelector('.chords');
    var ly = lines[i].querySelector('.lyrics');
    if (!ch || !ly) continue;
    var spans = ch.querySelectorAll('span[data-col]');
    for (var j = 0; j < spans.length; j++) {
      var col = parseInt(spans[j].getAttribute('data-col'), 10) || 0;
      spans[j].style.left = chordLeftFor(ly, col) + 'px';
    }
  }
}
function layoutAllSongs() {
  if (typeof document === 'undefined' || !document.querySelectorAll) return;
  var els = document.querySelectorAll('[data-content]');
  for (var i = 0; i < els.length; i++) layoutChords(els[i]);
}

if (typeof window !== 'undefined' && window.addEventListener) {
  var _relayoutTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(_relayoutTimer);
    _relayoutTimer = setTimeout(layoutAllSongs, 120);
  });
  window.addEventListener('orientationchange', function () { setTimeout(layoutAllSongs, 250); });
  window.addEventListener('load', function () { setTimeout(layoutAllSongs, 0); });
}

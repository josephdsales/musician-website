// Live search suggestions for song + playlist search boxes.
// Usage: <span class="suggest-wrap"><input data-suggest="song|playlist" ...></span>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[data-suggest]').forEach(attachAutocomplete);
});

function attachAutocomplete(input) {
  var type = input.getAttribute('data-suggest') || 'song';
  var box = document.createElement('div');
  box.className = 'suggest-box';
  box.style.display = 'none';
  input.parentNode.appendChild(box);

  var items = [], active = -1, timer = null, lastQ = '';

  function close() { box.style.display = 'none'; active = -1; }

  function render() {
    if (!items.length) {
      box.innerHTML = '<div class="suggest-empty">No matches — press Enter to search.</div>';
    } else {
      box.innerHTML = '';
      items.forEach(function (it, i) {
        var a = document.createElement('a');
        a.className = 'suggest-item' + (i === active ? ' active' : '');
        a.href = it.url;
        var b = document.createElement('b');
        b.textContent = it.title;
        a.appendChild(b);
        if (it.sub) {
          a.appendChild(document.createElement('br'));
          var s = document.createElement('small');
          s.textContent = it.sub;
          a.appendChild(s);
        }
        // mousedown fires before input blur -> navigation wins
        a.addEventListener('mousedown', function (e) {
          e.preventDefault();
          window.location.href = it.url;
        });
        a.addEventListener('mousemove', function () { setActive(i); });
        box.appendChild(a);
      });
    }
    box.style.display = 'block';
  }

  function setActive(i) {
    active = i;
    var els = box.querySelectorAll('.suggest-item');
    for (var k = 0; k < els.length; k++) {
      if (k === i) els[k].classList.add('active');
      else els[k].classList.remove('active');
    }
  }

  input.setAttribute('autocomplete', 'off');

  input.addEventListener('input', function () {
    var q = input.value.trim();
    clearTimeout(timer);
    if (q.length < 1) { close(); return; }
    timer = setTimeout(function () {
      lastQ = q;
      fetch('suggest.php?type=' + encodeURIComponent(type) + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (input.value.trim() !== lastQ) return; // stale response
          items = Array.isArray(data) ? data : [];
          active = -1;
          render();
        })
        .catch(function () { close(); });
    }, 200);
  });

  input.addEventListener('keydown', function (e) {
    if (box.style.display === 'none') return;
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active < items.length - 1 ? active + 1 : 0); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active > 0 ? active - 1 : items.length - 1); }
    else if (e.key === 'Enter' && active >= 0 && items[active]) { e.preventDefault(); window.location.href = items[active].url; }
    else if (e.key === 'Escape') { close(); }
  });

  input.addEventListener('blur', function () { setTimeout(close, 150); });
  input.addEventListener('focus', function () {
    if (input.value.trim().length >= 1 && (items.length || true)) {
      // re-trigger fetch on focus if text present
      var ev = new Event('input');
      input.dispatchEvent(ev);
    }
  });
}

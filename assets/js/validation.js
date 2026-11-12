/* KopiKita – validasi form sisi klien (server tetap memvalidasi ulang) */
(function () {
  'use strict';
  function setErr(input, msg) {
    input.classList.add('is-invalid');
    var box = input.closest('.form-group') || input.parentElement;
    var el = box.querySelector('.form-error.js');
    if (!el) { el = document.createElement('div'); el.className = 'form-error js'; box.appendChild(el); }
    el.textContent = msg;
  }
  function clearErr(input) {
    input.classList.remove('is-invalid');
    var box = input.closest('.form-group') || input.parentElement;
    var el = box.querySelector('.form-error.js');
    if (el) el.remove();
  }
  function check(input) {
    clearErr(input);
    var v = input.value.trim();
    if (input.required && v === '') { setErr(input, 'Kolom ini wajib diisi.'); return false; }
    if (v === '') return true;
    if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { setErr(input, 'Format email tidak valid.'); return false; }
    if (input.minLength > 0 && v.length < input.minLength) { setErr(input, 'Minimal ' + input.minLength + ' karakter.'); return false; }
    if (input.pattern && !new RegExp('^(?:' + input.pattern + ')$').test(v)) { setErr(input, 'Format tidak sesuai.'); return false; }
    if (input.dataset.match) {
      var other = document.getElementById(input.dataset.match);
      if (other && other.value !== input.value) { setErr(input, 'Isian tidak sama.'); return false; }
    }
    return true;
  }
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var fields = form.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]), select, textarea');
    fields.forEach(function (f) { f.addEventListener('blur', function () { check(f); }); });
    form.addEventListener('submit', function (e) {
      var ok = true;
      fields.forEach(function (f) { if (!check(f)) ok = false; });
      if (!ok) { e.preventDefault(); var bad = form.querySelector('.is-invalid'); bad && bad.focus(); }
    });
  });
})();

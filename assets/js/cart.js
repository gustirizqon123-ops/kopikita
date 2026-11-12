/* KopiKita – interaksi keranjang & detail produk (harga final tetap dihitung server) */
(function () {
  'use strict';
  var rupiah = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };
  var $ = function (s, c) { return (c || document).querySelector(s); };

  // Kontrol jumlah (+/−) generik
  document.addEventListener('click', function (e) {
    var minus = e.target.closest('[data-qty-minus]'), plus = e.target.closest('[data-qty-plus]');
    if (!minus && !plus) return;
    var ctl = e.target.closest('.qty-control'), inp = ctl && $('input', ctl);
    if (!inp) return;
    var v = parseInt(inp.value, 10) || 1, max = parseInt(inp.dataset.max || inp.max || '99', 10);
    v = minus ? Math.max(1, v - 1) : Math.min(max, v + 1);
    inp.value = v;
    inp.dispatchEvent(new Event('input', { bubbles: true }));
    inp.dispatchEvent(new Event('change', { bubbles: true }));
  });

  // Keranjang: kirim form otomatis saat ukuran/jumlah/catatan berubah
  document.querySelectorAll('form[data-autosubmit]').forEach(function (f) {
    var t;
    var go = function () { clearTimeout(t); t = setTimeout(function () { f.submit(); }, 450); };
    f.addEventListener('change', go);
    var q = f.querySelector('input[name=qty]');
    if (q) q.addEventListener('input', go);
  });

  // Halaman detail produk: total live + tambah via fetch
  var form = $('#addToCartForm');
  if (form) {
    var base = parseFloat($('#detailPrice').dataset.base), extra = parseInt(form.dataset.largeExtra, 10) || 0;
    var qty = $('#qty'), totalEl = $('#detailTotal'), priceEl = $('#detailPrice');
    var recalc = function () {
      var sz = form.querySelector('input[name=size]:checked');
      var unit = base + (sz && sz.value === 'large' ? extra : 0);
      var q = Math.max(1, parseInt(qty.value, 10) || 1);
      priceEl.textContent = rupiah(unit);
      totalEl.textContent = rupiah(unit * q);
    };
    form.addEventListener('input', recalc);
    form.addEventListener('change', recalc);
    recalc();

    form.addEventListener('submit', function (e) {
      if (!window.fetch) return;
      e.preventDefault();
      var btn = form.querySelector('button[type=submit]');
      if (btn) btn.disabled = true;
      var fd = new FormData(form);
      fd.append('csrf_token', window.KK.csrf);
      fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          toast(d.message || (d.ok ? 'Ditambahkan ke keranjang' : 'Gagal menambahkan'), d.ok ? '' : 'error');
          if (d.ok && window.updateBadge) updateBadge(d.count);
        })
        .catch(function () { toast('Terjadi kesalahan jaringan.', 'error'); })
        .finally(function () { if (btn) btn.disabled = false; });
    });
  }
})();

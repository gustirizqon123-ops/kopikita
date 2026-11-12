/* KopiKita – POS kasir. Total di sini hanya tampilan; server menghitung ulang harga, stok, dan total. */
(function () {
  'use strict';
  var D = window.POS, $ = function (id) { return document.getElementById(id); };
  var fmt = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };
  var cart = [];            // {pid, size, qty, notes}
  var cat = 'all', q = '';
  var byId = {};
  D.products.forEach(function (p) { byId[p.id] = p; });

  function unit(p, size) { return p.price + (p.has_size_option == 1 && size === 'large' ? D.sizePrices.large : 0); }
  function total() { return cart.reduce(function (s, l) { return s + unit(byId[l.pid], l.size) * l.qty; }, 0); }
  function qtyOf(pid) { return cart.reduce(function (s, l) { return s + (l.pid === pid ? l.qty : 0); }, 0); }

  function renderProducts() {
    var box = $('posProducts'); box.innerHTML = '';
    D.products.forEach(function (p) {
      if (cat !== 'all' && String(p.category_id) !== cat) return;
      if (q && p.name.toLowerCase().indexOf(q) === -1) return;
      var b = document.createElement('button'); b.type = 'button'; b.className = 'pos-item';
      var out = p.stock <= 0; b.disabled = out;
      b.innerHTML = '<img alt=""><div class="pi-body"><strong></strong><span class="pi-price"></span><br><small></small></div>';
      b.querySelector('img').src = p.img;
      b.querySelector('strong').textContent = p.name;
      b.querySelector('.pi-price').textContent = fmt(p.price);
      b.querySelector('small').textContent = out ? 'Habis' : 'Stok ' + p.stock;
      b.addEventListener('click', function () { add(p.id); });
      box.appendChild(b);
    });
  }

  function add(pid) {
    var p = byId[pid], size = p.has_size_option == 1 ? 'regular' : null;
    if (qtyOf(pid) + 1 > p.stock) { return err('Stok ' + p.name + ' tidak mencukupi.'); }
    var l = cart.find(function (x) { return x.pid === pid && x.size === size; });
    if (l) l.qty++; else cart.push({ pid: pid, size: size, qty: 1, notes: '' });
    err(''); renderCart();
  }

  function renderCart() {
    var box = $('posLines'); box.innerHTML = '';
    if (!cart.length) { box.innerHTML = '<div class="pos-empty">Belum ada item. Klik produk untuk menambah.</div>'; }
    cart.forEach(function (l, i) {
      var p = byId[l.pid], el = document.createElement('div'); el.className = 'pos-line';
      el.innerHTML = '<div class="pl-top"><span></span><span></span></div><div class="pl-ctl"></div>';
      el.querySelector('.pl-top span').textContent = p.name;
      el.querySelectorAll('.pl-top span')[1].textContent = fmt(unit(p, l.size) * l.qty);
      var ctl = el.querySelector('.pl-ctl');
      var qty = document.createElement('div'); qty.className = 'qty';
      qty.innerHTML = '<button type="button">−</button><span></span><button type="button">+</button>';
      qty.querySelector('span').textContent = l.qty;
      var bt = qty.querySelectorAll('button');
      bt[0].onclick = function () { l.qty--; if (l.qty < 1) cart.splice(i, 1); renderCart(); };
      bt[1].onclick = function () { if (qtyOf(l.pid) + 1 > p.stock) return err('Stok ' + p.name + ' tidak mencukupi.'); l.qty++; renderCart(); };
      ctl.appendChild(qty);
      if (p.has_size_option == 1) {
        var sel = document.createElement('select');
        Object.keys(D.sizeLabels).forEach(function (k) { var o = document.createElement('option'); o.value = k; o.textContent = D.sizeLabels[k]; if (k === l.size) o.selected = true; sel.appendChild(o); });
        sel.onchange = function () {
          var other = cart.find(function (x) { return x !== l && x.pid === l.pid && x.size === sel.value; });
          if (other) { other.qty += l.qty; cart.splice(i, 1); } else { l.size = sel.value; }
          renderCart();
        };
        ctl.appendChild(sel);
      }
      var n = document.createElement('input'); n.type = 'text'; n.placeholder = 'Catatan'; n.maxLength = 150; n.value = l.notes;
      n.oninput = function () { l.notes = n.value; };
      ctl.appendChild(n);
      box.appendChild(el);
    });
    $('posTotal').textContent = fmt(total());
    quick(); calc();
  }

  function method() { return document.querySelector('input[name=pos_method]:checked').value; }
  function paid() { return parseInt(($('posPaid').value || '').replace(/\D/g, ''), 10) || 0; }
  function calc() {
    var diff = paid() - total(), box = $('posChange');
    box.classList.toggle('minus', diff < 0);
    $('posChangeVal').textContent = !paid() ? '-' : (diff < 0 ? 'Kurang ' + fmt(-diff) : fmt(diff));
  }
  function quick() {
    var t = total(), box = $('posQuick'); box.innerHTML = '';
    if (!t) return;
    [t, 20000, 50000, 100000, 200000].forEach(function (a, i) {
      if (i && a <= t) return;
      var b = document.createElement('button'); b.type = 'button'; b.textContent = i === 0 ? 'Uang pas' : fmt(a);
      b.onclick = function () { $('posPaid').value = a; calc(); }; box.appendChild(b);
    });
  }
  function err(m) { var e = $('posError'); e.textContent = m; e.style.display = m ? '' : 'none'; }

  function syncUI() {
    var m = method();
    $('posCashBox').style.display = m === 'cash' ? '' : 'none';
    $('posRefBox').style.display = m === 'cash' ? 'none' : '';
    var t = document.querySelector('input[name=pos_type]:checked').value;
    $('posTableGroup').style.display = t === 'dine_in' ? '' : 'none';
  }

  $('posCats').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    cat = b.dataset.cat;
    this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('active', x === b); });
    renderProducts();
  });
  $('posSearch').addEventListener('input', function () { q = this.value.toLowerCase().trim(); renderProducts(); });
  $('posPaid').addEventListener('input', calc);
  document.querySelectorAll('input[name=pos_method],input[name=pos_type]').forEach(function (r) { r.addEventListener('change', syncUI); });
  $('posClear').addEventListener('click', function () { cart = []; err(''); renderCart(); });

  $('posPay').addEventListener('click', function () {
    err('');
    if (!cart.length) return err('Tambahkan produk terlebih dahulu.');
    var m = method(), type = document.querySelector('input[name=pos_type]:checked').value;
    if (type === 'dine_in' && !$('posTable').value.trim()) return err('Nomor meja wajib diisi untuk dine-in.');
    if (m === 'cash' && paid() < total()) return err('Uang diterima kurang dari total.');
    if (m !== 'cash' && !$('posRef').value.trim()) return err('Nomor referensi wajib diisi.');
    var btn = this; btn.disabled = true;
    fetch(D.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.KK.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: JSON.stringify({
        customer_id: $('posCustomer').value, order_type: type, table_number: $('posTable').value, notes: $('posNotes').value,
        payment_method: m, amount_paid: paid(), reference: $('posRef').value,
        items: cart.map(function (l) { return { product_id: l.pid, size: l.size, qty: l.qty, notes: l.notes }; })
      })
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) { btn.disabled = false; return err(d.message || 'Gagal menyimpan.'); }
      window.open(d.receipt, '_blank');
      window.location.href = d.detail;
    }).catch(function () { btn.disabled = false; err('Terjadi kesalahan jaringan.'); });
  });

  renderProducts(); renderCart(); syncUI();
})();

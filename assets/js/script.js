/* KopiKita – interaksi umum: navbar, search, slider, carousel, filter chip, lightbox, reveal, toast */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // Toast global
  window.toast = function (msg, type) {
    var wrap = $('#toastWrap');
    if (!wrap) return;
    var t = document.createElement('div');
    t.className = 'toast' + (type === 'error' ? ' error' : '');
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(function () { t.remove(); }, 3200);
  };

  // Hamburger
  var navToggle = $('#navToggle'), navLinks = $('#navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var open = navLinks.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', function (e) {
      if (!navLinks.contains(e.target) && !navToggle.contains(e.target)) {
        navLinks.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Dropdown user
  var um = $('#userMenu');
  if (um) {
    var btn = $('.user-menu-btn', um);
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = um.classList.toggle('open');
      btn.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', function () { um.classList.remove('open'); });
  }

  // Search overlay
  var so = $('#searchOverlay');
  if (so) {
    $$('[data-open-search]').forEach(function (b) {
      b.addEventListener('click', function () { so.classList.add('open'); var i = $('input', so); i && i.focus(); });
    });
    $$('[data-close-search]').forEach(function (b) { b.addEventListener('click', function () { so.classList.remove('open'); }); });
    so.addEventListener('click', function (e) { if (e.target === so) so.classList.remove('open'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') so.classList.remove('open'); });
  }

  // Alert close
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('alert-close')) e.target.parentElement.remove();
  });

  // Tampil/sembunyikan password
  $$('[data-toggle-pass]').forEach(function (b) {
    b.addEventListener('click', function () {
      var inp = document.getElementById(b.dataset.togglePass);
      if (inp) inp.type = inp.type === 'password' ? 'text' : 'password';
    });
  });

  // Hero slider
  var slides = $$('.hero-slide');
  if (slides.length > 1) {
    var dotsWrap = $('.slider-dots'), idx = 0, timer;
    var show = function (i) {
      idx = (i + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('active', k === idx); });
      $$('button', dotsWrap).forEach(function (d, k) { d.classList.toggle('active', k === idx); });
    };
    slides.forEach(function (_, k) {
      var d = document.createElement('button');
      d.type = 'button';
      d.setAttribute('aria-label', 'Slide ' + (k + 1));
      d.addEventListener('click', function () { show(k); restart(); });
      dotsWrap.appendChild(d);
    });
    var restart = function () { clearInterval(timer); timer = setInterval(function () { show(idx + 1); }, 6500); };
    show(0); restart();
  }

  // Carousel
  $$('[data-carousel]').forEach(function (c) {
    var track = $('.carousel-track', c);
    var step = function () { var it = track.children[0]; return it ? it.getBoundingClientRect().width + 22 : 260; };
    var prev = $('[data-prev]', c.parentElement), next = $('[data-next]', c.parentElement);
    prev && prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
    next && next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
  });

  // Filter chip (koleksi & galeri) – data-filter-group + data-cat pada item
  $$('[data-filter-group]').forEach(function (group) {
    var target = document.querySelector(group.dataset.filterGroup);
    if (!target) return;
    $$('.chip', group).forEach(function (chip) {
      chip.addEventListener('click', function (e) {
        if (chip.tagName === 'A') e.preventDefault();
        $$('.chip', group).forEach(function (c) { c.classList.remove('active'); });
        chip.classList.add('active');
        var f = chip.dataset.filter;
        $$('[data-cat]', target).forEach(function (it) {
          it.classList.toggle('hidden', f !== 'all' && it.dataset.cat !== f);
        });
      });
    });
  });

  // Lightbox galeri
  var lb = $('#lightbox');
  if (lb) {
    var items = [], cur = 0, img = $('img', lb), cap = $('.lb-caption', lb);
    var open = function (i) {
      items = $$('.gallery-item:not(.hidden)');
      cur = (i + items.length) % items.length;
      var it = items[cur];
      img.src = it.dataset.full; img.alt = it.dataset.title; cap.textContent = it.dataset.title;
      lb.classList.add('open');
    };
    $$('.gallery-item').forEach(function (it) {
      it.addEventListener('click', function () { items = $$('.gallery-item:not(.hidden)'); open(items.indexOf(it)); });
    });
    $('.lb-close', lb).addEventListener('click', function () { lb.classList.remove('open'); });
    $('.lb-prev', lb).addEventListener('click', function () { open(cur - 1); });
    $('.lb-next', lb).addEventListener('click', function () { open(cur + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) lb.classList.remove('open'); });
    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'Escape') lb.classList.remove('open');
      if (e.key === 'ArrowLeft') open(cur - 1);
      if (e.key === 'ArrowRight') open(cur + 1);
    });
  }

  // Animasi fade-in saat scroll
  var rev = $$('.reveal');
  if ('IntersectionObserver' in window && rev.length) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('visible'); io.unobserve(en.target); } });
    }, { threshold: .12 });
    rev.forEach(function (r) { io.observe(r); });
  } else {
    rev.forEach(function (r) { r.classList.add('visible'); });
  }

  // Tombol "tambah ke keranjang" cepat (kartu produk)
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-quick-add]');
    if (!b) return;
    e.preventDefault();
    if (!window.KK || !window.KK.loggedIn) { window.location.href = window.KK.loginUrl + '?redirect=' + encodeURIComponent(location.pathname + location.search); return; }
    if (window.KK.role !== 'customer') { toast('Keranjang hanya untuk akun pelanggan.', 'error'); return; }
    b.disabled = true;
    var fd = new FormData();
    fd.append('action', 'add'); fd.append('product_id', b.dataset.quickAdd); fd.append('qty', 1);
    fd.append('size', 'regular'); fd.append('csrf_token', window.KK.csrf);
    fetch(window.KK.base + '/customer/cart-action.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        toast(d.message || (d.ok ? 'Ditambahkan ke keranjang' : 'Gagal'), d.ok ? '' : 'error');
        if (d.ok) updateBadge(d.count);
      })
      .catch(function () { toast('Terjadi kesalahan jaringan.', 'error'); })
      .finally(function () { b.disabled = false; });
  });

  window.updateBadge = function (n) {
    var bd = $('#cartBadge');
    if (!bd) return;
    bd.textContent = n; bd.hidden = !(n > 0);
  };
})();

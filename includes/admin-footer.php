<?php
/**
 * Penutup layout admin/kasir. Variabel opsional: $extraScripts (array), $inlineScript (string JS)
 */
?>
        </div>
    </div>
</div>
<script>
(function () {
    // Sidebar mobile
    var sb = document.getElementById('sidebar'), bd = document.getElementById('sidebarBackdrop'), tg = document.getElementById('menuToggle');
    function toggle(open) { sb.classList.toggle('open', open); bd.classList.toggle('open', open); }
    if (tg) tg.addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
    if (bd) bd.addEventListener('click', function () { toggle(false); });
    // Tutup alert
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('alert-close')) e.target.parentElement.remove();
    });
    // Konfirmasi aksi
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });
    // Jam
    var clock = document.getElementById('clock');
    if (clock) setInterval(function () {
        clock.textContent = new Date().toLocaleString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }, 1000);
    // Pratinjau gambar upload
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var img = document.getElementById(inp.dataset.preview);
            var f = inp.files && inp.files[0];
            if (!f || !img) return;
            if (f.size > 2 * 1024 * 1024) { alert('Ukuran file maksimal 2 MB.'); inp.value = ''; return; }
            img.src = URL.createObjectURL(f);
        });
    });
})();
</script>
<?php foreach (($extraScripts ?? []) as $js): ?>
<script src="<?= e(strpos($js, 'http') === 0 ? $js : asset('js/' . $js)) ?>"></script>
<?php endforeach; ?>
<?php if (!empty($inlineScript)): ?><script><?= $inlineScript ?></script><?php endif; ?>
</body>
</html>

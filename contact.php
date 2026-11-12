<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Contact';
$pageDesc = 'Hubungi KopiKita: alamat, jam operasional, telepon, dan WhatsApp.';
$mapQuery = rawurlencode('Makassar, Sulawesi Selatan');
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a> / Contact</div>
        <h1>Hubungi <em>Kami</em></h1>
        <p>Mampir, telepon, atau kirim pesan. Kami senang mendengar dari Anda.</p>
    </section>

    <div class="contact-grid">
        <section class="panel card-block reveal" style="padding:28px">
            <div class="contact-item"><div class="ico"><?= icon('pin') ?></div><div><strong>Alamat</strong><?= e(SHOP_ADDRESS) ?><br><small class="text-muted">(alamat simulasi untuk proyek ini)</small></div></div>
            <div class="contact-item"><div class="ico"><?= icon('clock') ?></div><div><strong>Jam Operasional</strong>
                <?php foreach (SHOP_HOURS as $day => $hour): ?><?= e($day) ?>: <?= e($hour) ?><br><?php endforeach; ?></div></div>
            <div class="contact-item"><div class="ico"><?= icon('phone') ?></div><div><strong>Telepon</strong><?= e(SHOP_PHONE) ?></div></div>
            <div class="contact-item"><div class="ico"><?= icon('mail') ?></div><div><strong>Email</strong><?= e(SHOP_EMAIL) ?></div></div>
            <div class="mt-3">
                <a class="btn btn-primary" target="_blank" rel="noopener" href="https://wa.me/<?= e(SHOP_WHATSAPP) ?>?text=<?= rawurlencode('Halo KopiKita, saya ingin bertanya.') ?>"><?= icon('whatsapp') ?> Chat WhatsApp</a>
            </div>
        </section>
        <section class="reveal">
            <iframe class="map-frame" title="Peta lokasi" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.openstreetmap.org/export/embed.html?bbox=119.38%2C-5.20%2C119.46%2C-5.10&amp;layer=mapnik"></iframe>
        </section>
    </div>

    <section class="section" id="faq">
        <div class="section-head"><h2 class="section-title">Pertanyaan <em>Umum</em></h2></div>
        <div class="panel" style="padding:28px">
            <div class="contact-item"><div><strong>Apakah bisa memesan online?</strong>Bisa. Daftar akun, pilih menu, lalu checkout. Pembayaran dikonfirmasi kasir (simulasi).</div></div>
            <div class="contact-item"><div><strong>Metode pembayaran apa saja?</strong>Tunai, QRIS, dan transfer bank. QRIS dan transfer hanya simulasi, tidak terhubung payment gateway.</div></div>
            <div class="contact-item"><div><strong>Apakah ada opsi ukuran?</strong>Minuman tersedia Regular dan Large (+<?= e(rupiah(SIZE_PRICES['large'])) ?>).</div></div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

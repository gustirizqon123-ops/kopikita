<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About';
$pageDesc = 'Cerita KopiKita: kedai kopi yang dibangun oleh pecinta kopi.';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a> / About</div>
        <h1>Cerita <em>KopiKita</em></h1>
        <p>Dari satu mesin espresso kecil menjadi ruang berkumpul favorit warga.</p>
    </section>

    <section class="section">
        <div class="panel about-panel reveal">
            <span class="watermark" aria-hidden="true">KopiKita</span>
            <div class="about-grid">
                <div class="about-photo"><img src="<?= e(url('assets/images/ui/about-1.svg')) ?>" alt="Barista menyeduh kopi" loading="lazy"></div>
                <div class="about-copy">
                    <span class="eyebrow">Tentang Kami</span>
                    <h2 class="mt-2">Sebuah kedai yang dibangun oleh <em>pecinta kopi</em></h2>
                    <p>KopiKita berdiri pada <?= e(APP_EST) ?> dengan keyakinan sederhana: secangkir kopi yang baik membuat hari lebih baik. Kami memilih biji dari petani lokal, menyangrainya setiap hari, dan menyajikannya di ruang yang hangat untuk bekerja, bertemu, atau sekadar rehat.</p>
                    <p>Setiap menu dirancang bersama barista kami, dari espresso klasik sampai signature drinks yang hanya ada di KopiKita.</p>
                    <div class="feature-cards">
                        <div class="feature-card"><div class="ico"><?= icon('bean') ?></div><h4>Biji Pilihan</h4><p>Dikurasi langsung dari kebun lokal.</p></div>
                        <div class="feature-card"><div class="ico"><?= icon('award') ?></div><h4>Diracik Barista Ahli</h4><p>Terlatih dan peduli detail.</p></div>
                        <div class="feature-card"><div class="ico"><?= icon('leaf') ?></div><h4>Ramah Lingkungan</h4><p>Ampas kopi diolah kembali.</p></div>
                    </div>
                </div>
                <div class="about-photo offset"><img src="<?= e(url('assets/images/ui/about-2.svg')) ?>" alt="Suasana kedai" loading="lazy"></div>
            </div>
            <div class="stats">
                <div class="stat"><strong><?= e(APP_EST) ?></strong><span>Berdiri</span></div>
                <div class="stat"><strong>10+</strong><span>Barista</span></div>
                <div class="stat"><strong>100%</strong><span>Biji Lokal</span></div>
                <div class="stat"><strong>5K+</strong><span>Pelanggan Bahagia</span></div>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

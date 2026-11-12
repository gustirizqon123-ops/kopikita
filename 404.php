<?php
require_once __DIR__ . '/includes/auth.php';
http_response_code(404);
$pageTitle = 'Tidak Ditemukan';
$hideNewsletter = true;
require __DIR__ . '/includes/header.php';
?>
<section class="error-page">
    <div>
        <div class="error-code">404</div>
        <h1>Halaman <em>tidak ditemukan</em></h1>
        <p>Sepertinya cangkir yang Anda cari sudah habis. Coba kembali ke beranda atau lihat menu kami.</p>
        <div class="flex" style="justify-content:center">
            <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">Ke Beranda <span class="arrow">›</span></a>
            <a href="<?= e(url('menu.php')) ?>" class="btn btn-secondary">Lihat Menu</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

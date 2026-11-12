<?php
require_once __DIR__ . '/includes/auth.php';
http_response_code(403);
$pageTitle = 'Akses Ditolak';
$hideNewsletter = true;
$u = current_user();
require __DIR__ . '/includes/header.php';
?>
<section class="error-page">
    <div>
        <div class="error-code">403</div>
        <h1>Akses <em>ditolak</em></h1>
        <p>Maaf, akun Anda tidak memiliki hak untuk membuka halaman ini.</p>
        <div class="flex" style="justify-content:center">
            <a href="<?= e(url($u ? home_for_role($u['role']) : 'index.php')) ?>" class="btn btn-primary"><?= $u ? 'Ke Halaman Saya' : 'Ke Beranda' ?> <span class="arrow">›</span></a>
            <?php if (!$u): ?><a href="<?= e(url('login.php')) ?>" class="btn btn-secondary">Sign In</a><?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

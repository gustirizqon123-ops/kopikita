<?php
require_once __DIR__ . '/includes/auth.php';

// Logout hanya lewat POST + CSRF (mencegah logout paksa via link)
if (is_post()) {
    verify_csrf();
    logout_user();
    session_start();
    flash('success', 'Anda telah logout. Sampai jumpa lagi!');
    redirect('index.php');
}

if (!current_user()) {
    redirect('index.php');
}

$pageTitle = 'Logout';
require __DIR__ . '/includes/header.php';
?>
<section class="error-page">
    <div class="panel section-pad" style="max-width:480px;margin:0 auto">
        <h1>Keluar dari <em>KopiKita</em>?</h1>
        <p>Keranjang Anda akan dikosongkan.</p>
        <form method="post" class="flex" style="justify-content:center">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit">Ya, Logout</button>
            <a href="javascript:history.back()" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</section>
<?php
$hideNewsletter = true;
require __DIR__ . '/includes/footer.php';

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';
$user = require_role(['customer']);
$pdo = db();

$st = $pdo->prepare("SELECT o.*, s.payment_status FROM orders o JOIN customers c ON c.id = o.customer_id
                     LEFT JOIN sales s ON s.order_id = o.id
                     WHERE c.user_id = ? AND o.order_status NOT IN ('completed','cancelled') ORDER BY o.ordered_at DESC LIMIT 5");
$st->execute([$user['id']]);
$active = $st->fetchAll();

$best = $pdo->query('SELECT p.*, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id
                     WHERE p.is_best_seller = 1 AND p.is_available = 1 AND c.is_active = 1 ORDER BY p.name LIMIT 8')->fetchAll();

$pageTitle = 'Beranda Saya';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <h1>Halo, <em><?= e(explode(' ', $user['name'])[0]) ?></em>!</h1>
        <p>Mau ngopi apa hari ini?</p>
        <div class="hero-actions" style="justify-content:center;margin-top:20px">
            <a class="btn btn-primary" href="<?= e(url('customer/menu.php')) ?>">Pesan Sekarang</a>
            <a class="btn btn-secondary" href="<?= e(url('customer/orders.php')) ?>">Riwayat Pesanan</a>
        </div>
    </section>

    <section class="section">
        <div class="section-head"><h2 class="section-title">Pesanan <em>Aktif</em></h2></div>
        <?php if (!$active): ?>
            <p class="text-muted">Tidak ada pesanan yang sedang diproses.</p>
        <?php else: foreach ($active as $o): ?>
            <div class="active-order">
                <div><strong class="text-cream"><?= e($o['order_code']) ?></strong><br><small class="text-muted"><?= e(tanggal($o['ordered_at'])) ?> · <?= e(rupiah($o['total_amount'])) ?></small></div>
                <div><?= status_badge($o['order_status']) ?> <?= payment_badge($o['payment_status']) ?></div>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('customer/order-detail.php?code=' . urlencode($o['order_code']))) ?>">Lacak</a>
            </div>
        <?php endforeach; endif; ?>
    </section>

    <section class="section">
        <div class="section-head"><h2 class="section-title">Menu <em>Favorit</em></h2><a class="btn btn-secondary btn-sm" href="<?= e(url('customer/menu.php')) ?>">Semua Menu</a></div>
        <div class="product-grid"><?php foreach ($best as $p) { echo render_product_card($p, true); } ?></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['customer']);
$pdo = db();

$base = 'FROM orders o JOIN customers c ON c.id = o.customer_id LEFT JOIN sales s ON s.order_id = o.id WHERE c.user_id = ?';
$cnt = $pdo->prepare("SELECT COUNT(*) $base");
$cnt->execute([$user['id']]);
$pg = paginate((int) $cnt->fetchColumn(), 10);
$st = $pdo->prepare("SELECT o.*, s.payment_status, (SELECT SUM(quantity) FROM order_details WHERE order_id = o.id) AS item_count
                     $base ORDER BY o.ordered_at DESC, o.id DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$st->execute([$user['id']]);
$rows = $st->fetchAll();

$pageTitle = 'Pesanan Saya';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('customer/index.php')) ?>">Home</a> / Pesanan Saya</div>
        <h1>Pesanan <em>Saya</em></h1>
    </section>
    <?php if (!$rows): ?>
        <div class="empty-state"><div class="big">Belum ada pesanan</div><a class="btn btn-primary" href="<?= e(url('customer/menu.php')) ?>">Mulai Pesan</a></div>
    <?php else: ?>
        <section class="panel mt-4"><div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kode</th><th>Tanggal</th><th class="num">Item</th><th class="num">Total</th><th>Status</th><th>Pembayaran</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['order_code']) ?></strong></td>
                        <td><?= e(tanggal($r['ordered_at'])) ?></td>
                        <td class="num"><?= (int) $r['item_count'] ?></td>
                        <td class="num"><?= e(rupiah($r['total_amount'])) ?></td>
                        <td><?= status_badge($r['order_status']) ?></td>
                        <td><?= payment_badge($r['payment_status']) ?></td>
                        <td><a class="btn btn-secondary btn-sm" href="<?= e(url('customer/order-detail.php?code=' . urlencode($r['order_code']))) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></section>
        <?= pagination_links($pg) ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

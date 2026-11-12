<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['cashier', 'admin']);
$pdo = db();

$cnt = $pdo->query("SELECT
        SUM(order_status = 'pending') AS pending,
        SUM(order_status IN ('pending','confirmed','preparing','ready') AND id IN (SELECT order_id FROM sales WHERE payment_status = 'pending')) AS unpaid,
        SUM(order_status = 'preparing') AS preparing,
        SUM(order_status = 'ready') AS ready
        FROM orders")->fetch();
$st = $pdo->prepare('SELECT COUNT(*) c, COALESCE(SUM(amount_due),0) t FROM sales WHERE payment_status = "paid" AND DATE(paid_at) = CURDATE() AND cashier_id = ?');
$st->execute([$user['id']]);
$today = $st->fetch();
$allToday = $pdo->query('SELECT COALESCE(SUM(amount_due),0) FROM sales WHERE payment_status = "paid" AND DATE(paid_at) = CURDATE()')->fetchColumn();

$latest = $pdo->query("SELECT o.id, o.order_code, o.ordered_at, o.order_status, o.total_amount, c.name AS customer_name, s.payment_status
        FROM orders o JOIN customers c ON c.id = o.customer_id LEFT JOIN sales s ON s.order_id = o.id
        WHERE o.order_status IN ('pending','confirmed','preparing','ready') ORDER BY o.ordered_at ASC LIMIT 8")->fetchAll();

$pageTitle = 'Dashboard Kasir';
require __DIR__ . '/../includes/cashier-header.php';
?>
<div class="stat-cards">
    <div class="stat-card"><div class="ico gray"><?= icon('clock') ?></div><div><small>Pesanan pending</small><strong><?= (int) $cnt['pending'] ?></strong></div></div>
    <div class="stat-card"><div class="ico yellow"><?= icon('cash') ?></div><div><small>Menunggu pembayaran</small><strong><?= (int) $cnt['unpaid'] ?></strong></div></div>
    <div class="stat-card"><div class="ico orange"><?= icon('coffee') ?></div><div><small>Sedang dibuat</small><strong><?= (int) $cnt['preparing'] ?></strong></div></div>
    <div class="stat-card"><div class="ico green"><?= icon('check') ?></div><div><small>Siap diambil</small><strong><?= (int) $cnt['ready'] ?></strong></div></div>
    <div class="stat-card"><div class="ico brown"><?= icon('receipt') ?></div><div><small>Omzet shift saya hari ini</small><strong><?= e(rupiah($today['t'])) ?></strong><small><?= (int) $today['c'] ?> transaksi · total toko <?= e(rupiah($allToday)) ?></small></div></div>
</div>
<div class="actions mb-3">
    <a class="btn btn-primary btn-lg" href="<?= e(url('cashier/pos.php')) ?>"><?= icon('pos') ?> Transaksi Baru (POS)</a>
    <a class="btn btn-outline btn-lg" href="<?= e(url('cashier/orders/index.php')) ?>">Pesanan Masuk</a>
</div>
<div class="card">
    <div class="card-header"><h2>Antrian Pesanan</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Waktu</th><th>Status</th><th>Bayar</th><th class="text-right">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($latest as $r): ?>
            <tr><td><strong><?= e($r['order_code']) ?></strong></td><td><?= e($r['customer_name']) ?></td><td><?= e(tanggal($r['ordered_at'])) ?></td>
                <td><?= status_badge($r['order_status']) ?></td><td><?= payment_badge($r['payment_status']) ?></td>
                <td class="text-right nowrap"><?= e(rupiah($r['total_amount'])) ?></td>
                <td><a class="btn btn-primary btn-sm" href="<?= e(url('cashier/orders/show.php?id=' . $r['id'])) ?>">Proses</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$latest): ?><tr><td colspan="7" class="table-empty">Tidak ada antrian.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/cashier-footer.php'; ?>

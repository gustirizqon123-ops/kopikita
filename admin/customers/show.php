<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();
$st = $pdo->prepare('SELECT c.*, u.username, u.is_active, u.last_login_at FROM customers c LEFT JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$st->execute([(int) ($_GET['id'] ?? 0)]);
$c = $st->fetch();
if (!$c) {
    abort(404);
}
$st = $pdo->prepare('SELECT o.*, s.payment_status FROM orders o LEFT JOIN sales s ON s.order_id = o.id WHERE o.customer_id = ? ORDER BY o.ordered_at DESC LIMIT 50');
$st->execute([$c['id']]);
$orders = $st->fetchAll();

$pageTitle = 'Detail Pelanggan';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="grid-2">
    <div class="card"><div class="card-header"><h2><?= e($c['name']) ?></h2></div><div class="card-body">
        <dl class="dl">
            <dt>Telepon</dt><dd><?= e($c['phone'] ?: '-') ?></dd>
            <dt>Email</dt><dd><?= e($c['email'] ?: '-') ?></dd>
            <dt>Alamat</dt><dd><?= e($c['address'] ?: '-') ?></dd>
            <dt>Username</dt><dd><?= e($c['username'] ?? '— (tanpa akun)') ?></dd>
            <dt>Login terakhir</dt><dd><?= e(tanggal($c['last_login_at'] ?? null)) ?></dd>
            <dt>Bergabung</dt><dd><?= e(tanggal($c['created_at'], false)) ?></dd>
        </dl>
    </div></div>
    <div class="card"><div class="card-header"><h2>Ringkasan</h2></div><div class="card-body">
        <?php $done = array_filter($orders, fn($o) => $o['order_status'] === 'completed'); ?>
        <dl class="dl"><dt>Total pesanan</dt><dd><?= count($orders) ?></dd><dt>Pesanan selesai</dt><dd><?= count($done) ?></dd>
            <dt>Total belanja</dt><dd><?= e(rupiah(array_sum(array_column($done, 'total_amount')))) ?></dd></dl>
    </div></div>
</div>
<div class="card mt-3"><div class="card-header"><h2>Riwayat Pesanan</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kode</th><th>Waktu</th><th>Status</th><th>Bayar</th><th class="text-right">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr><td><strong><?= e($o['order_code']) ?></strong></td><td><?= e(tanggal($o['ordered_at'])) ?></td><td><?= status_badge($o['order_status']) ?></td>
                <td><?= payment_badge($o['payment_status']) ?></td><td class="text-right nowrap"><?= e(rupiah($o['total_amount'])) ?></td>
                <td><a class="btn btn-outline btn-sm" href="<?= e(url('admin/orders/show.php?id=' . $o['id'])) ?>">Detail</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="6" class="table-empty">Belum ada pesanan.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<p class="mt-3"><a class="btn btn-outline btn-sm" href="<?= e(url('admin/customers/index.php')) ?>">← Kembali</a></p>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

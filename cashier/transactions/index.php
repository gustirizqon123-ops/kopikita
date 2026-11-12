<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_role(['cashier', 'admin']);
$pdo = db();
[$from, $to] = date_range('-6 days');
$mine = $user['role'] === 'cashier' ? true : !empty($_GET['mine']);  // kasir hanya melihat transaksinya

$where = 's.payment_status = "paid" AND DATE(s.paid_at) BETWEEN ? AND ?';
$args = [$from, $to];
if ($mine) {
    $where .= ' AND s.cashier_id = ?';
    $args[] = $user['id'];
}
$sum = $pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(s.amount_due),0) t FROM sales s WHERE $where");
$sum->execute($args);
$sum = $sum->fetch();

$pg = paginate((int) $sum['c'], 15);
$st = $pdo->prepare("SELECT s.*, o.id AS oid, o.order_code, o.order_status, c.name AS customer_name, u.name AS cashier_name
        FROM sales s JOIN orders o ON o.id = s.order_id JOIN customers c ON c.id = o.customer_id LEFT JOIN users u ON u.id = s.cashier_id
        WHERE $where ORDER BY s.paid_at DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

$pageTitle = 'Riwayat Transaksi';
require __DIR__ . '/../../includes/cashier-header.php';
?>
<div class="card mb-3"><div class="card-body">
    <form class="filter-bar" method="get">
        <div class="form-group"><label class="form-label" for="from">Dari</label><input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
        <div class="form-group"><label class="form-label" for="to">Sampai</label><input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>"></div>
        <?php if ($user['role'] === 'admin'): ?><label class="check"><input type="checkbox" name="mine" value="1" <?= $mine ? 'checked' : '' ?>> Hanya transaksi saya</label><?php endif; ?>
        <button class="btn btn-dark btn-sm" type="submit">Filter</button>
    </form>
</div></div>
<div class="stat-cards">
    <div class="stat-card"><div class="ico green"><?= icon('receipt') ?></div><div><small>Jumlah transaksi</small><strong><?= (int) $sum['c'] ?></strong></div></div>
    <div class="stat-card"><div class="ico orange"><?= icon('cash') ?></div><div><small>Total diterima</small><strong><?= e(rupiah($sum['t'])) ?></strong></div></div>
</div>
<div class="card"><div class="table-wrap">
    <table class="table">
        <thead><tr><th>Waktu bayar</th><th>Invoice</th><th>Pesanan</th><th>Pelanggan</th><th>Metode</th><th class="text-right">Total</th><th>Kasir</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="nowrap"><?= e(tanggal($r['paid_at'])) ?></td><td><?= e($r['invoice_number']) ?></td>
                <td><?= e($r['order_code']) ?> <?= status_badge($r['order_status']) ?></td><td><?= e($r['customer_name']) ?></td>
                <td><?= e(PAYMENT_METHODS[$r['payment_method']]) ?></td><td class="text-right nowrap"><?= e(rupiah($r['amount_due'])) ?></td>
                <td><?= e($r['cashier_name'] ?? '-') ?></td>
                <td><div class="actions"><a class="btn btn-outline btn-sm" href="<?= e(url('cashier/orders/show.php?id=' . $r['oid'])) ?>">Detail</a>
                    <a class="btn btn-outline btn-sm" target="_blank" href="<?= e(url('cashier/receipt.php?id=' . $r['oid'])) ?>">Struk</a></div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Belum ada transaksi pada rentang ini.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?= pagination_links($pg) ?>
<?php require __DIR__ . '/../../includes/cashier-footer.php'; ?>

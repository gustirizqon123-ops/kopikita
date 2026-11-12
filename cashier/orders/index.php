<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['cashier', 'admin']);
$pdo = db();

$rows = $pdo->query("SELECT o.*, c.name AS customer_name, s.payment_status, s.payment_method,
        (SELECT GROUP_CONCAT(CONCAT(od.quantity, '× ', od.product_name) SEPARATOR '||') FROM order_details od WHERE od.order_id = o.id) AS item_list
        FROM orders o JOIN customers c ON c.id = o.customer_id LEFT JOIN sales s ON s.order_id = o.id
        WHERE o.order_status IN ('pending','confirmed','preparing','ready')
        ORDER BY FIELD(o.order_status,'pending','confirmed','preparing','ready'), o.ordered_at ASC")->fetchAll();

ob_start();
if (!$rows): ?>
    <div class="card"><div class="card-body table-empty">Tidak ada pesanan aktif. ☕</div></div>
<?php else: ?>
    <div class="order-board">
    <?php foreach ($rows as $r): ?>
        <article class="order-tile st-<?= e($r['order_status']) ?>">
            <div class="ot-head">
                <div><div class="ot-code"><?= e($r['order_code']) ?></div><small class="text-muted"><?= e(tanggal($r['ordered_at'])) ?></small></div>
                <div style="text-align:right"><?= status_badge($r['order_status']) ?><br><?= payment_badge($r['payment_status']) ?></div>
            </div>
            <div><strong><?= e($r['customer_name']) ?></strong>
                <span class="text-muted">· <?= e(ORDER_TYPES[$r['order_type']]) ?><?= $r['table_number'] ? ' · Meja ' . e($r['table_number']) : '' ?>
                · <span class="badge badge-<?= e($r['order_channel']) ?>"><?= e(ORDER_CHANNELS[$r['order_channel']]) ?></span></span></div>
            <ul><?php foreach (explode('||', (string) $r['item_list']) as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul>
            <div class="ot-foot">
                <strong><?= e(rupiah($r['total_amount'])) ?></strong>
                <div class="actions">
                    <?php if ($r['payment_status'] !== 'paid'): ?><a class="btn btn-success btn-sm" href="<?= e(url('cashier/payments/confirm.php?id=' . $r['id'])) ?>">Bayar</a><?php endif; ?>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('cashier/orders/show.php?id=' . $r['id'])) ?>">Proses</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif;
$board = ob_get_clean();

// Permintaan auto-refresh: kembalikan papan saja
if (isset($_GET['partial'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo $board;
    exit;
}

$pageTitle = 'Pesanan Masuk';
require __DIR__ . '/../../includes/cashier-header.php';
?>
<p class="refresh-note">Diperbarui otomatis tiap 15 detik · terakhir <span id="lastRefresh"><?= date('H:i:s') ?></span></p>
<div id="board"><?= $board ?></div>
<script>
setInterval(function () {
    fetch('<?= e(url('cashier/orders/index.php?partial=1')) ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
        .then(function (html) {
            document.getElementById('board').innerHTML = html;
            document.getElementById('lastRefresh').textContent = new Date().toLocaleTimeString('id-ID');
        }).catch(function () {});
}, 15000);
</script>
<?php require __DIR__ . '/../../includes/cashier-footer.php'; ?>

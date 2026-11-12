<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['customer']);

$order = find_order(db(), 'code', (string) ($_GET['code'] ?? ''));
if (!$order || (int) $order['customer_user_id'] !== (int) $user['id']) {
    abort(404); // bukan milik pelanggan ini
}

$steps = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed'];
$cancelled = $order['order_status'] === 'cancelled';
$keys = array_keys($steps);
$curIdx = $cancelled ? -1 : array_search($order['order_status'], $keys, true);
$progress = $curIdx > 0 ? ($curIdx / (count($keys) - 1)) * 88 : 0; // lebar garis progres (%)

$pageTitle = 'Pesanan ' . $order['order_code'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="breadcrumb mt-4"><a href="<?= e(url('customer/orders.php')) ?>">Pesanan Saya</a> / <?= e($order['order_code']) ?></div>
    <section class="panel card-block" style="padding:clamp(22px,4vw,40px)">
        <div class="order-head">
            <div>
                <div class="order-code"><?= e($order['order_code']) ?></div>
                <small class="text-muted"><?= e(tanggal($order['ordered_at'])) ?></small>
            </div>
            <div><?= status_badge($order['order_status']) ?> <?= payment_badge($order['payment_status']) ?></div>
        </div>

        <?php if ($cancelled): ?>
            <div class="alert alert-error mt-3">Pesanan dibatalkan<?= $order['cancel_reason'] ? ': ' . e($order['cancel_reason']) : '.' ?></div>
        <?php else: ?>
            <div class="timeline">
                <div class="progress" style="width:<?= round($progress, 1) ?>%"></div>
                <?php foreach ($steps as $k => $label): $i = array_search($k, $keys, true); ?>
                    <div class="step <?= $i <= $curIdx ? 'done' : '' ?> <?= $i === $curIdx ? 'current' : '' ?>">
                        <div class="dot"><?= $i < $curIdx || $curIdx === count($keys) - 1 ? icon('check') : $i + 1 ?></div>
                        <span><?= e($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="info-list">
            <div><small>Jenis</small><strong><?= e(ORDER_TYPES[$order['order_type']]) ?><?= $order['table_number'] ? ' · Meja ' . e($order['table_number']) : '' ?></strong></div>
            <div><small>Metode bayar</small><strong><?= e(PAYMENT_METHODS[$order['payment_method']] ?? '-') ?></strong></div>
            <div><small>Invoice</small><strong><?= e($order['invoice_number'] ?? '-') ?></strong></div>
            <?php if ($order['paid_at']): ?><div><small>Dibayar</small><strong><?= e(tanggal($order['paid_at'])) ?></strong></div><?php endif; ?>
        </div>

        <div class="table-wrap mt-3">
            <table class="table">
                <thead><tr><th>Item</th><th>Ukuran</th><th class="num">Harga</th><th class="num">Qty</th><th class="num">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($order['items'] as $it): ?>
                    <tr><td><?= e($it['product_name']) ?><?= $it['item_notes'] ? '<br><small class="text-muted">' . e($it['item_notes']) . '</small>' : '' ?></td>
                        <td><?= e(size_label($it['size'])) ?></td><td class="num"><?= e(rupiah($it['unit_price'])) ?></td>
                        <td class="num"><?= (int) $it['quantity'] ?></td><td class="num"><?= e(rupiah($it['line_total'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="max-width:320px;margin-left:auto">
            <div class="summary-row"><span>Subtotal</span><span><?= e(rupiah($order['subtotal'])) ?></span></div>
            <?php if ((float) $order['discount_amount'] > 0): ?><div class="summary-row"><span>Diskon</span><span>-<?= e(rupiah($order['discount_amount'])) ?></span></div><?php endif; ?>
            <div class="summary-row total"><span>Total</span><span><?= e(rupiah($order['total_amount'])) ?></span></div>
        </div>
        <?php if ($order['notes']): ?><p class="mt-3"><strong>Catatan:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
    </section>
</div>
<?php if (!$cancelled && $order['order_status'] !== 'completed'): ?>
<script>setTimeout(function () { location.reload(); }, 30000); /* segarkan status berkala */</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>

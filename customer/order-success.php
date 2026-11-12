<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['customer']);

$order = find_order(db(), 'code', (string) ($_GET['code'] ?? ''));
// Cek kepemilikan di server (cegah IDOR)
if (!$order || (int) $order['customer_user_id'] !== (int) $user['id']) {
    abort(404);
}

$pageTitle = 'Pesanan Berhasil';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="panel card-block text-center mt-4" style="padding:clamp(28px,5vw,56px)">
        <div class="success-icon"><?= icon('check') ?></div>
        <h1>Pesanan <em>Berhasil</em> Dibuat!</h1>
        <p>Terima kasih, <?= e(explode(' ', $user['name'])[0]) ?>. Tunjukkan kode ini kepada kasir.</p>
        <div class="order-code mt-3"><?= e($order['order_code']) ?></div>
        <div class="info-list" style="text-align:left;max-width:720px;margin:24px auto">
            <div><small>Jenis</small><strong><?= e(ORDER_TYPES[$order['order_type']]) ?><?= $order['table_number'] ? ' · Meja ' . e($order['table_number']) : '' ?></strong></div>
            <div><small>Metode bayar</small><strong><?= e(PAYMENT_METHODS[$order['payment_method']] ?? '-') ?></strong></div>
            <div><small>Status bayar</small><strong><?= payment_badge($order['payment_status']) ?></strong></div>
            <div><small>Total</small><strong><?= e(rupiah($order['total_amount'])) ?></strong></div>
        </div>
        <div class="table-wrap" style="max-width:720px;margin:0 auto">
            <table class="table">
                <thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($order['items'] as $it): ?>
                    <tr><td><?= e($it['product_name']) ?> <?= $it['size'] ? '<small class="text-muted">' . e(size_label($it['size'])) . '</small>' : '' ?></td>
                        <td class="num"><?= (int) $it['quantity'] ?></td><td class="num"><?= e(rupiah($it['line_total'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="sim-note mt-3">
            <?php if ($order['payment_method'] === 'cash'): ?>Silakan bayar tunai di kasir. Pesanan diproses setelah dikonfirmasi.
            <?php else: ?>Simulasi: pembayaran <?= e(PAYMENT_METHODS[$order['payment_method']]) ?> tidak terhubung ke payment gateway. Kasir akan mengonfirmasi pembayaran.<?php endif; ?>
        </p>
        <div class="hero-actions mt-3" style="justify-content:center">
            <a class="btn btn-primary" href="<?= e(url('customer/order-detail.php?code=' . urlencode($order['order_code']))) ?>">Pantau Pesanan</a>
            <a class="btn btn-secondary" href="<?= e(url('customer/menu.php')) ?>">Pesan Lagi</a>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

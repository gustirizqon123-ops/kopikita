<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['cashier', 'admin']);

$order = find_order(db(), 'id', (int) ($_GET['id'] ?? 0));
if (!$order) {
    abort(404);
}
$w = ($_GET['w'] ?? '80') === '58' ? '58' : '80';
$paid = $order['payment_status'] === 'paid';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk <?= e($order['order_code']) ?></title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
    <style>@media print { @page { size: <?= $w ?>mm auto; margin: 2mm; } }</style>
</head>
<body class="receipt-page">
<div class="print-toolbar">
    <strong>Struk <?= e($order['order_code']) ?></strong>
    <span class="spacer"></span>
    <a class="ghost" href="?id=<?= (int) $order['id'] ?>&w=58">58 mm</a>
    <a class="ghost" href="?id=<?= (int) $order['id'] ?>&w=80">80 mm</a>
    <button type="button" onclick="window.print()">Cetak</button>
    <a class="ghost" href="javascript:history.back()">Kembali</a>
</div>
<div class="receipt w<?= $w ?>">
    <div class="c shop">KopiKita</div>
    <div class="c"><?= e(SHOP_ADDRESS) ?></div>
    <div class="c"><?= e(SHOP_PHONE) ?></div>
    <div class="sep"></div>
    <div class="row"><span>No. Pesanan</span><span><?= e($order['order_code']) ?></span></div>
    <div class="row"><span>Invoice</span><span><?= e($order['invoice_number'] ?? '-') ?></span></div>
    <div class="row"><span>Tanggal</span><span><?= e(tanggal($order['ordered_at'])) ?></span></div>
    <div class="row"><span>Pelanggan</span><span><?= e($order['customer_name']) ?></span></div>
    <div class="row"><span>Jenis</span><span><?= e(ORDER_TYPES[$order['order_type']]) ?><?= $order['table_number'] ? ' / Meja ' . e($order['table_number']) : '' ?></span></div>
    <div class="row"><span>Kasir</span><span><?= e($order['cashier_name'] ?? $order['processed_name'] ?? '-') ?></span></div>
    <div class="sep"></div>
    <?php foreach ($order['items'] as $it): ?>
        <div class="item-name"><?= e($it['product_name']) ?><?= $it['size'] ? ' (' . e(size_label($it['size'])) . ')' : '' ?></div>
        <div class="row"><span><?= (int) $it['quantity'] ?> x <?= e(rupiah($it['unit_price'])) ?></span><span><?= e(rupiah($it['line_total'])) ?></span></div>
        <?php if ($it['item_notes']): ?><div class="note">* <?= e($it['item_notes']) ?></div><?php endif; ?>
    <?php endforeach; ?>
    <div class="sep"></div>
    <div class="row"><span>Subtotal</span><span><?= e(rupiah($order['subtotal'])) ?></span></div>
    <?php if ((float) $order['discount_amount'] > 0): ?><div class="row"><span>Diskon</span><span>-<?= e(rupiah($order['discount_amount'])) ?></span></div><?php endif; ?>
    <div class="row big"><span>TOTAL</span><span><?= e(rupiah($order['total_amount'])) ?></span></div>
    <div class="sep"></div>
    <div class="row"><span>Metode</span><span><?= e(PAYMENT_METHODS[$order['payment_method']] ?? '-') ?></span></div>
    <?php if ($paid): ?>
        <div class="row"><span>Dibayar</span><span><?= e(rupiah($order['amount_paid'])) ?></span></div>
        <div class="row"><span>Kembalian</span><span><?= e(rupiah($order['change_amount'])) ?></span></div>
        <?php if ($order['reference_number']): ?><div class="row"><span>Ref</span><span><?= e($order['reference_number']) ?></span></div><?php endif; ?>
    <?php endif; ?>
    <div class="c b mt-2"><?= $paid ? '*** LUNAS ***' : '*** BELUM BAYAR ***' ?></div>
    <div class="sep"></div>
    <div class="c">Terima kasih sudah mampir!</div>
    <div class="c note"><?= e(APP_SLOGAN) ?></div>
</div>
</body>
</html>

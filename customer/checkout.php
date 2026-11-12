<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['customer']);
$pdo = db();

$st = $pdo->prepare('SELECT * FROM customers WHERE user_id = ?');
$st->execute([$user['id']]);
$customer = $st->fetch();
if (!$customer) {
    abort(403);
}

$raw = array_values(cart_get());
if (!$raw) {
    flash('info', 'Keranjang masih kosong.');
    redirect('customer/cart.php');
}

$errors = [];
$form = ['order_type' => 'dine_in', 'table_number' => '', 'notes' => '', 'payment_method' => 'cash'];

if (is_post()) {
    verify_csrf();
    $form = [
        'order_type'     => (string) ($_POST['order_type'] ?? ''),
        'table_number'   => trim((string) ($_POST['table_number'] ?? '')),
        'notes'          => trim((string) ($_POST['notes'] ?? '')),
        'payment_method' => (string) ($_POST['payment_method'] ?? ''),
    ];
    // Semua harga/stok dihitung ulang di server di dalam transaksi
    $result = create_order($pdo, (int) $customer['id'], $raw, [
        'channel' => 'online', 'order_type' => $form['order_type'], 'table_number' => $form['table_number'],
        'notes' => $form['notes'], 'payment_method' => $form['payment_method'], 'processed_by' => null,
    ]);
    if ($result['ok']) {
        unset($_SESSION['cart']);
        redirect('customer/order-success.php?code=' . urlencode($result['order_code']));
    }
    $errors = $result['errors'];
}

$res = resolve_items($pdo, $raw);
$pageTitle = 'Checkout';
$extraScripts = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('customer/cart.php')) ?>">Keranjang</a> / Checkout</div>
        <h1>Check<em>out</em></h1>
    </section>

    <?php foreach ($errors as $er): ?><div class="alert alert-error mt-3"><?= e($er) ?></div><?php endforeach; ?>

    <form method="post" class="cart-layout" data-validate>
        <?= csrf_field() ?>
        <section class="panel card-block" style="padding:26px">
            <div class="form-group">
                <label class="form-label">Jenis pesanan</label>
                <div class="radio-cards">
                    <?php foreach (ORDER_TYPES as $k => $l): ?>
                        <label class="radio-card"><input type="radio" name="order_type" value="<?= e($k) ?>" <?= $form['order_type'] === $k ? 'checked' : '' ?>><span><?= e($l) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group" id="tableGroup">
                <label class="form-label" for="table_number">Nomor meja (untuk dine-in)</label>
                <input class="form-control" id="table_number" name="table_number" maxlength="10" value="<?= e($form['table_number']) ?>" placeholder="Contoh: 7">
            </div>
            <div class="form-group">
                <label class="form-label" for="notes">Catatan pesanan</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500" placeholder="Opsional"><?= e($form['notes']) ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Metode pembayaran</label>
                <div class="radio-cards">
                    <?php foreach (PAYMENT_METHODS as $k => $l): ?>
                        <label class="radio-card"><input type="radio" name="payment_method" value="<?= e($k) ?>" <?= $form['payment_method'] === $k ? 'checked' : '' ?>><span><?= e($l) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <p class="sim-note mt-2">Pembayaran QRIS dan transfer hanya simulasi. Pembayaran dikonfirmasi oleh kasir setelah pesanan dibuat.</p>
            </div>
        </section>

        <aside class="panel summary-card">
            <h3>Ringkasan Pesanan</h3>
            <div class="checkout-items">
                <?php foreach ($res['items'] as $it): ?>
                    <div class="ci">
                        <div><?= (int) $it['qty'] ?>× <?= e($it['name']) ?>
                            <small><?= e(size_label($it['size'])) ?><?= $it['notes'] ? ' · ' . e($it['notes']) : '' ?></small></div>
                        <span><?= e(rupiah($it['line_total'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="summary-row"><span>Subtotal</span><span><?= e(rupiah($res['subtotal'])) ?></span></div>
            <div class="summary-row"><span>Diskon</span><span>Rp0</span></div>
            <div class="summary-row total"><span>Total</span><span><?= e(rupiah($res['subtotal'])) ?></span></div>
            <button class="btn btn-primary btn-block btn-lg mt-3" type="submit">Buat Pesanan</button>
            <a class="btn btn-ghost btn-block btn-sm mt-2" href="<?= e(url('customer/cart.php')) ?>">Kembali ke Keranjang</a>
        </aside>
    </form>
</div>
<script>
(function () {
    var radios = document.querySelectorAll('input[name=order_type]'), g = document.getElementById('tableGroup');
    function sync() { var v = document.querySelector('input[name=order_type]:checked'); g.style.display = v && v.value === 'dine_in' ? '' : 'none'; }
    radios.forEach(function (r) { r.addEventListener('change', sync); }); sync();
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

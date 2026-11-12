<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_role(['cashier', 'admin']);
$pdo = db();

$order = find_order($pdo, 'id', (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
if (!$order) {
    abort(404);
}
$showUrl = 'cashier/orders/show.php?id=' . $order['id'];
if ($order['payment_status'] === 'paid' || $order['order_status'] === 'cancelled') {
    flash('info', $order['order_status'] === 'cancelled' ? 'Pesanan sudah dibatalkan.' : 'Pesanan ini sudah lunas.');
    redirect($showUrl);
}

$errors = [];
$method = $order['payment_method'] ?: 'cash';
if (is_post()) {
    verify_csrf();
    $method = (string) ($_POST['payment_method'] ?? '');
    // Nominal & total dihitung ulang di server (total dari database)
    $res = confirm_payment($pdo, (int) $order['id'], $method, money_input($_POST['amount_paid'] ?? 0), $_POST['reference'] ?? '', (int) $user['id']);
    if ($res['ok']) {
        flash('success', 'Pembayaran dikonfirmasi.' . ($res['change'] > 0 ? ' Kembalian ' . rupiah($res['change']) . '.' : ''));
        redirect($showUrl);
    }
    $errors[] = $res['error'];
}

$total = (float) $order['total_amount'];
$pageTitle = 'Konfirmasi Pembayaran';
require __DIR__ . '/../../includes/cashier-header.php';
?>
<div class="card" style="max-width:620px">
    <div class="card-header"><h2><?= e($order['order_code']) ?> · <?= e($order['customer_name']) ?></h2></div>
    <div class="card-body">
        <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
        <div class="totals" style="border:0;padding:0 0 14px"><div class="row big" style="border:0;margin:0;padding:0"><span>Total tagihan</span><span><?= e(rupiah($total)) ?></span></div></div>
        <form method="post" id="payForm">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
            <div class="form-group">
                <label class="form-label">Metode pembayaran</label>
                <div class="radio-pills">
                    <?php foreach (PAYMENT_METHODS as $k => $l): ?>
                        <label><input type="radio" name="payment_method" value="<?= e($k) ?>" <?= $method === $k ? 'checked' : '' ?>><span><?= e($l) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="cashBox">
                <div class="form-group">
                    <label class="form-label" for="amount_paid">Uang diterima (Rp)</label>
                    <input class="form-control" id="amount_paid" name="amount_paid" inputmode="numeric" autocomplete="off" value="<?= e($_POST['amount_paid'] ?? '') ?>">
                    <div class="quick-cash" id="quickCash"></div>
                </div>
                <div class="change-box minus" id="changeBox"><span>Kembalian</span><span id="changeVal">Kurang <?= e(rupiah($total)) ?></span></div>
            </div>
            <div id="refBox" class="form-group" style="display:none">
                <label class="form-label" for="reference">Nomor referensi (simulasi)</label>
                <input class="form-control" id="reference" name="reference" maxlength="100" value="<?= e($_POST['reference'] ?? '') ?>" placeholder="Contoh: QRIS-123456">
                <div class="sim-note mt-2">Simulasi: tidak terhubung payment gateway. Kasir memastikan dana masuk secara manual.</div>
            </div>
            <div class="form-actions">
                <button class="btn btn-success btn-lg" type="submit"><?= icon('check') ?> Tandai Lunas</button>
                <a class="btn btn-outline" href="<?= e(url($showUrl)) ?>">Batal</a>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var total = <?= json_encode($total) ?>;
    var fmt = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };
    var inp = document.getElementById('amount_paid'), box = document.getElementById('changeBox'), val = document.getElementById('changeVal');
    function calc() {
        var paid = parseInt((inp.value || '').replace(/\D/g, ''), 10) || 0, diff = paid - total;
        box.classList.toggle('minus', diff < 0);
        val.textContent = diff < 0 ? 'Kurang ' + fmt(-diff) : fmt(diff);
    }
    inp.addEventListener('input', calc);
    var qc = document.getElementById('quickCash');
    var amounts = [total]; [20000, 50000, 100000, 200000].forEach(function (a) { if (a > total) amounts.push(a); });
    amounts.forEach(function (a, i) {
        var b = document.createElement('button'); b.type = 'button'; b.textContent = i === 0 ? 'Uang pas' : fmt(a);
        b.addEventListener('click', function () { inp.value = a; calc(); }); qc.appendChild(b);
    });
    function sync() {
        var m = document.querySelector('input[name=payment_method]:checked').value;
        document.getElementById('cashBox').style.display = m === 'cash' ? '' : 'none';
        document.getElementById('refBox').style.display = m === 'cash' ? 'none' : '';
    }
    document.querySelectorAll('input[name=payment_method]').forEach(function (r) { r.addEventListener('change', sync); });
    sync(); calc();
})();
</script>
<?php require __DIR__ . '/../../includes/cashier-footer.php'; ?>

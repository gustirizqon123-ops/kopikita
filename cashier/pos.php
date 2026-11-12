<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['cashier', 'admin']);
$pdo = db();

// Simpan transaksi POS (JSON). Harga, stok & total dihitung ulang di server.
if (is_post()) {
    verify_csrf();
    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        json_response(['ok' => false, 'message' => 'Data tidak valid.'], 400);
    }
    $customerId = (int) ($body['customer_id'] ?? 0);
    $st = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
    $st->execute([$customerId]);
    if (!$st->fetch()) {
        $customerId = (int) $pdo->query("SELECT id FROM customers WHERE name = 'Pelanggan Umum' ORDER BY id LIMIT 1")->fetchColumn();
    }
    $items = [];
    foreach ((array) ($body['items'] ?? []) as $it) {
        $items[] = [
            'product_id' => (int) ($it['product_id'] ?? 0), 'size' => $it['size'] ?? null,
            'qty' => (int) ($it['qty'] ?? 0), 'notes' => (string) ($it['notes'] ?? ''),
        ];
    }
    $res = create_order($pdo, $customerId, $items, [
        'channel' => 'cashier', 'order_type' => (string) ($body['order_type'] ?? ''), 'table_number' => (string) ($body['table_number'] ?? ''),
        'notes' => (string) ($body['notes'] ?? ''), 'payment_method' => (string) ($body['payment_method'] ?? ''),
        'processed_by' => (int) $user['id'], 'deduct_stock' => true,
        'pay' => ['amount_paid' => money_input($body['amount_paid'] ?? 0), 'reference' => (string) ($body['reference'] ?? ''), 'cashier_id' => (int) $user['id']],
    ]);
    if (!$res['ok']) {
        json_response(['ok' => false, 'message' => implode(' ', $res['errors'])], 422);
    }
    json_response(['ok' => true, 'order_code' => $res['order_code'],
        'receipt' => url('cashier/receipt.php?id=' . $res['order_id']), 'detail' => url('cashier/orders/show.php?id=' . $res['order_id'])]);
}

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();
$products = $pdo->query('SELECT p.id, p.category_id, p.name, p.price, p.stock, p.image, p.has_size_option
        FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_available = 1 AND c.is_active = 1 ORDER BY p.name')->fetchAll();
foreach ($products as &$p) {
    $p['img'] = product_image_url($p['image']);
    $p['price'] = (float) $p['price'];
    $p['stock'] = (int) $p['stock'];
    unset($p['image']);
}
unset($p);
$customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY (name = 'Pelanggan Umum') DESC, name LIMIT 300")->fetchAll();

$pageTitle = 'Transaksi Baru (POS)';
$extraScripts = ['pos.js'];
$inlineScript = 'window.POS = ' . json_encode([
    'products' => $products, 'sizePrices' => SIZE_PRICES, 'sizeLabels' => SIZE_LABELS, 'endpoint' => url('cashier/pos.php'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ';';
// $inlineScript dicetak setelah extraScripts, jadi pasang data lebih awal lewat extraHead
$extraHead = '<script>' . $inlineScript . '</script>';
$inlineScript = '';
require __DIR__ . '/../includes/cashier-header.php';
?>
<div class="pos">
    <section>
        <div class="pos-cats" id="posCats">
            <button type="button" class="active" data-cat="all">Semua</button>
            <?php foreach ($cats as $c): ?><button type="button" data-cat="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></button><?php endforeach; ?>
        </div>
        <div class="form-group"><input type="search" class="form-control" id="posSearch" placeholder="Cari produk…" aria-label="Cari produk"></div>
        <div class="pos-products" id="posProducts"></div>
    </section>

    <aside class="card pos-cart">
        <div class="card-header"><h2>Pesanan Baru</h2><button type="button" class="btn btn-outline btn-sm" id="posClear">Kosongkan</button></div>
        <div class="pos-lines" id="posLines"></div>
        <div class="totals"><div class="row big" style="border:0;margin:0;padding:0"><span>Total</span><span id="posTotal">Rp0</span></div></div>
        <div class="card-body" style="border-top:1px solid var(--border)">
            <div class="form-group">
                <label class="form-label" for="posCustomer">Pelanggan</label>
                <select class="form-control" id="posCustomer">
                    <?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?><?= $c['phone'] ? ' · ' . e($c['phone']) : '' ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <div class="radio-pills">
                    <?php foreach (ORDER_TYPES as $k => $l): ?><label><input type="radio" name="pos_type" value="<?= e($k) ?>" <?= $k === 'takeaway' ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach; ?>
                </div>
            </div>
            <div class="form-group" id="posTableGroup" style="display:none"><input class="form-control" id="posTable" maxlength="10" placeholder="Nomor meja"></div>
            <div class="form-group">
                <div class="radio-pills">
                    <?php foreach (PAYMENT_METHODS as $k => $l): ?><label><input type="radio" name="pos_method" value="<?= e($k) ?>" <?= $k === 'cash' ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach; ?>
                </div>
            </div>
            <div id="posCashBox">
                <input class="form-control" id="posPaid" inputmode="numeric" placeholder="Uang diterima (Rp)" autocomplete="off">
                <div class="quick-cash" id="posQuick"></div>
                <div class="change-box minus" id="posChange"><span>Kembalian</span><span id="posChangeVal">-</span></div>
            </div>
            <div id="posRefBox" style="display:none">
                <input class="form-control" id="posRef" maxlength="100" placeholder="Nomor referensi (simulasi)">
                <div class="sim-note mt-2">Simulasi, tanpa payment gateway.</div>
            </div>
            <div class="form-group mt-2"><input class="form-control" id="posNotes" maxlength="500" placeholder="Catatan pesanan (opsional)"></div>
            <div id="posError" class="alert alert-error" style="display:none"></div>
            <button type="button" class="btn btn-success btn-block btn-lg" id="posPay"><?= icon('check') ?> Bayar &amp; Simpan</button>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/../includes/cashier-footer.php'; ?>

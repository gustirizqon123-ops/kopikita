<?php
/**
 * Detail pesanan + ubah status (dipakai cashier/orders/show.php dan admin/orders/show.php).
 * $panel: 'cashier' | 'admin'
 */
function render_order_detail(string $panel): void
{
    $user = current_user();
    $pdo = db();
    $order = find_order($pdo, 'id', (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
    if (!$order) {
        abort(404);
    }
    $self = $panel === 'admin' ? 'admin/orders/show.php' : 'cashier/orders/show.php';
    $listUrl = $panel === 'admin' ? 'admin/orders/index.php' : 'cashier/orders/index.php';

    if (is_post()) {
        verify_csrf();
        $new = (string) ($_POST['status'] ?? '');
        $res = change_order_status($pdo, (int) $order['id'], $new, (int) $user['id'], (string) ($_POST['reason'] ?? ''));
        flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Status pesanan diubah menjadi ' . ORDER_STATUSES[$new] . '.' : $res['error']);
        redirect($self . '?id=' . $order['id']);
    }

    $status = $order['order_status'];
    $cancelled = $status === 'cancelled';
    $nexts = ORDER_TRANSITIONS[$status] ?? [];
    $mainNext = array_values(array_diff($nexts, ['cancelled']))[0] ?? null;
    $canCancel = in_array('cancelled', $nexts, true);
    $paid = $order['payment_status'] === 'paid';
    $flow = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
    $curIdx = array_search($status, $flow, true);

    $pageTitle = 'Pesanan ' . $order['order_code'];
    if ($panel === 'admin') {
        require APP_ROOT . '/includes/admin-header.php';
    } else {
        require APP_ROOT . '/includes/cashier-header.php';
    } ?>
    <div class="status-steps">
        <?php if ($cancelled): ?><div class="st cancel">Dibatalkan</div>
        <?php else: foreach ($flow as $i => $f): ?>
            <div class="st <?= $curIdx !== false && $i <= $curIdx ? 'done' : '' ?>"><?= e(ORDER_STATUSES[$f]) ?></div>
        <?php endforeach; endif; ?>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2><?= e($order['order_code']) ?></h2><div><?= status_badge($status) ?> <?= payment_badge($order['payment_status']) ?></div></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Item</th><th>Ukuran</th><th class="text-right">Harga</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($order['items'] as $it): ?>
                        <tr><td><strong><?= e($it['product_name']) ?></strong><?= $it['item_notes'] ? '<div class="text-muted">' . e($it['item_notes']) . '</div>' : '' ?></td>
                            <td><?= e(size_label($it['size'])) ?></td><td class="text-right nowrap"><?= e(rupiah($it['unit_price'])) ?></td>
                            <td class="text-right"><?= (int) $it['quantity'] ?></td><td class="text-right nowrap"><?= e(rupiah($it['line_total'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="totals">
                <div class="row"><span>Subtotal</span><span><?= e(rupiah($order['subtotal'])) ?></span></div>
                <?php if ((float) $order['discount_amount'] > 0): ?><div class="row"><span>Diskon</span><span>-<?= e(rupiah($order['discount_amount'])) ?></span></div><?php endif; ?>
                <div class="row big"><span>Total</span><span><?= e(rupiah($order['total_amount'])) ?></span></div>
            </div>
        </div>

        <div>
            <div class="card mb-3">
                <div class="card-header"><h3>Informasi</h3></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Pelanggan</dt><dd><?= e($order['customer_name']) ?><?= $order['customer_phone'] ? ' · ' . e($order['customer_phone']) : '' ?></dd>
                        <dt>Channel</dt><dd><?= e(ORDER_CHANNELS[$order['order_channel']]) ?></dd>
                        <dt>Jenis</dt><dd><?= e(ORDER_TYPES[$order['order_type']]) ?><?= $order['table_number'] ? ' · Meja ' . e($order['table_number']) : '' ?></dd>
                        <dt>Waktu pesan</dt><dd><?= e(tanggal($order['ordered_at'])) ?></dd>
                        <?php if ($order['completed_at']): ?><dt>Selesai</dt><dd><?= e(tanggal($order['completed_at'])) ?></dd><?php endif; ?>
                        <dt>Diproses oleh</dt><dd><?= e($order['processed_name'] ?? '-') ?></dd>
                        <dt>Stok</dt><dd><?= $order['stock_deducted'] ? 'Sudah dikurangi' : 'Belum dikurangi' ?></dd>
                        <?php if ($order['notes']): ?><dt>Catatan</dt><dd><?= e($order['notes']) ?></dd><?php endif; ?>
                        <?php if ($cancelled): ?><dt>Alasan batal</dt><dd class="text-danger"><?= e($order['cancel_reason']) ?></dd><?php endif; ?>
                    </dl>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3>Pembayaran</h3></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Invoice</dt><dd><?= e($order['invoice_number'] ?? '-') ?></dd>
                        <dt>Metode</dt><dd><?= e(PAYMENT_METHODS[$order['payment_method']] ?? '-') ?></dd>
                        <dt>Status</dt><dd><?= payment_badge($order['payment_status']) ?></dd>
                        <?php if ($paid): ?>
                            <dt>Dibayar</dt><dd><?= e(rupiah($order['amount_paid'])) ?></dd>
                            <dt>Kembalian</dt><dd><?= e(rupiah($order['change_amount'])) ?></dd>
                            <?php if ($order['reference_number']): ?><dt>Referensi</dt><dd><?= e($order['reference_number']) ?></dd><?php endif; ?>
                            <dt>Waktu bayar</dt><dd><?= e(tanggal($order['paid_at'])) ?></dd>
                            <dt>Kasir</dt><dd><?= e($order['cashier_name'] ?? '-') ?></dd>
                        <?php endif; ?>
                    </dl>
                    <div class="actions mt-2">
                        <?php if (!$paid && !$cancelled): ?>
                            <a class="btn btn-success" href="<?= e(url('cashier/payments/confirm.php?id=' . $order['id'])) ?>"><?= icon('cash') ?> Konfirmasi Pembayaran</a>
                        <?php endif; ?>
                        <a class="btn btn-outline" target="_blank" href="<?= e(url('cashier/receipt.php?id=' . $order['id'])) ?>"><?= icon('print') ?> Cetak Struk</a>
                    </div>
                </div>
            </div>

            <?php if ($nexts): ?>
                <div class="card">
                    <div class="card-header"><h3>Ubah Status</h3></div>
                    <div class="card-body">
                        <?php if ($mainNext === 'completed' && !$paid): ?>
                            <div class="alert alert-warning">Pesanan harus lunas sebelum diselesaikan.</div>
                        <?php endif; ?>
                        <div class="actions">
                            <?php if ($mainNext): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="status" value="<?= e($mainNext) ?>">
                                    <button class="btn btn-primary" type="submit">Tandai <?= e(ORDER_STATUSES[$mainNext]) ?> →</button></form>
                            <?php endif; ?>
                            <?php if ($canCancel): ?>
                                <button class="btn btn-danger" type="button" onclick="document.getElementById('cancelModal').classList.add('open')">Batalkan</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($canCancel): ?>
                <div class="modal" id="cancelModal">
                    <div class="modal-box">
                        <h3>Batalkan pesanan?</h3>
                        <p class="text-muted">Stok yang sudah dikurangi akan dikembalikan.</p>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="status" value="cancelled">
                            <div class="form-group"><label class="form-label" for="reason">Alasan pembatalan *</label>
                                <input class="form-control" id="reason" name="reason" maxlength="255" required></div>
                            <div class="form-actions">
                                <button class="btn btn-danger" type="submit">Ya, batalkan</button>
                                <button class="btn btn-outline" type="button" onclick="document.getElementById('cancelModal').classList.remove('open')">Tutup</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <p class="mt-3"><a class="btn btn-outline btn-sm" href="<?= e(url($listUrl)) ?>">← Kembali ke daftar</a></p>
    <?php
    require APP_ROOT . '/includes/' . ($panel === 'admin' ? 'admin-footer.php' : 'cashier-footer.php');
}

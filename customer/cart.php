<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['customer']);

$res = resolve_items(db(), array_values(cart_get()));
$items = $res['items'];
$cartEmpty = !cart_get();

$pageTitle = 'Keranjang';
$extraScripts = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('customer/index.php')) ?>">Home</a> / Keranjang</div>
        <h1>Keranjang <em>Belanja</em></h1>
    </section>

    <?php if ($cartEmpty): ?>
        <div class="empty-state">
            <div class="big">Keranjang masih kosong</div>
            <p>Yuk pilih kopi atau camilan favoritmu.</p>
            <a class="btn btn-primary" href="<?= e(url('customer/menu.php')) ?>">Lihat Menu</a>
        </div>
    <?php else: ?>
        <?php foreach ($res['errors'] as $er): ?><div class="alert alert-warning"><?= e($er) ?></div><?php endforeach; ?>
        <div class="cart-layout">
            <section class="panel">
                <?php foreach (array_values(cart_get()) as $raw):
                    $it = null;
                    foreach ($items as $x) { if ($x['key'] === $raw['key']) { $it = $x; break; } }
                    if (!$it): // item tidak valid (nonaktif/stok kurang): tampilkan opsi hapus saja
                        $st = db()->prepare('SELECT name, image FROM products WHERE id = ?');
                        $st->execute([$raw['product_id']]);
                        $pp = $st->fetch() ?: ['name' => 'Produk', 'image' => null]; ?>
                        <div class="cart-item">
                            <img src="<?= e(product_image_url($pp['image'])) ?>" alt="">
                            <div><h4><?= e($pp['name']) ?></h4><div class="form-error">Tidak dapat dipesan saat ini.</div></div>
                            <form method="post" action="<?= e(url('customer/cart-action.php')) ?>"><?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove"><input type="hidden" name="key" value="<?= e($raw['key']) ?>">
                                <button class="link-remove" type="submit">Hapus</button></form>
                        </div>
                        <?php continue; endif; $p = $it['product']; ?>
                    <div class="cart-item">
                        <img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($it['name']) ?>">
                        <div>
                            <h4><a href="<?= e(product_url($p, true)) ?>"><?= e($it['name']) ?></a></h4>
                            <small class="text-muted"><?= e(rupiah($it['unit_price'])) ?> / item</small>
                            <form method="post" action="<?= e(url('customer/cart-action.php')) ?>" class="opts" data-autosubmit>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update"><input type="hidden" name="key" value="<?= e($it['key']) ?>">
                                <?php if ($p['has_size_option']): ?>
                                    <select name="size" class="form-control" aria-label="Ukuran">
                                        <?php foreach (SIZE_LABELS as $k => $l): ?>
                                            <option value="<?= e($k) ?>" <?= $it['size'] === $k ? 'selected' : '' ?>><?= e($l) ?><?= SIZE_PRICES[$k] ? ' (+' . e(rupiah(SIZE_PRICES[$k])) . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <div class="qty-control sm">
                                    <button type="button" data-qty-minus aria-label="Kurangi">−</button>
                                    <input type="number" name="qty" value="<?= (int) $it['qty'] ?>" min="1" max="<?= max(1, (int) $p['stock']) ?>" data-max="<?= max(1, min(99, (int) $p['stock'])) ?>" aria-label="Jumlah">
                                    <button type="button" data-qty-plus aria-label="Tambah">+</button>
                                </div>
                                <input type="text" name="notes" class="form-control" maxlength="150" value="<?= e($it['notes']) ?>" placeholder="Catatan (opsional)" aria-label="Catatan">
                                <noscript><button class="btn btn-sm btn-secondary" type="submit">Perbarui</button></noscript>
                            </form>
                            <form method="post" action="<?= e(url('customer/cart-action.php')) ?>"><?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove"><input type="hidden" name="key" value="<?= e($it['key']) ?>">
                                <button class="link-remove" type="submit">Hapus</button></form>
                        </div>
                        <div class="line-total"><?= e(rupiah($it['line_total'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </section>

            <aside class="panel summary-card">
                <h3>Ringkasan</h3>
                <div class="summary-row"><span>Subtotal (<?= (int) cart_count() ?> item)</span><span><?= e(rupiah($res['subtotal'])) ?></span></div>
                <div class="summary-row"><span>Diskon</span><span>Rp0</span></div>
                <div class="summary-row total"><span>Total</span><span><?= e(rupiah($res['subtotal'])) ?></span></div>
                <?php if ($res['errors']): ?>
                    <button class="btn btn-primary btn-block mt-3" disabled>Perbaiki keranjang dulu</button>
                <?php else: ?>
                    <a class="btn btn-primary btn-block mt-3" href="<?= e(url('customer/checkout.php')) ?>">Lanjut ke Checkout</a>
                <?php endif; ?>
                <form method="post" action="<?= e(url('customer/cart-action.php')) ?>" class="mt-2" data-confirm="Kosongkan keranjang?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="clear">
                    <button class="btn btn-ghost btn-block btn-sm" type="submit">Kosongkan Keranjang</button>
                </form>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

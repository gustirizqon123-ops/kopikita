<?php
/** Komponen tampilan bersama (kartu produk, tampilan menu & detail produk). */

/** Link detail produk sesuai area (publik / pelanggan). */
function product_url(array $p, bool $customerArea = false): string
{
    return url(($customerArea ? 'customer/' : '') . 'product-detail.php?slug=' . rawurlencode($p['slug']));
}

/** Keterangan stok: [teks, kelas CSS]. */
function stock_info(array $p): array
{
    if (!(int) $p['is_available'] || (int) $p['stock'] <= 0) {
        return ['Stok habis', 'out'];
    }
    if ((int) $p['stock'] <= (int) $p['min_stock']) {
        return ['Sisa ' . (int) $p['stock'] . ' – hampir habis', 'low'];
    }
    return ['Tersedia', ''];
}

/** Kartu produk (grid / carousel). */
function render_product_card(array $p, bool $customerArea = false): string
{
    [$stockText, $stockClass] = stock_info($p);
    $out = $stockClass === 'out';
    $link = e(product_url($p, $customerArea));
    ob_start(); ?>
    <article class="product-card" data-cat="<?= e($p['category_slug'] ?? '') ?>">
        <a class="thumb" href="<?= $link ?>">
            <img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <span class="badges">
                <?php if ($p['is_best_seller']): ?><span class="badge badge-best">Best Seller</span><?php endif; ?>
                <?php if ($stockClass === 'out'): ?><span class="badge badge-out">Habis</span>
                <?php elseif ($stockClass === 'low'): ?><span class="badge badge-low">Terbatas</span><?php endif; ?>
            </span>
        </a>
        <div class="body">
            <h3><a href="<?= $link ?>"><?= e($p['name']) ?></a></h3>
            <p class="desc"><?= e($p['description']) ?></p>
            <div class="stock-note <?= e($stockClass) ?>"><?= e($stockText) ?></div>
            <div class="foot">
                <span class="price"><?= e(rupiah($p['price'])) ?></span>
                <button type="button" class="add-btn" data-quick-add="<?= (int) $p['id'] ?>" aria-label="Tambah <?= e($p['name']) ?> ke keranjang" <?= $out ? 'disabled' : '' ?>>+</button>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/** Halaman katalog menu (dipakai menu.php dan customer/menu.php). */
function render_menu_page(bool $customerArea): void
{
    $pdo = db();
    $q = trim((string) ($_GET['q'] ?? ''));
    $catSlug = (string) ($_GET['category'] ?? 'all');
    $cats = $pdo->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.is_available = 1 AND c.is_active = 1';
    $args = [];
    if ($catSlug !== 'all') {
        $sql .= ' AND c.slug = ?';
        $args[] = $catSlug;
    }
    if ($q !== '') {
        $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
        $like = '%' . addcslashes($q, '%_\\') . '%';
        array_push($args, $like, $like);
    }
    $sql .= ' ORDER BY c.sort_order, p.is_best_seller DESC, p.name';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $products = $st->fetchAll();

    global $pageTitle, $pageDesc;
    $pageTitle = 'Menu';
    $pageDesc = 'Daftar menu KopiKita: kopi, non-kopi, makanan, dan signature drinks.';
    $self = url(($customerArea ? 'customer/' : '') . 'menu.php');
    require APP_ROOT . '/includes/header.php'; ?>
    <div class="container">
        <section class="panel page-hero reveal">
            <div class="breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a> / Menu</div>
            <h1>Menu <em>KopiKita</em></h1>
            <p>Dari espresso pekat sampai racikan signature kami. Semua dibuat segar setiap hari.</p>
            <form class="search-bar" method="get" action="<?= e($self) ?>">
                <?php if ($catSlug !== 'all'): ?><input type="hidden" name="category" value="<?= e($catSlug) ?>"><?php endif; ?>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari menu favorit…" aria-label="Cari menu">
                <button class="btn btn-primary btn-sm" type="submit">Cari</button>
            </form>
        </section>

        <div class="toolbar">
            <div class="chips">
                <a class="chip <?= $catSlug === 'all' ? 'active' : '' ?>" href="<?= e($self . ($q !== '' ? '?q=' . urlencode($q) : '')) ?>">All</a>
                <?php foreach ($cats as $c): ?>
                    <a class="chip <?= $catSlug === $c['slug'] ? 'active' : '' ?>"
                       href="<?= e($self . '?category=' . urlencode($c['slug']) . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <span class="text-muted"><?= count($products) ?> menu ditemukan</span>
        </div>

        <?php if (!$products): ?>
            <div class="empty-state"><div class="big">Menu tidak ditemukan</div>Coba kata kunci atau kategori lain.</div>
        <?php else: ?>
            <div class="product-grid section-pad">
                <?php foreach ($products as $p) { echo render_product_card($p, $customerArea); } ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    require APP_ROOT . '/includes/footer.php';
}

/** Halaman detail produk (publik / pelanggan). */
function render_product_detail(bool $customerArea): void
{
    $st = db()->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
                         WHERE p.slug = ? AND p.is_available = 1 LIMIT 1');
    $st->execute([(string) ($_GET['slug'] ?? '')]);
    $p = $st->fetch();
    if (!$p) {
        abort(404);
    }
    [$stockText, $stockClass] = stock_info($p);
    $out = $stockClass === 'out';
    $maxQty = max(1, min(20, (int) $p['stock']));

    $rel = db()->prepare('SELECT p.*, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id
                          WHERE p.category_id = ? AND p.id <> ? AND p.is_available = 1 ORDER BY p.is_best_seller DESC LIMIT 4');
    $rel->execute([$p['category_id'], $p['id']]);
    $related = $rel->fetchAll();

    global $pageTitle, $pageDesc, $extraScripts;
    $pageTitle = $p['name'];
    $pageDesc = mb_substr((string) $p['description'], 0, 150);
    $extraScripts = ['cart.js'];
    require APP_ROOT . '/includes/header.php'; ?>
    <div class="container">
        <div class="breadcrumb mt-4"><a href="<?= e(url('index.php')) ?>">Home</a> / <a href="<?= e(url(($customerArea ? 'customer/' : '') . 'menu.php')) ?>">Menu</a> / <?= e($p['name']) ?></div>
        <section class="panel detail-grid">
            <div class="detail-photo"><img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>"></div>
            <div class="detail-info">
                <span class="badge badge-cat"><?= e($p['category_name']) ?></span>
                <?php if ($p['is_best_seller']): ?><span class="badge badge-best">Best Seller</span><?php endif; ?>
                <h1 class="mt-2"><?= e($p['name']) ?></h1>
                <div class="detail-price" id="detailPrice" data-base="<?= (float) $p['price'] ?>"><?= e(rupiah($p['price'])) ?></div>
                <p><?= nl2br(e($p['description'])) ?></p>
                <div class="stock-note <?= e($stockClass) ?>"><?= e($stockText) ?></div>

                <form id="addToCartForm" method="post" action="<?= e(url('customer/cart-action.php')) ?>" data-large-extra="<?= (int) SIZE_PRICES['large'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                    <?php if ($p['has_size_option']): ?>
                        <div class="form-group">
                            <label class="form-label">Ukuran</label>
                            <div class="radio-cards">
                                <?php foreach (SIZE_LABELS as $key => $label): ?>
                                    <label class="radio-card">
                                        <input type="radio" name="size" value="<?= e($key) ?>" <?= $key === 'regular' ? 'checked' : '' ?>>
                                        <span><?= e($label) ?><?= SIZE_PRICES[$key] > 0 ? ' (+' . e(rupiah(SIZE_PRICES[$key])) . ')' : '' ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="size" value="">
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label" for="qty">Jumlah</label>
                        <div class="qty-control">
                            <button type="button" data-qty-minus aria-label="Kurangi">−</button>
                            <input type="number" id="qty" name="qty" value="1" min="1" max="<?= $maxQty ?>" data-max="<?= $maxQty ?>">
                            <button type="button" data-qty-plus aria-label="Tambah">+</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="itemNotes">Catatan (opsional)</label>
                        <input type="text" class="form-control" id="itemNotes" name="notes" maxlength="150" placeholder="Misal: less sugar, tanpa es">
                    </div>
                    <div class="detail-total"><span>Total</span><strong class="price" id="detailTotal"><?= e(rupiah($p['price'])) ?></strong></div>
                    <?php if ($customerArea): ?>
                        <button type="submit" class="btn btn-primary btn-lg btn-block" <?= $out ? 'disabled' : '' ?>><?= $out ? 'Stok Habis' : 'Tambah ke Keranjang' ?></button>
                    <?php elseif (!is_logged_in()): ?>
                        <a class="btn btn-primary btn-lg btn-block" href="<?= e(url('login.php?redirect=' . urlencode('customer/product-detail.php?slug=' . $p['slug']))) ?>">Login untuk Memesan</a>
                    <?php else: ?>
                        <p class="sim-note">Keranjang hanya tersedia untuk akun pelanggan.</p>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <?php if ($related): ?>
            <section class="section">
                <div class="section-head"><h2>Menu <em>Serupa</em></h2></div>
                <div class="product-grid">
                    <?php foreach ($related as $r) { echo render_product_card($r, $customerArea); } ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
    <?php
    require APP_ROOT . '/includes/footer.php';
}

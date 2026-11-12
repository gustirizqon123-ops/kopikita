<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();
$cat = (int) ($_GET['category'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));
$low = !empty($_GET['low']);

$where = ['1=1'];
$args = [];
if ($cat) { $where[] = 'p.category_id = ?'; $args[] = $cat; }
if ($q !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $like = '%' . addcslashes($q, '%_\\') . '%'; array_push($args, $like, $like); }
if ($low) { $where[] = 'p.stock <= p.min_stock'; }
$cond = implode(' AND ', $where);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $cond");
$cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 15);

$st = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
                     WHERE $cond ORDER BY p.name LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$st->execute($args);
$rows = $st->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();

$pageTitle = 'Produk & Stok';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card mb-3">
    <div class="card-body">
        <form class="filter-bar" method="get">
            <div class="form-group"><label class="form-label" for="q">Cari</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Nama / SKU"></div>
            <div class="form-group"><label class="form-label" for="category">Kategori</label>
                <select class="form-control" id="category" name="category"><option value="0">Semua</option>
                    <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select></div>
            <label class="check"><input type="checkbox" name="low" value="1" <?= $low ? 'checked' : '' ?>> Stok menipis</label>
            <button class="btn btn-dark btn-sm" type="submit">Filter</button>
            <a class="btn btn-outline btn-sm" href="<?= e(url('admin/products/index.php')) ?>">Reset</a>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header"><h2>Produk (<?= (int) $pg['total'] ?>)</h2>
        <a class="btn btn-primary btn-sm" href="<?= e(url('admin/products/create.php')) ?>"><?= icon('plus') ?> Tambah Produk</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $isLow = (int) $r['stock'] <= (int) $r['min_stock']; ?>
                <tr>
                    <td><div class="flex" style="gap:10px;align-items:center">
                        <img src="<?= e(product_image_url($r['image'])) ?>" alt="" width="44" height="44" style="border-radius:8px;object-fit:cover">
                        <div><strong><?= e($r['name']) ?></strong> <?= $r['is_best_seller'] ? '<span class="badge badge-best">Best</span>' : '' ?>
                            <div class="text-muted"><?= e($r['sku']) ?></div></div></div></td>
                    <td><?= e($r['category_name']) ?></td>
                    <td class="nowrap"><?= e(rupiah($r['price'])) ?></td>
                    <td><?= (int) $r['stock'] ?> <?= (int) $r['stock'] === 0 ? '<span class="badge badge-out">Habis</span>' : ($isLow ? '<span class="badge badge-low">Menipis</span>' : '') ?>
                        <div class="text-muted">min <?= (int) $r['min_stock'] ?></div></td>
                    <td><?= $r['is_available'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-off">Nonaktif</span>' ?></td>
                    <td><div class="actions">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('admin/products/edit.php?id=' . $r['id'])) ?>">Edit</a>
                        <form method="post" action="<?= e(url('admin/products/delete.php')) ?>" class="inline-form" data-confirm="Hapus produk ini? Jika sudah pernah dipesan, produk hanya dinonaktifkan.">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Tidak ada produk.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= pagination_links($pg) ?>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$where = '1=1';
$args = [];
if ($q !== '') {
    $where = '(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $args = [$like, $like, $like];
}
$cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c WHERE $where");
$cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 15);
$st = $pdo->prepare("SELECT c.*, u.is_active, COUNT(o.id) AS order_count,
        COALESCE(SUM(CASE WHEN o.order_status = 'completed' THEN o.total_amount END), 0) AS spent
        FROM customers c LEFT JOIN users u ON u.id = c.user_id LEFT JOIN orders o ON o.customer_id = c.id
        WHERE $where GROUP BY c.id ORDER BY c.name LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

$pageTitle = 'Data Pelanggan';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card mb-3"><div class="card-body">
    <form class="filter-bar" method="get">
        <div class="form-group"><label class="form-label" for="q">Cari</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Nama / telepon / email"></div>
        <button class="btn btn-dark btn-sm" type="submit">Cari</button>
    </form>
</div></div>
<div class="card">
    <div class="card-header"><h2>Pelanggan (<?= (int) $pg['total'] ?>)</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Nama</th><th>Kontak</th><th>Akun</th><th class="text-right">Pesanan</th><th class="text-right">Total belanja</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><strong><?= e($r['name']) ?></strong><div class="text-muted">Sejak <?= e(tanggal($r['created_at'], false)) ?></div></td>
                <td><?= e($r['phone'] ?: '-') ?><div class="text-muted"><?= e($r['email'] ?: '') ?></div></td>
                <td><?= $r['user_id'] ? ($r['is_active'] ? '<span class="badge badge-ok">Terdaftar</span>' : '<span class="badge badge-off">Nonaktif</span>') : '<span class="badge badge-off">Walk-in</span>' ?></td>
                <td class="text-right"><?= (int) $r['order_count'] ?></td><td class="text-right nowrap"><?= e(rupiah($r['spent'])) ?></td>
                <td><a class="btn btn-outline btn-sm" href="<?= e(url('admin/customers/show.php?id=' . $r['id'])) ?>">Detail</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Tidak ada pelanggan.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?= pagination_links($pg) ?>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

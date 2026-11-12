<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();

// Hapus / nonaktifkan kategori (POST)
if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $cnt->execute([$id]);
    if ((int) $cnt->fetchColumn() > 0) {
        $pdo->prepare('UPDATE categories SET is_active = 0, updated_at = ? WHERE id = ?')->execute([now(), $id]);
        flash('warning', 'Kategori masih memiliki produk, jadi dinonaktifkan (tidak dihapus).');
    } else {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash('success', 'Kategori dihapus.');
    }
    redirect('admin/categories/index.php');
}

$rows = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
                     FROM categories c ORDER BY c.sort_order, c.name')->fetchAll();
$pageTitle = 'Kategori';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card">
    <div class="card-header"><h2>Daftar Kategori (<?= count($rows) ?>)</h2>
        <a class="btn btn-primary btn-sm" href="<?= e(url('admin/categories/create.php')) ?>"><?= icon('plus') ?> Tambah Kategori</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Urutan</th><th>Nama</th><th>Slug</th><th>Produk</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int) $r['sort_order'] ?></td>
                    <td><strong><?= e($r['name']) ?></strong><div class="text-muted"><?= e($r['description']) ?></div></td>
                    <td><?= e($r['slug']) ?></td>
                    <td><?= (int) $r['product_count'] ?></td>
                    <td><?= $r['is_active'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-off">Nonaktif</span>' ?></td>
                    <td><div class="actions">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('admin/categories/edit.php?id=' . $r['id'])) ?>">Edit</a>
                        <form method="post" class="inline-form" data-confirm="Hapus kategori ini? Jika masih punya produk, kategori hanya dinonaktifkan.">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Belum ada kategori.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

<?php
// Hapus produk yang belum pernah dipesan; jika sudah ada di riwayat pesanan → nonaktifkan.
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
if (!is_post()) {
    redirect('admin/products/index.php');
}
verify_csrf();
$pdo = db();
$id = (int) ($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    flash('error', 'Produk tidak ditemukan.');
    redirect('admin/products/index.php');
}
$used = $pdo->prepare('SELECT COUNT(*) FROM order_details WHERE product_id = ?');
$used->execute([$id]);
if ((int) $used->fetchColumn() > 0) {
    $pdo->prepare('UPDATE products SET is_available = 0, updated_at = ? WHERE id = ?')->execute([now(), $id]);
    flash('warning', 'Produk sudah ada di riwayat pesanan, jadi dinonaktifkan (tidak dihapus).');
} else {
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    delete_upload($p['image'], 'products');
    flash('success', 'Produk dihapus.');
}
redirect('admin/products/index.php');

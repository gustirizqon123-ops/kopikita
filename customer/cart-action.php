<?php
// Tambah / ubah / hapus item keranjang (session). Harga TIDAK disimpan dari browser; dihitung ulang saat tampil & checkout.
require_once __DIR__ . '/../includes/auth.php';
require_role(['customer']);

if (!is_post()) {
    redirect('customer/cart.php');
}
verify_csrf();

$pdo = db();
$action = (string) ($_POST['action'] ?? '');
$cart = cart_get();
$msg = '';
$ok = true;

/** Kunci item = produk + ukuran. */
$makeKey = fn(int $pid, ?string $size): string => $pid . ':' . ($size ?? '-');

$loadProduct = function (int $pid) use ($pdo) {
    $st = $pdo->prepare('SELECT p.*, c.is_active AS category_active FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
    $st->execute([$pid]);
    return $st->fetch();
};

if ($action === 'add') {
    $pid = (int) ($_POST['product_id'] ?? 0);
    $qty = max(1, min(99, (int) ($_POST['qty'] ?? 1)));
    $p = $loadProduct($pid);
    if (!$p || !$p['is_available'] || !$p['category_active']) {
        $ok = false;
        $msg = 'Produk tidak tersedia.';
    } else {
        $size = normalize_size($p, $_POST['size'] ?? null);
        $key = $makeKey($pid, $size);
        $newQty = ($cart[$key]['qty'] ?? 0) + $qty;
        if ($newQty > (int) $p['stock']) {
            $ok = false;
            $msg = 'Stok ' . $p['name'] . ' tidak mencukupi (tersisa ' . (int) $p['stock'] . ').';
        } else {
            $cart[$key] = [
                'key' => $key, 'product_id' => $pid, 'size' => $size, 'qty' => $newQty,
                'notes' => mb_substr(trim((string) ($_POST['notes'] ?? ($cart[$key]['notes'] ?? ''))), 0, 150),
            ];
            $msg = $p['name'] . ' ditambahkan ke keranjang.';
        }
    }
} elseif ($action === 'update') {
    $key = (string) ($_POST['key'] ?? '');
    if (isset($cart[$key])) {
        $item = $cart[$key];
        $p = $loadProduct((int) $item['product_id']);
        $qty = max(1, min(99, (int) ($_POST['qty'] ?? $item['qty'])));
        $size = $p ? normalize_size($p, $_POST['size'] ?? $item['size']) : $item['size'];
        if ($p && $qty > (int) $p['stock']) {
            $ok = false;
            $msg = 'Stok ' . $p['name'] . ' hanya tersisa ' . (int) $p['stock'] . '.';
            $qty = max(1, (int) $p['stock']);
        }
        unset($cart[$key]);
        $newKey = $makeKey((int) $item['product_id'], $size);
        if (isset($cart[$newKey])) {   // gabungkan bila ukuran diubah menjadi sama
            $qty = min(99, $qty + $cart[$newKey]['qty']);
        }
        $cart[$newKey] = [
            'key' => $newKey, 'product_id' => (int) $item['product_id'], 'size' => $size, 'qty' => $qty,
            'notes' => mb_substr(trim((string) ($_POST['notes'] ?? $item['notes'])), 0, 150),
        ];
        $msg = $msg ?: 'Keranjang diperbarui.';
    }
} elseif ($action === 'remove') {
    unset($cart[(string) ($_POST['key'] ?? '')]);
    $msg = 'Item dihapus dari keranjang.';
} elseif ($action === 'clear') {
    $cart = [];
    $msg = 'Keranjang dikosongkan.';
} else {
    $ok = false;
    $msg = 'Aksi tidak dikenal.';
}

$_SESSION['cart'] = $cart;
$count = cart_count();

if (is_ajax()) {
    json_response(['ok' => $ok, 'message' => $msg, 'count' => $count], $ok ? 200 : 422);
}
flash($ok ? 'success' : 'error', $msg);
$back = $action === 'add' ? safe_redirect_path((string) ($_SERVER['HTTP_REFERER'] ?? ''), 'customer/cart.php') : 'customer/cart.php';
redirect($back);

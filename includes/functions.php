<?php
/**
 * Fungsi bantu umum KopiKita: escaping, format, URL, CSRF, flash,
 * upload gambar, kode pesanan, dan logika bisnis pesanan/stok.
 */

// ---------------------------------------------------------------------
// Output & format
// ---------------------------------------------------------------------

/** Escape output HTML. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format rupiah: Rp25.000 */
function rupiah($amount): string
{
    $amount = (float) $amount;
    $prefix = $amount < 0 ? '-Rp' : 'Rp';
    return $prefix . number_format(abs($amount), 0, ',', '.');
}

/** Format tanggal Indonesia singkat. */
function tanggal($datetime, bool $withTime = true): string
{
    if (!$datetime) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime((string) $datetime);
    $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('H:i', $ts) : $out;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

// ---------------------------------------------------------------------
// URL & redirect
// ---------------------------------------------------------------------

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . ltrim($path, '/');
    $ver = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $ver;
}

function redirect(string $path): void
{
    $target = preg_match('#^https?://#', $path) || strpos($path, BASE_URL . '/') === 0 ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/** Hanya izinkan redirect internal (cegah open redirect). */
function safe_redirect_path(?string $path, string $fallback = ''): string
{
    $path = (string) $path;
    if ($path === '' || !preg_match('#^/[^/\\\\]#', $path) || strpos($path, BASE_URL . '/') !== 0) {
        return url($fallback);
    }
    return $path;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function current_path(): string
{
    return strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
}

/** Tandai link aktif pada navigasi. */
function nav_active(string $path, bool $prefix = false): string
{
    $cur = current_path();
    $target = url($path);
    $match = $prefix ? strpos($cur, rtrim($target, '/')) === 0 : ($cur === $target || ($path === 'index.php' && $cur === BASE_URL . '/'));
    return $match ? 'active' : '';
}

function abort(int $code): void
{
    http_response_code($code);
    if ($code === 403) {
        require APP_ROOT . '/403.php';
    } else {
        require APP_ROOT . '/404.php';
    }
    exit;
}

// ---------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validasi token CSRF dari form (field) atau header X-CSRF-Token (fetch). */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        if (is_ajax()) {
            json_response(['ok' => false, 'message' => 'Sesi kedaluwarsa. Muat ulang halaman.'], 403);
        }
        exit('<h1>419 – Sesi kedaluwarsa</h1><p>Token keamanan tidak valid. <a href="javascript:history.back()">Kembali</a> dan muat ulang halaman.</p>');
    }
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ---------------------------------------------------------------------
// Flash message & old input
// ---------------------------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function render_flashes(): string
{
    $html = '';
    foreach (get_flashes() as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . '" role="alert">' . e($f['message'])
            . '<button type="button" class="alert-close" aria-label="Tutup">&times;</button></div>';
    }
    return $html;
}

function set_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['csrf_token'], $data['current_password'], $data['new_password']);
    $_SESSION['old'] = $data;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

// ---------------------------------------------------------------------
// String
// ---------------------------------------------------------------------

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = strtr($text, ['è' => 'e', 'é' => 'e', 'à' => 'a', 'ç' => 'c', '&' => ' ']);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

/** Pastikan slug unik pada tabel tertentu. */
function unique_slug(PDO $pdo, string $table, string $base, ?int $ignoreId = null): string
{
    $allowed = ['products', 'categories'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Tabel tidak valid');
    }
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM `$table` WHERE slug = ?" . ($ignoreId ? ' AND id <> ?' : '') . ' LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if (!$st->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function money_input($value): float
{
    // Terima "25.000", "25000", "Rp25.000"
    $clean = preg_replace('/[^0-9]/', '', (string) $value);
    return $clean === '' ? 0.0 : (float) $clean;
}

// ---------------------------------------------------------------------
// Gambar & upload
// ---------------------------------------------------------------------

function product_image_url(?string $image): string
{
    if ($image && is_file(APP_ROOT . '/assets/images/products/' . basename($image))) {
        return url('assets/images/products/' . rawurlencode(basename($image)));
    }
    return url('assets/images/ui/placeholder-product.svg');
}

function gallery_image_url(?string $image): string
{
    if ($image && is_file(APP_ROOT . '/assets/images/gallery/' . basename($image))) {
        return url('assets/images/gallery/' . rawurlencode(basename($image)));
    }
    return url('assets/images/ui/placeholder-gallery.svg');
}

/**
 * Upload gambar JPG/PNG/WEBP maks 2 MB dengan validasi MIME asli (finfo).
 * @return array{ok:bool, filename?:string, error?:string}
 */
function upload_image(array $file, string $folder): array
{
    if (!in_array($folder, ['products', 'gallery'], true)) {
        return ['ok' => false, 'error' => 'Folder upload tidak valid.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Tidak ada file yang dipilih.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Ukuran file melebihi batas 2 MB.' : 'Upload gagal (kode ' . (int) $file['error'] . ').';
        return ['ok' => false, 'error' => $msg];
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Ukuran file maksimal 2 MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'File upload tidak valid.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(UPLOAD_ALLOWED_MIME[$mime])) {
        return ['ok' => false, 'error' => 'Format harus JPG, PNG, atau WEBP.'];
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'File bukan gambar yang valid.'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . UPLOAD_ALLOWED_MIME[$mime];
    $dest = APP_ROOT . '/assets/images/' . $folder . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Gagal menyimpan file.'];
    }
    return ['ok' => true, 'filename' => $name];
}

/** Hapus file upload lama (hanya nama file acak hasil upload, bukan placeholder bawaan). */
function delete_upload(?string $filename, string $folder): void
{
    if (!$filename || !in_array($folder, ['products', 'gallery'], true)) {
        return;
    }
    $filename = basename($filename);
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) {
        return; // jangan hapus aset contoh
    }
    $path = APP_ROOT . '/assets/images/' . $folder . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

// ---------------------------------------------------------------------
// Badge & label
// ---------------------------------------------------------------------

function status_badge(string $status): string
{
    $label = ORDER_STATUSES[$status] ?? ucfirst($status);
    return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>';
}

function payment_badge(?string $status): string
{
    $status = $status ?: 'pending';
    $label = PAYMENT_STATUSES[$status] ?? ucfirst($status);
    return '<span class="badge badge-pay-' . e($status) . '">' . e($label) . '</span>';
}

function size_label(?string $size): string
{
    return $size ? (SIZE_LABELS[$size] ?? ucfirst($size)) : '-';
}

// ---------------------------------------------------------------------
// Kode pesanan & invoice
// ---------------------------------------------------------------------

/**
 * Buat kode berurutan per hari, contoh KK-20261112-0001 / INV-20261112-0001.
 * Dipanggil DI DALAM transaksi; baris terakhir dikunci dengan FOR UPDATE.
 */
function generate_code(PDO $pdo, string $type): string
{
    $map = [
        'order'   => ['orders', 'order_code', 'KK'],
        'invoice' => ['sales', 'invoice_number', 'INV'],
    ];
    [$table, $col, $pfx] = $map[$type];
    $prefix = $pfx . '-' . date('Ymd') . '-';
    $st = $pdo->prepare("SELECT `$col` FROM `$table` WHERE `$col` LIKE ? ORDER BY `$col` DESC LIMIT 1 FOR UPDATE");
    $st->execute([$prefix . '%']);
    $last = $st->fetchColumn();
    $next = $last ? ((int) substr($last, -4)) + 1 : 1;
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

// ---------------------------------------------------------------------
// Keranjang & kalkulasi harga (selalu di server)
// ---------------------------------------------------------------------

/** Harga satuan = harga dasar + tambahan ukuran. */
function unit_price(array $product, ?string $size): float
{
    $price = (float) $product['price'];
    if ((int) $product['has_size_option'] === 1) {
        $price += (float) (SIZE_PRICES[$size ?? 'regular'] ?? 0);
    }
    return $price;
}

/** Normalisasi ukuran sesuai produk. */
function normalize_size(array $product, $size): ?string
{
    if ((int) $product['has_size_option'] !== 1) {
        return null;
    }
    return isset(SIZE_PRICES[$size]) ? $size : 'regular';
}

/**
 * Ubah daftar item mentah [{product_id, size, qty, notes}] menjadi item tervalidasi
 * dengan harga dari database. $lock = true untuk SELECT ... FOR UPDATE (di dalam transaksi).
 * @return array{items:array, subtotal:float, errors:array}
 */
function resolve_items(PDO $pdo, array $rawItems, bool $lock = false): array
{
    $items = [];
    $errors = [];
    $subtotal = 0.0;
    $ids = array_values(array_unique(array_map(fn($i) => (int) ($i['product_id'] ?? 0), $rawItems)));
    $ids = array_filter($ids);
    if (!$ids) {
        return ['items' => [], 'subtotal' => 0.0, 'errors' => ['Keranjang masih kosong.']];
    }
    sort($ids); // urutan kunci konsisten untuk menghindari deadlock
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT p.*, c.is_active AS category_active, c.name AS category_name
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE p.id IN ($in) ORDER BY p.id" . ($lock ? ' FOR UPDATE' : '');
    $st = $pdo->prepare($sql);
    $st->execute($ids);
    $products = [];
    foreach ($st->fetchAll() as $p) {
        $products[(int) $p['id']] = $p;
    }

    $qtyPerProduct = [];
    foreach ($rawItems as $raw) {
        $pid = (int) ($raw['product_id'] ?? 0);
        $qty = (int) ($raw['qty'] ?? 0);
        if (!isset($products[$pid])) {
            $errors[] = 'Produk tidak ditemukan.';
            continue;
        }
        $p = $products[$pid];
        if ($qty < 1 || $qty > 99) {
            $errors[] = 'Jumlah untuk ' . $p['name'] . ' tidak valid.';
            continue;
        }
        if ((int) $p['is_available'] !== 1 || (int) $p['category_active'] !== 1) {
            $errors[] = $p['name'] . ' sedang tidak tersedia.';
            continue;
        }
        $size = normalize_size($p, $raw['size'] ?? null);
        $price = unit_price($p, $size);
        $qtyPerProduct[$pid] = ($qtyPerProduct[$pid] ?? 0) + $qty;
        $line = $price * $qty;
        $subtotal += $line;
        $items[] = [
            'key'        => $raw['key'] ?? null,
            'product_id' => $pid,
            'product'    => $p,
            'name'       => $p['name'],
            'size'       => $size,
            'unit_price' => $price,
            'qty'        => $qty,
            'line_total' => $line,
            'notes'      => mb_substr(trim((string) ($raw['notes'] ?? '')), 0, 255),
        ];
    }
    foreach ($qtyPerProduct as $pid => $q) {
        if ($q > (int) $products[$pid]['stock']) {
            $errors[] = 'Stok ' . $products[$pid]['name'] . ' tidak mencukupi (tersisa ' . (int) $products[$pid]['stock'] . ').';
        }
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'errors' => $errors];
}

function cart_get(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_map(fn($i) => (int) $i['qty'], cart_get()));
}

// ---------------------------------------------------------------------
// Logika pesanan (transaksi database) – ERD bagian 4
// ---------------------------------------------------------------------

/**
 * Buat pesanan baru: orders + order_details + sales (pending) dalam satu transaksi.
 * $opt: channel, order_type, table_number, notes, payment_method, discount,
 *       processed_by, deduct_stock (POS), pay (['amount_paid','reference','cashier_id'])
 * @return array{ok:bool, order_id?:int, order_code?:string, errors?:array}
 */
function create_order(PDO $pdo, int $customerId, array $rawItems, array $opt): array
{
    $channel = $opt['channel'] === 'cashier' ? 'cashier' : 'online';
    $type = isset(ORDER_TYPES[$opt['order_type'] ?? '']) ? $opt['order_type'] : null;
    $method = isset(PAYMENT_METHODS[$opt['payment_method'] ?? '']) ? $opt['payment_method'] : null;
    $table = $type === 'dine_in' ? mb_substr(trim((string) ($opt['table_number'] ?? '')), 0, 10) : null;
    $errors = [];
    if (!$type) {
        $errors[] = 'Pilih jenis pesanan.';
    }
    if (!$method) {
        $errors[] = 'Pilih metode pembayaran.';
    }
    if ($type === 'dine_in' && $table === '') {
        $errors[] = 'Nomor meja wajib diisi untuk dine-in.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }

    try {
        $pdo->beginTransaction();
        $res = resolve_items($pdo, $rawItems, true); // kunci stok
        if ($res['errors']) {
            $pdo->rollBack();
            return ['ok' => false, 'errors' => $res['errors']];
        }
        $subtotal = $res['subtotal'];
        $discount = max(0.0, min((float) ($opt['discount'] ?? 0), $subtotal));
        $total = $subtotal - $discount;
        $deduct = !empty($opt['deduct_stock']);
        $now = now();

        $code = generate_code($pdo, 'order');
        $st = $pdo->prepare('INSERT INTO orders (order_code, customer_id, processed_by, order_channel, order_type, table_number,
                order_status, subtotal, discount_amount, total_amount, notes, stock_deducted, ordered_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([
            $code, $customerId, $opt['processed_by'] ?? null, $channel, $type, $table ?: null,
            $deduct ? 'confirmed' : 'pending', $subtotal, $discount, $total,
            mb_substr(trim((string) ($opt['notes'] ?? '')), 0, 1000) ?: null, $deduct ? 1 : 0, $now, $now,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $det = $pdo->prepare('INSERT INTO order_details (order_id, product_id, product_name, size, unit_price, quantity, line_total, item_notes)
                VALUES (?,?,?,?,?,?,?,?)');
        $dec = $pdo->prepare('UPDATE products SET stock = stock - ?, updated_at = ? WHERE id = ? AND stock >= ?');
        foreach ($res['items'] as $it) {
            $det->execute([$orderId, $it['product_id'], $it['name'], $it['size'], $it['unit_price'], $it['qty'], $it['line_total'], $it['notes'] ?: null]);
            if ($deduct) {
                $dec->execute([$it['qty'], $now, $it['product_id'], $it['qty']]);
                if ($dec->rowCount() !== 1) {
                    throw new RuntimeException('Stok ' . $it['name'] . ' tidak mencukupi.');
                }
            }
        }

        // Data penjualan (pending), atau langsung lunas untuk POS
        $invoice = generate_code($pdo, 'invoice');
        $pay = $opt['pay'] ?? null;
        $paid = 0.0;
        $change = 0.0;
        $payStatus = 'pending';
        $ref = null;
        $paidAt = null;
        $cashierId = null;
        if ($pay) {
            $check = validate_payment($method, $total, $pay['amount_paid'] ?? 0, $pay['reference'] ?? '');
            if (!$check['ok']) {
                throw new RuntimeException($check['error']);
            }
            [$paid, $change, $ref] = [$check['paid'], $check['change'], $check['reference']];
            $payStatus = 'paid';
            $paidAt = $now;
            $cashierId = $pay['cashier_id'] ?? null;
        }
        $st = $pdo->prepare('INSERT INTO sales (order_id, invoice_number, cashier_id, payment_method, amount_due, amount_paid,
                change_amount, payment_status, reference_number, paid_at, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$orderId, $invoice, $cashierId, $method, $total, $paid, $change, $payStatus, $ref, $paidAt, $now, $now]);

        $pdo->commit();
        return ['ok' => true, 'order_id' => $orderId, 'order_code' => $code];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $msg = $ex instanceof RuntimeException ? $ex->getMessage() : 'Pesanan gagal disimpan. Silakan coba lagi.';
        if (APP_DEBUG && !($ex instanceof RuntimeException)) {
            $msg .= ' (' . $ex->getMessage() . ')';
        }
        return ['ok' => false, 'errors' => [$msg]];
    }
}

/**
 * Validasi pembayaran (aturan ERD no. 10).
 * @return array{ok:bool, paid?:float, change?:float, reference?:?string, error?:string}
 */
function validate_payment(string $method, float $due, $amountPaid, $reference): array
{
    if (!isset(PAYMENT_METHODS[$method])) {
        return ['ok' => false, 'error' => 'Metode pembayaran tidak valid.'];
    }
    if ($method === 'cash') {
        $paid = (float) $amountPaid;
        if ($paid < $due) {
            return ['ok' => false, 'error' => 'Uang diterima kurang dari total tagihan (' . rupiah($due) . ').'];
        }
        return ['ok' => true, 'paid' => $paid, 'change' => $paid - $due, 'reference' => null];
    }
    $reference = mb_substr(trim((string) $reference), 0, 100);
    if ($reference === '') {
        return ['ok' => false, 'error' => 'Nomor referensi QRIS/transfer (simulasi) wajib diisi.'];
    }
    return ['ok' => true, 'paid' => $due, 'change' => 0.0, 'reference' => $reference];
}

/**
 * Konfirmasi pembayaran: sales → paid.
 */
function confirm_payment(PDO $pdo, int $orderId, string $method, $amountPaid, $reference, int $cashierId): array
{
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT o.*, s.id AS sale_id, s.payment_status FROM orders o JOIN sales s ON s.order_id = o.id WHERE o.id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $o = $st->fetch();
        if (!$o) {
            throw new RuntimeException('Pesanan tidak ditemukan.');
        }
        if ($o['order_status'] === 'cancelled') {
            throw new RuntimeException('Pesanan sudah dibatalkan.');
        }
        if ($o['payment_status'] === 'paid') {
            throw new RuntimeException('Pesanan ini sudah lunas.');
        }
        $check = validate_payment($method, (float) $o['total_amount'], $amountPaid, $reference);
        if (!$check['ok']) {
            throw new RuntimeException($check['error']);
        }
        $now = now();
        $pdo->prepare('UPDATE sales SET payment_method = ?, amount_due = ?, amount_paid = ?, change_amount = ?, payment_status = "paid",
                reference_number = ?, cashier_id = ?, paid_at = ?, updated_at = ? WHERE id = ?')
            ->execute([$method, $o['total_amount'], $check['paid'], $check['change'], $check['reference'], $cashierId, $now, $now, $o['sale_id']]);
        $pdo->prepare('UPDATE orders SET processed_by = COALESCE(processed_by, ?), updated_at = ? WHERE id = ?')
            ->execute([$cashierId, $now, $orderId]);
        $pdo->commit();
        return ['ok' => true, 'change' => $check['change']];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => $ex instanceof RuntimeException ? $ex->getMessage() : 'Gagal mengonfirmasi pembayaran.'];
    }
}

/**
 * Ubah status pesanan dengan aturan alur, stok, dan pembayaran (ERD bagian 4 no. 7–9).
 */
function change_order_status(PDO $pdo, int $orderId, string $newStatus, int $userId, string $cancelReason = ''): array
{
    if (!isset(ORDER_STATUSES[$newStatus])) {
        return ['ok' => false, 'error' => 'Status tidak valid.'];
    }
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT o.*, s.id AS sale_id, s.payment_status FROM orders o LEFT JOIN sales s ON s.order_id = o.id WHERE o.id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $o = $st->fetch();
        if (!$o) {
            throw new RuntimeException('Pesanan tidak ditemukan.');
        }
        $current = $o['order_status'];
        if (!in_array($newStatus, ORDER_TRANSITIONS[$current] ?? [], true)) {
            throw new RuntimeException('Status tidak dapat diubah dari ' . ORDER_STATUSES[$current] . ' ke ' . ORDER_STATUSES[$newStatus] . '.');
        }
        $now = now();
        $items = $pdo->prepare('SELECT product_id, product_name, SUM(quantity) AS qty FROM order_details WHERE order_id = ? GROUP BY product_id, product_name ORDER BY product_id');
        $items->execute([$orderId]);
        $items = $items->fetchAll();

        if ($newStatus === 'confirmed' && (int) $o['stock_deducted'] === 0) {
            // Kunci dan kurangi stok
            $ids = array_column($items, 'product_id');
            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("SELECT id FROM products WHERE id IN ($in) ORDER BY id FOR UPDATE")->execute($ids);
            $dec = $pdo->prepare('UPDATE products SET stock = stock - ?, updated_at = ? WHERE id = ? AND stock >= ?');
            foreach ($items as $it) {
                $dec->execute([$it['qty'], $now, $it['product_id'], $it['qty']]);
                if ($dec->rowCount() !== 1) {
                    throw new RuntimeException('Stok ' . $it['product_name'] . ' tidak mencukupi untuk mengonfirmasi pesanan.');
                }
            }
            $pdo->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')->execute([$orderId]);
        }

        if ($newStatus === 'completed' && $o['payment_status'] !== 'paid') {
            throw new RuntimeException('Pesanan belum lunas. Konfirmasi pembayaran terlebih dahulu.');
        }

        if ($newStatus === 'cancelled') {
            $cancelReason = mb_substr(trim($cancelReason), 0, 255);
            if ($cancelReason === '') {
                throw new RuntimeException('Alasan pembatalan wajib diisi.');
            }
            if ((int) $o['stock_deducted'] === 1) {
                $inc = $pdo->prepare('UPDATE products SET stock = stock + ?, updated_at = ? WHERE id = ?');
                foreach ($items as $it) {
                    $inc->execute([$it['qty'], $now, $it['product_id']]);
                }
            }
            $pdo->prepare('UPDATE orders SET stock_deducted = 0, cancel_reason = ? WHERE id = ?')->execute([$cancelReason, $orderId]);
            if ($o['sale_id']) {
                $newPay = $o['payment_status'] === 'paid' ? 'refunded' : 'failed';
                $pdo->prepare('UPDATE sales SET payment_status = ?, updated_at = ? WHERE id = ?')->execute([$newPay, $now, $o['sale_id']]);
            }
        }

        $pdo->prepare('UPDATE orders SET order_status = ?, processed_by = COALESCE(processed_by, ?), completed_at = ?, updated_at = ? WHERE id = ?')
            ->execute([$newStatus, $userId, $newStatus === 'completed' ? $now : null, $now, $orderId]);
        $pdo->commit();
        return ['ok' => true];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => $ex instanceof RuntimeException ? $ex->getMessage() : 'Gagal mengubah status pesanan.'];
    }
}

/** Ambil pesanan lengkap (order + sales + customer + items). */
function find_order(PDO $pdo, string $by, $value): ?array
{
    $col = $by === 'code' ? 'o.order_code' : 'o.id';
    $st = $pdo->prepare("SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.user_id AS customer_user_id,
            s.invoice_number, s.payment_method, s.amount_due, s.amount_paid, s.change_amount, s.payment_status,
            s.reference_number, s.paid_at, s.cashier_id, u.name AS processed_name, k.name AS cashier_name
        FROM orders o
        JOIN customers c ON c.id = o.customer_id
        LEFT JOIN sales s ON s.order_id = o.id
        LEFT JOIN users u ON u.id = o.processed_by
        LEFT JOIN users k ON k.id = s.cashier_id
        WHERE $col = ? LIMIT 1");
    $st->execute([$value]);
    $order = $st->fetch();
    if (!$order) {
        return null;
    }
    $it = $pdo->prepare('SELECT od.*, p.image, p.sku FROM order_details od LEFT JOIN products p ON p.id = od.product_id WHERE od.order_id = ? ORDER BY od.id');
    $it->execute([$order['id']]);
    $order['items'] = $it->fetchAll();
    return $order;
}

/** Validasi rentang tanggal filter laporan (Y-m-d). */
function date_range(string $defaultDaysBack = '-29 days'): array
{
    $from = (string) ($_GET['from'] ?? '');
    $to = (string) ($_GET['to'] ?? '');
    $valid = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
    if (!$valid($from)) {
        $from = date('Y-m-d', strtotime($defaultDaysBack));
    }
    if (!$valid($to)) {
        $to = date('Y-m-d');
    }
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [$from, $to];
}

/** Ringkasan laporan penjualan (hanya completed + paid). */
function sales_report(PDO $pdo, string $from, string $to): array
{
    $base = "FROM orders o JOIN sales s ON s.order_id = o.id
             WHERE o.order_status = 'completed' AND s.payment_status = 'paid' AND DATE(o.ordered_at) BETWEEN ? AND ?";
    $st = $pdo->prepare("SELECT COUNT(o.id) AS trx, COALESCE(SUM(o.total_amount),0) AS omzet, COALESCE(SUM(o.discount_amount),0) AS diskon $base");
    $st->execute([$from, $to]);
    $summary = $st->fetch();

    $st = $pdo->prepare("SELECT DATE(o.ordered_at) AS tanggal, COUNT(o.id) AS jumlah_transaksi, SUM(o.total_amount) AS total_penjualan
            $base GROUP BY DATE(o.ordered_at) ORDER BY tanggal ASC");
    $st->execute([$from, $to]);
    $daily = $st->fetchAll();

    $st = $pdo->prepare("SELECT od.product_name, SUM(od.quantity) AS jumlah_terjual, SUM(od.line_total) AS nilai_penjualan
            FROM order_details od JOIN orders o ON o.id = od.order_id JOIN sales s ON s.order_id = o.id
            WHERE o.order_status = 'completed' AND s.payment_status = 'paid' AND DATE(o.ordered_at) BETWEEN ? AND ?
            GROUP BY od.product_id, od.product_name ORDER BY jumlah_terjual DESC, nilai_penjualan DESC LIMIT 5");
    $st->execute([$from, $to]);
    $top = $st->fetchAll();

    $st = $pdo->prepare("SELECT s.payment_method, COUNT(*) AS trx, SUM(o.total_amount) AS total $base GROUP BY s.payment_method");
    $st->execute([$from, $to]);
    $methods = $st->fetchAll();

    $st = $pdo->prepare("SELECT o.order_code, o.ordered_at, o.order_channel, o.order_type, c.name AS customer_name, s.invoice_number,
            s.payment_method, o.subtotal, o.discount_amount, o.total_amount
            FROM orders o JOIN sales s ON s.order_id = o.id JOIN customers c ON c.id = o.customer_id
            WHERE o.order_status = 'completed' AND s.payment_status = 'paid' AND DATE(o.ordered_at) BETWEEN ? AND ?
            ORDER BY o.ordered_at ASC");
    $st->execute([$from, $to]);
    $rows = $st->fetchAll();

    // Lengkapi grafik harian (tanggal tanpa transaksi = 0)
    $map = [];
    foreach ($daily as $d) {
        $map[$d['tanggal']] = $d;
    }
    $chart = ['labels' => [], 'values' => []];
    $days = (int) ((strtotime($to) - strtotime($from)) / 86400);
    if ($days <= 92) {
        for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)) {
            $k = date('Y-m-d', $t);
            $chart['labels'][] = date('d/m', $t);
            $chart['values'][] = isset($map[$k]) ? (float) $map[$k]['total_penjualan'] : 0;
        }
    } else {
        foreach ($daily as $d) {
            $chart['labels'][] = date('d/m/y', strtotime($d['tanggal']));
            $chart['values'][] = (float) $d['total_penjualan'];
        }
    }

    return compact('summary', 'daily', 'top', 'methods', 'rows', 'chart');
}

// ---------------------------------------------------------------------
// Ikon SVG inline (stroke, mewarisi currentColor)
// ---------------------------------------------------------------------

function icon(string $name, string $class = ''): string
{
    static $paths = [
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cart'      => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
        'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
        'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'left'      => '<path d="M15 6l-6 6 6 6"/>',
        'right'     => '<path d="M9 6l6 6-6 6"/>',
        'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'   => '<path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/>',
        'coffee'    => '<path d="M4 9h13v5a6 6 0 0 1-6 6H10a6 6 0 0 1-6-6V9z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H17M8 2v3M12 2v3"/>',
        'bean'      => '<ellipse cx="12" cy="12" rx="6" ry="9" transform="rotate(35 12 12)"/><path d="M8 17c3-2 5-8 8-10"/>',
        'leaf'      => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19l7-7"/>',
        'award'     => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/>',
        'star'      => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'tag'       => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'box'       => '<path d="m21 8-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8M12 13v8"/>',
        'receipt'   => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'chart'     => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'image'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 17-5-5-9 8"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/>',
        'pos'       => '<rect x="3" y="3" width="18" height="12" rx="2"/><path d="M7 21h10M12 15v6M7 8h4"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'history'   => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
        'cash'      => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>',
        'check'     => '<path d="m5 12 5 5 9-10"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'edit'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m14 6 4 4"/>',
        'trash'     => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'print'     => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
        'download'  => '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'pin'       => '<path d="M12 22s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'     => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'whatsapp'  => '<path d="M3.5 20.5 5 16a8.5 8.5 0 1 1 3.2 3.1z"/><path d="M9 9.5c.3 2 2.5 4.2 4.5 4.6l1.2-1.2 1.8.8c-.3 1.3-1.3 2-2.5 1.8C11 15 8.6 12.7 8.2 9.8 8 8.6 8.8 7.6 10 7.3l.8 1.8z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8"/>',
        'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8a0 0 0 0 1 0 0z"/>',
        'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 3 2.5 5 5.5 5"/>',
        'x'         => '<path d="M4 4l16 16M20 4 4 20"/>',
        'refresh'   => '<path d="M20 11a8 8 0 0 0-14.9-3.5M4 4v4h4M4 13a8 8 0 0 0 14.9 3.5M20 20v-4h-4"/>',
        'home'      => '<path d="m3 11 9-8 9 8M5 9.5V21h14V9.5"/>',
        'filter'    => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
    ];
    $p = $paths[$name] ?? $paths['coffee'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Inisial nama untuk avatar. */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $s = mb_substr($parts[0] ?? 'K', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
    return mb_strtoupper($s);
}

/** Pagination sederhana. */
function paginate(int $total, int $perPage = 15): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'limit' => $perPage, 'total' => $total];
}

function pagination_links(array $pg): string
{
    if ($pg['pages'] <= 1) {
        return '';
    }
    $q = $_GET;
    $html = '<nav class="pagination" aria-label="Halaman">';
    for ($i = 1; $i <= $pg['pages']; $i++) {
        $q['page'] = $i;
        $html .= $i === $pg['page'] ? '<span class="current">' . $i . '</span>'
            : '<a href="?' . e(http_build_query($q)) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}

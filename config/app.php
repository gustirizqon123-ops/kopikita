<?php
/**
 * Konfigurasi aplikasi KopiKita.
 */

define('APP_NAME', 'KopiKita');
define('APP_FULL_NAME', 'KopiKita – Coffee Shop Management System');
define('APP_SLOGAN', 'A Little Coffee, A Lot of Happiness.');
define('APP_EST', '2026');

// Zona waktu & locale
define('APP_TIMEZONE', 'Asia/Makassar');
date_default_timezone_set(APP_TIMEZONE);

// Mode debug: tampilkan detail error (matikan di server produksi)
define('APP_DEBUG', true);

/**
 * BASE_URL dideteksi otomatis dari lokasi folder terhadap DOCUMENT_ROOT,
 * sehingga jalan di http://localhost/kopikita/ maupun `php -S localhost:8000`.
 * Bisa ditimpa manual, contoh: define('BASE_URL', '/kopikita');
 */
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(__DIR__ . '/..');
    $base = '/kopikita';
    if ($docRoot && $appRoot) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $appRoot = str_replace('\\', '/', $appRoot);
        if (stripos($appRoot, $docRoot) === 0) {
            $base = substr($appRoot, strlen($docRoot));
        }
    }
    define('BASE_URL', rtrim($base, '/'));
}

define('APP_ROOT', realpath(__DIR__ . '/..'));

// Tambahan harga per ukuran (Regular / Large) – ERD 3.4
define('SIZE_PRICES', ['regular' => 0, 'large' => 5000]);
define('SIZE_LABELS', ['regular' => 'Regular', 'large' => 'Large']);

// Upload gambar
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024); // 2 MB
define('UPLOAD_ALLOWED_MIME', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

// Label status pesanan (ERD 3.5)
define('ORDER_STATUSES', [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready'     => 'Ready',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
]);

// Alur status yang diizinkan (ERD bagian 4 no. 8)
define('ORDER_TRANSITIONS', [
    'pending'   => ['confirmed', 'cancelled'],
    'confirmed' => ['preparing', 'cancelled'],
    'preparing' => ['ready'],
    'ready'     => ['completed'],
    'completed' => [],
    'cancelled' => [],
]);

define('PAYMENT_METHODS', [
    'cash'          => 'Cash di Kasir',
    'qris'          => 'QRIS (simulasi)',
    'bank_transfer' => 'Transfer Bank (simulasi)',
]);

define('PAYMENT_STATUSES', [
    'pending'  => 'Belum Bayar',
    'paid'     => 'Lunas',
    'failed'   => 'Gagal',
    'refunded' => 'Dikembalikan',
]);

define('ORDER_TYPES', ['dine_in' => 'Dine-in', 'takeaway' => 'Takeaway']);
define('ORDER_CHANNELS', ['online' => 'Online', 'cashier' => 'Kasir']);

define('GALLERY_COLLECTIONS', [
    'coffee'   => 'Coffee',
    'drinks'   => 'Drinks',
    'food'     => 'Food',
    'ambience' => 'Ambience',
    'desserts' => 'Desserts',
]);

define('ROLE_LABELS', ['admin' => 'Admin', 'cashier' => 'Kasir', 'customer' => 'Pelanggan']);

// Info kedai (simulasi)
define('SHOP_ADDRESS', 'Jl. Kopi Nusantara No. 26, Makassar, Sulawesi Selatan');
define('SHOP_PHONE', '+62 811-0000-2026');
define('SHOP_WHATSAPP', '6281100002026');
define('SHOP_EMAIL', 'halo@kopikita.test');
define('SHOP_HOURS', [
    'Senin – Jumat' => '07.00 – 22.00',
    'Sabtu – Minggu' => '08.00 – 23.00',
]);

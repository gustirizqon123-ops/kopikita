<?php
/**
 * Layout sidebar Cashier System – memakai kerangka admin-header.php
 * dengan menu khusus kasir.
 */
$panel = 'cashier';
$activeCount = (int) db()->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending','confirmed','preparing','ready')")->fetchColumn();
$navItems = [
    ['label' => 'Kasir'],
    ['href' => 'cashier/index.php', 'icon' => 'dashboard', 'text' => 'Dashboard Kasir', 'exact' => true],
    ['href' => 'cashier/pos.php', 'icon' => 'pos', 'text' => 'Transaksi Baru (POS)', 'exact' => true],
    ['href' => 'cashier/orders/', 'icon' => 'receipt', 'text' => 'Pesanan Masuk', 'count' => $activeCount],
    ['href' => 'cashier/transactions/', 'icon' => 'history', 'text' => 'Riwayat Transaksi'],
];
if (user_role() === 'admin') {
    $navItems[] = ['label' => 'Admin'];
    $navItems[] = ['href' => 'admin/index.php', 'icon' => 'dashboard', 'text' => 'Kembali ke Admin', 'exact' => true];
}
require __DIR__ . '/admin-header.php';

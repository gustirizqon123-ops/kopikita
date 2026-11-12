<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/components.php';
// Pelanggan yang sudah login diarahkan ke katalog area pelanggan
if (($u = current_user()) && $u['role'] === 'customer') {
    redirect('customer/menu.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
}
render_menu_page(false);

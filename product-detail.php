<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/components.php';
if (($u = current_user()) && $u['role'] === 'customer') {
    redirect('customer/product-detail.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
}
render_product_detail(false);

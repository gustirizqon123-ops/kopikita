<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin-catalog.php';
require_role(['admin']);
$st = db()->prepare('SELECT * FROM products WHERE id = ?');
$st->execute([(int) ($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) {
    abort(404);
}
handle_product_form($p);

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin-catalog.php';
require_role(['admin']);
$st = db()->prepare('SELECT * FROM categories WHERE id = ?');
$st->execute([(int) ($_GET['id'] ?? 0)]);
$cat = $st->fetch();
if (!$cat) {
    abort(404);
}
handle_category_form($cat);

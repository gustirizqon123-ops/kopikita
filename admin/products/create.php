<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin-catalog.php';
require_role(['admin']);
handle_product_form(null);

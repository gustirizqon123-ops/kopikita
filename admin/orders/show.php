<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/order-view.php';
require_role(['admin']);
render_order_detail('admin');

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/order-view.php';
require_role(['cashier', 'admin']);
render_order_detail('cashier');

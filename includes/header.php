<?php
/**
 * Head + navbar website publik/pelanggan.
 * Variabel opsional: $pageTitle, $pageDesc, $bodyClass, $hideNav
 */
$user = current_user();
$isCustomer = $user && $user['role'] === 'customer';
$pageTitle = isset($pageTitle) ? $pageTitle . ' · ' . APP_NAME : APP_NAME . ' – ' . APP_SLOGAN;
$menuUrl = $isCustomer ? 'customer/menu.php' : 'menu.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc ?? 'KopiKita – coffee shop modern. ' . APP_SLOGAN) ?>">
    <meta name="theme-color" content="#1A0F0A">
    <link rel="icon" href="<?= e(url('assets/images/ui/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <script>
        window.KK = {
            base: <?= json_encode(BASE_URL) ?>,
            csrf: <?= json_encode(csrf_token()) ?>,
            loggedIn: <?= $user ? 'true' : 'false' ?>,
            role: <?= json_encode($user['role'] ?? null) ?>,
            loginUrl: <?= json_encode(url('login.php')) ?>
        };
    </script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php if (empty($hideNav)): ?>
<header class="site-header">
    <div class="container">
        <nav class="navbar" aria-label="Navigasi utama">
            <a href="<?= e(url('index.php')) ?>" class="brand" aria-label="KopiKita beranda">
                <span class="brand-logo">K</span>
                <span class="brand-text">
                    <span class="brand-name">KopiKita</span>
                    <span class="brand-est">Est. <?= e(APP_EST) ?></span>
                </span>
            </a>

            <ul class="nav-links" id="navLinks">
                <li><a href="<?= e(url('index.php')) ?>" class="<?= nav_active('index.php') ?>">Home</a></li>
                <li><a href="<?= e(url($menuUrl)) ?>" class="<?= nav_active('menu.php') ?: nav_active('customer/menu.php') ?: nav_active('product-detail.php') ?>">Menu</a></li>
                <li><a href="<?= e(url('gallery.php')) ?>" class="<?= nav_active('gallery.php') ?>">Gallery</a></li>
                <li><a href="<?= e(url('about.php')) ?>" class="<?= nav_active('about.php') ?>">About</a></li>
                <li><a href="<?= e(url('contact.php')) ?>" class="<?= nav_active('contact.php') ?>">Contact</a></li>
            </ul>

            <div class="nav-actions">
                <button type="button" class="icon-btn" data-open-search aria-label="Cari menu"><?= icon('search') ?></button>
                <a href="<?= e(url($isCustomer ? 'customer/cart.php' : 'login.php')) ?>" class="icon-btn" aria-label="Keranjang">
                    <?= icon('cart') ?>
                    <span class="cart-badge" id="cartBadge" <?= $isCustomer && cart_count() > 0 ? '' : 'hidden' ?>><?= $isCustomer ? cart_count() : 0 ?></span>
                </a>
                <?php if ($user): ?>
                    <div class="user-menu" id="userMenu">
                        <button type="button" class="user-menu-btn" aria-haspopup="true" aria-expanded="false">
                            <span class="avatar"><?= e(initials($user['name'])) ?></span>
                            <span class="uname"><?= e(explode(' ', $user['name'])[0]) ?></span>
                        </button>
                        <div class="user-dropdown" role="menu">
                            <div class="dd-head"><strong><?= e($user['name']) ?></strong><small><?= e(ROLE_LABELS[$user['role']]) ?></small></div>
                            <?php if ($isCustomer): ?>
                                <a href="<?= e(url('customer/index.php')) ?>">Beranda Saya</a>
                                <a href="<?= e(url('customer/orders.php')) ?>">Pesanan Saya</a>
                                <a href="<?= e(url('customer/profile.php')) ?>">Profil Saya</a>
                            <?php else: ?>
                                <a href="<?= e(url(home_for_role($user['role']))) ?>">Buka Dashboard</a>
                            <?php endif; ?>
                            <form action="<?= e(url('logout.php')) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit">Logout</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= e(url('login.php')) ?>" class="btn btn-cream btn-sm btn-signin"><span class="txt">Sign In</span> <span aria-hidden="true">›</span></a>
                <?php endif; ?>
                <button type="button" class="icon-btn nav-toggle" id="navToggle" aria-label="Buka menu" aria-controls="navLinks" aria-expanded="false"><?= icon('menu') ?></button>
            </div>
        </nav>
    </div>
</header>

<div class="search-overlay" id="searchOverlay" role="dialog" aria-label="Pencarian menu">
    <button type="button" class="icon-btn search-close" data-close-search aria-label="Tutup"><?= icon('close') ?></button>
    <form action="<?= e(url($menuUrl)) ?>" method="get">
        <input type="search" name="q" class="form-control" placeholder="Cari kopi, matcha, croissant…" aria-label="Kata kunci">
        <button class="btn btn-primary" type="submit">Cari</button>
    </form>
</div>
<?php endif; ?>

<div class="container flash-wrap" id="flashWrap"><?= render_flashes() ?></div>
<main id="main">

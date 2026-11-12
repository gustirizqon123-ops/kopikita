<?php
/**
 * Layout sidebar Admin Dashboard (juga dipakai cashier-header.php).
 * Variabel: $pageTitle, $panel ('admin'|'cashier'), $navItems (opsional), $extraHead
 */
$user = current_user();
$panel = $panel ?? 'admin';
$pendingCount = (int) db()->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending')")->fetchColumn();

if (!isset($navItems)) {
    $navItems = [
        ['label' => 'Utama'],
        ['href' => 'admin/index.php', 'icon' => 'dashboard', 'text' => 'Dashboard', 'exact' => true],
        ['label' => 'Katalog'],
        ['href' => 'admin/categories/', 'icon' => 'tag', 'text' => 'Kategori'],
        ['href' => 'admin/products/', 'icon' => 'box', 'text' => 'Produk & Stok'],
        ['label' => 'Operasional'],
        ['href' => 'admin/orders/', 'icon' => 'receipt', 'text' => 'Pesanan', 'count' => $pendingCount],
        ['href' => 'admin/customers/', 'icon' => 'users', 'text' => 'Pelanggan'],
        ['href' => 'admin/reports/', 'icon' => 'chart', 'text' => 'Laporan Penjualan'],
        ['label' => 'Konten & Akun'],
        ['href' => 'admin/gallery/', 'icon' => 'image', 'text' => 'Galeri'],
        ['href' => 'admin/newsletter/', 'icon' => 'mail', 'text' => 'Newsletter'],
        ['href' => 'admin/users/', 'icon' => 'user', 'text' => 'Pengguna'],
        ['label' => 'Pintasan'],
        ['href' => 'cashier/index.php', 'icon' => 'pos', 'text' => 'Cashier System'],
        ['href' => 'index.php', 'icon' => 'home', 'text' => 'Lihat Website'],
    ];
}
$cur = current_path();
$isActive = function (array $it) use ($cur): bool {
    $target = url($it['href']);
    if (!empty($it['exact'])) {
        return $cur === $target;
    }
    return strpos($cur, rtrim($target, '/')) === 0 && !preg_match('#/index\.php$#', $it['href']);
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Dashboard') . ' · ' . ($panel === 'cashier' ? 'Kasir' : 'Admin') . ' KopiKita') ?></title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="<?= e(url('assets/images/ui/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script>window.KK = { base: <?= json_encode(BASE_URL) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
    <?= $extraHead ?? '' ?>
</head>
<body class="panel-<?= e($panel) ?>">
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a href="<?= e(url($panel === 'cashier' ? 'cashier/index.php' : 'admin/index.php')) ?>" class="sidebar-brand">
            <span class="logo">K</span>
            <span><strong>KopiKita</strong><small><?= $panel === 'cashier' ? 'Cashier System' : 'Admin Dashboard' ?></small></span>
        </a>
        <nav class="sidebar-nav" aria-label="Menu panel">
            <?php foreach ($navItems as $it): ?>
                <?php if (isset($it['label'])): ?>
                    <div class="label"><?= e($it['label']) ?></div>
                <?php else: ?>
                    <a href="<?= e(url($it['href'])) ?>" class="<?= $isActive($it) ? 'active' : '' ?>">
                        <?= icon($it['icon']) ?> <span><?= e($it['text']) ?></span>
                        <?php if (!empty($it['count'])): ?><span class="count"><?= (int) $it['count'] ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <form action="<?= e(url('logout.php')) ?>" method="post" class="mt-2">
                <?= csrf_field() ?>
                <button type="submit"><?= icon('logout') ?> <span>Logout</span></button>
            </form>
        </nav>
        <div class="sidebar-foot">
            <span class="avatar"><?= e(initials($user['name'])) ?></span>
            <span><strong><?= e($user['name']) ?></strong><small><?= e(ROLE_LABELS[$user['role']]) ?></small></span>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-controls="sidebar"><?= icon('menu') ?></button>
            <div>
                <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
                <?php if (!empty($breadcrumb)): ?><div class="crumb"><?= $breadcrumb ?></div><?php endif; ?>
            </div>
            <div class="spacer"></div>
            <span class="clock" id="clock"><?= e(tanggal(now())) ?></span>
        </header>
        <div class="content">
            <?= render_flashes() ?>

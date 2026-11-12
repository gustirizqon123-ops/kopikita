<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
$pdo = db();
[$from, $to] = date_range('-29 days');
$rep = sales_report($pdo, $from, $to);

$st = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_status <> 'cancelled' AND DATE(ordered_at) BETWEEN ? AND ?");
$st->execute([$from, $to]);
$orderCount = (int) $st->fetchColumn();
$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_available = 1')->fetchColumn();
$customerCount = (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE name <> 'Pelanggan Umum'")->fetchColumn();

$latest = $pdo->query("SELECT o.id, o.order_code, o.ordered_at, o.order_status, o.total_amount, c.name AS customer_name, s.payment_status
        FROM orders o JOIN customers c ON c.id = o.customer_id LEFT JOIN sales s ON s.order_id = o.id ORDER BY o.ordered_at DESC, o.id DESC LIMIT 6")->fetchAll();
$low = $pdo->query('SELECT id, name, stock, min_stock FROM products WHERE is_available = 1 AND stock <= min_stock ORDER BY stock ASC LIMIT 6')->fetchAll();

$pageTitle = 'Dashboard';
$extraScripts = ['https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js'];
$inlineScript = 'window.addEventListener("load",function(){var c=document.getElementById("salesChart");if(!c||!window.Chart)return;'
    . 'new Chart(c,{type:"line",data:{labels:' . json_encode($rep['chart']['labels']) . ',datasets:[{label:"Penjualan (Rp)",data:' . json_encode($rep['chart']['values'])
    . ',borderColor:"#E08A3C",backgroundColor:"rgba(224,138,60,.15)",fill:true,tension:.3,pointRadius:2}]},options:{responsive:true,maintainAspectRatio:false,'
    . 'plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return "Rp"+v.toLocaleString("id-ID")}}}}}});});';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="card mb-3"><div class="card-body">
    <form class="filter-bar" method="get">
        <div class="form-group"><label class="form-label" for="from">Dari</label><input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
        <div class="form-group"><label class="form-label" for="to">Sampai</label><input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>"></div>
        <button class="btn btn-dark btn-sm" type="submit">Terapkan</button>
        <a class="btn btn-outline btn-sm" href="<?= e(url('admin/index.php')) ?>">30 hari terakhir</a>
    </form>
</div></div>

<div class="stat-cards">
    <div class="stat-card"><div class="ico orange"><?= icon('cash') ?></div><div><small>Total penjualan</small><strong><?= e(rupiah($rep['summary']['omzet'])) ?></strong><small><?= (int) $rep['summary']['trx'] ?> transaksi selesai &amp; lunas</small></div></div>
    <div class="stat-card"><div class="ico blue"><?= icon('receipt') ?></div><div><small>Jumlah pesanan</small><strong><?= $orderCount ?></strong><small>tidak termasuk batal</small></div></div>
    <div class="stat-card"><div class="ico green"><?= icon('box') ?></div><div><small>Produk aktif</small><strong><?= $productCount ?></strong></div></div>
    <div class="stat-card"><div class="ico brown"><?= icon('users') ?></div><div><small>Pelanggan</small><strong><?= $customerCount ?></strong></div></div>
</div>

<div class="grid-3 mb-3">
    <div class="card"><div class="card-header"><h2>Grafik Penjualan</h2></div><div class="card-body"><div class="chart-box"><canvas id="salesChart" aria-label="Grafik penjualan harian"></canvas></div></div></div>
    <div class="card"><div class="card-header"><h2>Produk Terlaris</h2></div><div class="card-body">
        <?php if (!$rep['top']): ?><p class="text-muted">Belum ada data.</p><?php endif; ?>
        <ul class="list-plain">
        <?php foreach ($rep['top'] as $i => $t): ?>
            <li style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="rank"><?= $i + 1 ?></span><strong><?= e($t['product_name']) ?></strong>
                <div class="text-muted" style="margin-left:36px"><?= (int) $t['jumlah_terjual'] ?> terjual · <?= e(rupiah($t['nilai_penjualan'])) ?></div></li>
        <?php endforeach; ?>
        </ul>
    </div></div>
</div>

<div class="grid-3">
    <div class="card"><div class="card-header"><h2>Pesanan Terbaru</h2><a class="btn btn-outline btn-sm" href="<?= e(url('admin/orders/index.php')) ?>">Semua</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Kode</th><th>Pelanggan</th><th>Status</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            <?php foreach ($latest as $r): ?>
                <tr><td><a href="<?= e(url('admin/orders/show.php?id=' . $r['id'])) ?>"><strong><?= e($r['order_code']) ?></strong></a><div class="text-muted"><?= e(tanggal($r['ordered_at'])) ?></div></td>
                    <td><?= e($r['customer_name']) ?></td><td><?= status_badge($r['order_status']) ?> <?= payment_badge($r['payment_status']) ?></td>
                    <td class="text-right nowrap"><?= e(rupiah($r['total_amount'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$latest): ?><tr><td colspan="4" class="table-empty">Belum ada pesanan.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    <div class="card"><div class="card-header"><h2>Stok Menipis</h2><a class="btn btn-outline btn-sm" href="<?= e(url('admin/products/index.php?low=1')) ?>">Kelola</a></div><div class="card-body">
        <?php if (!$low): ?><p class="text-muted">Semua stok aman.</p><?php endif; ?>
        <ul class="list-plain">
        <?php foreach ($low as $p): ?>
            <li style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <a href="<?= e(url('admin/products/edit.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a>
                <span class="badge <?= (int) $p['stock'] === 0 ? 'badge-out' : 'badge-low' ?>"><?= (int) $p['stock'] ?> / min <?= (int) $p['min_stock'] ?></span></li>
        <?php endforeach; ?>
        </ul>
    </div></div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>

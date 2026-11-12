<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
[$from, $to] = date_range('-29 days');
$rep = sales_report(db(), $from, $to);
$qs = http_build_query(['from' => $from, 'to' => $to]);
$avg = $rep['summary']['trx'] > 0 ? $rep['summary']['omzet'] / $rep['summary']['trx'] : 0;

$pageTitle = 'Laporan Penjualan';
$extraScripts = ['https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js'];
$inlineScript = 'window.addEventListener("load",function(){var c=document.getElementById("repChart");if(!c||!window.Chart)return;'
    . 'new Chart(c,{type:"bar",data:{labels:' . json_encode($rep['chart']['labels']) . ',datasets:[{label:"Penjualan (Rp)",data:' . json_encode($rep['chart']['values'])
    . ',backgroundColor:"#E08A3C",borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},'
    . 'scales:{y:{beginAtZero:true,ticks:{callback:function(v){return "Rp"+v.toLocaleString("id-ID")}}}}}});});';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card mb-3"><div class="card-body">
    <form class="filter-bar" method="get">
        <div class="form-group"><label class="form-label" for="from">Dari</label><input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
        <div class="form-group"><label class="form-label" for="to">Sampai</label><input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>"></div>
        <button class="btn btn-dark btn-sm" type="submit">Tampilkan</button>
        <a class="btn btn-success btn-sm" href="<?= e(url('admin/reports/export-excel.php?' . $qs)) ?>"><?= icon('download') ?> Excel</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('admin/reports/export-excel.php?' . $qs . '&format=csv')) ?>"><?= icon('download') ?> CSV</a>
        <a class="btn btn-warning btn-sm" target="_blank" href="<?= e(url('admin/reports/export-pdf.php?' . $qs)) ?>"><?= icon('print') ?> PDF</a>
    </form>
    <p class="text-muted mt-2 mb-0">Hanya pesanan berstatus Completed dengan pembayaran Lunas yang dihitung.</p>
</div></div>

<div class="stat-cards">
    <div class="stat-card"><div class="ico orange"><?= icon('cash') ?></div><div><small>Omzet</small><strong><?= e(rupiah($rep['summary']['omzet'])) ?></strong></div></div>
    <div class="stat-card"><div class="ico blue"><?= icon('receipt') ?></div><div><small>Jumlah transaksi</small><strong><?= (int) $rep['summary']['trx'] ?></strong></div></div>
    <div class="stat-card"><div class="ico green"><?= icon('chart') ?></div><div><small>Rata-rata per transaksi</small><strong><?= e(rupiah($avg)) ?></strong></div></div>
    <div class="stat-card"><div class="ico yellow"><?= icon('tag') ?></div><div><small>Total diskon</small><strong><?= e(rupiah($rep['summary']['diskon'])) ?></strong></div></div>
</div>

<div class="card mb-3"><div class="card-header"><h2>Grafik Penjualan Harian</h2></div><div class="card-body"><div class="chart-box"><canvas id="repChart"></canvas></div></div></div>

<div class="grid-2 mb-3">
    <div class="card"><div class="card-header"><h2>5 Produk Terlaris</h2></div><div class="table-wrap"><table class="table">
        <thead><tr><th>Produk</th><th class="text-right">Terjual</th><th class="text-right">Nilai</th></tr></thead><tbody>
        <?php foreach ($rep['top'] as $t): ?><tr><td><?= e($t['product_name']) ?></td><td class="text-right"><?= (int) $t['jumlah_terjual'] ?></td><td class="text-right nowrap"><?= e(rupiah($t['nilai_penjualan'])) ?></td></tr><?php endforeach; ?>
        <?php if (!$rep['top']): ?><tr><td colspan="3" class="table-empty">Belum ada data.</td></tr><?php endif; ?></tbody></table></div></div>
    <div class="card"><div class="card-header"><h2>Metode Pembayaran</h2></div><div class="table-wrap"><table class="table">
        <thead><tr><th>Metode</th><th class="text-right">Transaksi</th><th class="text-right">Total</th></tr></thead><tbody>
        <?php foreach ($rep['methods'] as $m): ?><tr><td><?= e(PAYMENT_METHODS[$m['payment_method']] ?? $m['payment_method']) ?></td><td class="text-right"><?= (int) $m['trx'] ?></td><td class="text-right nowrap"><?= e(rupiah($m['total'])) ?></td></tr><?php endforeach; ?>
        <?php if (!$rep['methods']): ?><tr><td colspan="3" class="table-empty">Belum ada data.</td></tr><?php endif; ?></tbody></table></div></div>
</div>

<div class="card"><div class="card-header"><h2>Rincian Transaksi (<?= count($rep['rows']) ?>)</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Tanggal</th><th>Kode</th><th>Invoice</th><th>Pelanggan</th><th>Channel</th><th>Metode</th><th class="text-right">Total</th></tr></thead><tbody>
    <?php foreach ($rep['rows'] as $r): ?>
        <tr><td class="nowrap"><?= e(tanggal($r['ordered_at'])) ?></td><td><?= e($r['order_code']) ?></td><td><?= e($r['invoice_number']) ?></td><td><?= e($r['customer_name']) ?></td>
            <td><?= e(ORDER_CHANNELS[$r['order_channel']]) ?></td><td><?= e(PAYMENT_METHODS[$r['payment_method']] ?? '-') ?></td><td class="text-right nowrap"><?= e(rupiah($r['total_amount'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rep['rows']): ?><tr><td colspan="7" class="table-empty">Tidak ada transaksi pada rentang ini.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

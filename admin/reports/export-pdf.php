<?php
// Laporan versi cetak (A4). Gunakan "Cetak" → pilih "Save as PDF" di browser.
require_once __DIR__ . '/../../includes/auth.php';
$user = require_role(['admin']);
[$from, $to] = date_range('-29 days');
$rep = sales_report(db(), $from, $to);
$avg = $rep['summary']['trx'] > 0 ? $rep['summary']['omzet'] / $rep['summary']['trx'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan <?= e($from) ?> – <?= e($to) ?></title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
    <style>@media print { @page { size: A4; margin: 12mm; } }</style>
</head>
<body>
<div class="print-toolbar">
    <strong>Laporan Penjualan</strong><span class="spacer"></span>
    <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="ghost" href="<?= e(url('admin/reports/index.php?from=' . $from . '&to=' . $to)) ?>">Kembali</a>
</div>
<div class="report">
    <div class="report-head">
        <div class="brand"><div class="logo">K</div><div><h1>KopiKita</h1><small><?= e(APP_SLOGAN) ?></small></div></div>
        <div style="text-align:right"><strong>Laporan Penjualan</strong><br><small>Periode <?= e(tanggal($from, false)) ?> – <?= e(tanggal($to, false)) ?></small></div>
    </div>
    <div class="summary">
        <div><small>Omzet</small><strong><?= e(rupiah($rep['summary']['omzet'])) ?></strong></div>
        <div><small>Transaksi</small><strong><?= (int) $rep['summary']['trx'] ?></strong></div>
        <div><small>Rata-rata/transaksi</small><strong><?= e(rupiah($avg)) ?></strong></div>
        <div><small>Total diskon</small><strong><?= e(rupiah($rep['summary']['diskon'])) ?></strong></div>
    </div>

    <h2>Penjualan per Hari</h2>
    <table><thead><tr><th>Tanggal</th><th class="num">Transaksi</th><th class="num">Total</th></tr></thead><tbody>
    <?php foreach ($rep['daily'] as $d): ?><tr><td><?= e(tanggal($d['tanggal'], false)) ?></td><td class="num"><?= (int) $d['jumlah_transaksi'] ?></td><td class="num"><?= e(rupiah($d['total_penjualan'])) ?></td></tr><?php endforeach; ?>
    <?php if (!$rep['daily']): ?><tr><td colspan="3">Tidak ada data.</td></tr><?php endif; ?></tbody></table>

    <h2>5 Produk Terlaris</h2>
    <table><thead><tr><th>Produk</th><th class="num">Terjual</th><th class="num">Nilai</th></tr></thead><tbody>
    <?php foreach ($rep['top'] as $t): ?><tr><td><?= e($t['product_name']) ?></td><td class="num"><?= (int) $t['jumlah_terjual'] ?></td><td class="num"><?= e(rupiah($t['nilai_penjualan'])) ?></td></tr><?php endforeach; ?>
    <?php if (!$rep['top']): ?><tr><td colspan="3">Tidak ada data.</td></tr><?php endif; ?></tbody></table>

    <h2>Rincian Transaksi</h2>
    <table><thead><tr><th>Tanggal</th><th>Kode</th><th>Pelanggan</th><th>Metode</th><th class="num">Total</th></tr></thead><tbody>
    <?php foreach ($rep['rows'] as $r): ?><tr><td><?= e(tanggal($r['ordered_at'])) ?></td><td><?= e($r['order_code']) ?></td><td><?= e($r['customer_name']) ?></td>
        <td><?= e(PAYMENT_METHODS[$r['payment_method']] ?? '-') ?></td><td class="num"><?= e(rupiah($r['total_amount'])) ?></td></tr><?php endforeach; ?>
    <?php if (!$rep['rows']): ?><tr><td colspan="5">Tidak ada transaksi.</td></tr><?php endif; ?></tbody>
    <tfoot><tr><td colspan="4">TOTAL</td><td class="num"><?= e(rupiah($rep['summary']['omzet'])) ?></td></tr></tfoot></table>

    <div class="report-foot"><div>Dicetak <?= e(tanggal(now())) ?> oleh <?= e($user['name']) ?></div>
        <div class="sign">Mengetahui,<div class="line">Pemilik KopiKita</div></div></div>
</div>
</body>
</html>

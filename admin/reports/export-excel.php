<?php
// Ekspor laporan penjualan: .xls (tabel HTML yang dibuka Excel) atau CSV (?format=csv).
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
[$from, $to] = date_range('-29 days');
$rep = sales_report(db(), $from, $to);
$csv = ($_GET['format'] ?? '') === 'csv';
$file = 'laporan-penjualan-' . $from . '_sd_' . $to;

/** Netralkan sel yang diawali = + - @ agar tidak dibaca sebagai rumus Excel. */
$safe = function ($v): string {
    $v = (string) $v;
    return $v !== '' && strpos('=+-@', $v[0]) !== false ? "'" . $v : $v;
};
$head = ['Tanggal', 'Kode Pesanan', 'Invoice', 'Pelanggan', 'Channel', 'Jenis', 'Metode Bayar', 'Subtotal', 'Diskon', 'Total'];
$line = fn($r) => [$r['ordered_at'], $r['order_code'], $r['invoice_number'], $safe($r['customer_name']), ORDER_CHANNELS[$r['order_channel']],
    ORDER_TYPES[$r['order_type']], PAYMENT_METHODS[$r['payment_method']] ?? $r['payment_method'], (float) $r['subtotal'], (float) $r['discount_amount'], (float) $r['total_amount']];

if ($csv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Laporan Penjualan KopiKita', $from . ' s/d ' . $to]);
    fputcsv($out, $head);
    foreach ($rep['rows'] as $r) {
        fputcsv($out, $line($r));
    }
    fputcsv($out, ['', '', '', '', '', '', 'TOTAL', '', (float) $rep['summary']['diskon'], (float) $rep['summary']['omzet']]);
    fclose($out);
    exit;
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $file . '.xls"');
echo "\xEF\xBB\xBF";
?>
<html><head><meta charset="UTF-8"></head><body>
<h3>Laporan Penjualan KopiKita</h3>
<p>Periode: <?= e($from) ?> s/d <?= e($to) ?> · Transaksi: <?= (int) $rep['summary']['trx'] ?> · Omzet: <?= (float) $rep['summary']['omzet'] ?></p>
<table border="1">
    <tr><?php foreach ($head as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
    <?php foreach ($rep['rows'] as $r): ?>
        <tr><?php foreach ($line($r) as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    <tr><td colspan="8"><b>TOTAL</b></td><td><b><?= (float) $rep['summary']['diskon'] ?></b></td><td><b><?= (float) $rep['summary']['omzet'] ?></b></td></tr>
</table>
<br><h4>Produk Terlaris</h4>
<table border="1"><tr><th>Produk</th><th>Terjual</th><th>Nilai</th></tr>
<?php foreach ($rep['top'] as $t): ?><tr><td><?= e($t['product_name']) ?></td><td><?= (int) $t['jumlah_terjual'] ?></td><td><?= (float) $t['nilai_penjualan'] ?></td></tr><?php endforeach; ?>
</table>
</body></html>

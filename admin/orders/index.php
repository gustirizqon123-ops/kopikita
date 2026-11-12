<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();

$status = (string) ($_GET['status'] ?? '');
$channel = (string) ($_GET['channel'] ?? '');
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$validDate = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);

$where = ['1=1'];
$args = [];
if (isset(ORDER_STATUSES[$status])) { $where[] = 'o.order_status = ?'; $args[] = $status; } else { $status = ''; }
if (isset(ORDER_CHANNELS[$channel])) { $where[] = 'o.order_channel = ?'; $args[] = $channel; } else { $channel = ''; }
if ($validDate($from)) { $where[] = 'DATE(o.ordered_at) >= ?'; $args[] = $from; } else { $from = ''; }
if ($validDate($to)) { $where[] = 'DATE(o.ordered_at) <= ?'; $args[] = $to; } else { $to = ''; }
if ($q !== '') { $where[] = '(o.order_code LIKE ? OR c.name LIKE ?)'; $like = '%' . addcslashes($q, '%_\\') . '%'; array_push($args, $like, $like); }
$cond = implode(' AND ', $where);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN customers c ON c.id = o.customer_id WHERE $cond");
$cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 15);
$st = $pdo->prepare("SELECT o.*, c.name AS customer_name, s.payment_status FROM orders o JOIN customers c ON c.id = o.customer_id
        LEFT JOIN sales s ON s.order_id = o.id WHERE $cond ORDER BY o.ordered_at DESC, o.id DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

$pageTitle = 'Kelola Pesanan';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card mb-3"><div class="card-body">
    <form class="filter-bar" method="get">
        <div class="form-group"><label class="form-label" for="q">Cari</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Kode / pelanggan"></div>
        <div class="form-group"><label class="form-label" for="status">Status</label><select class="form-control" id="status" name="status"><option value="">Semua</option>
            <?php foreach (ORDER_STATUSES as $k => $l): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label" for="channel">Channel</label><select class="form-control" id="channel" name="channel"><option value="">Semua</option>
            <?php foreach (ORDER_CHANNELS as $k => $l): ?><option value="<?= e($k) ?>" <?= $channel === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label" for="from">Dari</label><input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
        <div class="form-group"><label class="form-label" for="to">Sampai</label><input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>"></div>
        <button class="btn btn-dark btn-sm" type="submit">Filter</button>
        <a class="btn btn-outline btn-sm" href="<?= e(url('admin/orders/index.php')) ?>">Reset</a>
    </form>
</div></div>
<div class="card">
    <div class="card-header"><h2>Pesanan (<?= (int) $pg['total'] ?>)</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kode</th><th>Waktu</th><th>Pelanggan</th><th>Channel</th><th>Status</th><th>Bayar</th><th class="text-right">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><strong><?= e($r['order_code']) ?></strong></td><td class="nowrap"><?= e(tanggal($r['ordered_at'])) ?></td><td><?= e($r['customer_name']) ?></td>
                <td><span class="badge badge-<?= e($r['order_channel']) ?>"><?= e(ORDER_CHANNELS[$r['order_channel']]) ?></span></td>
                <td><?= status_badge($r['order_status']) ?></td><td><?= payment_badge($r['payment_status']) ?></td>
                <td class="text-right nowrap"><?= e(rupiah($r['total_amount'])) ?></td>
                <td><a class="btn btn-outline btn-sm" href="<?= e(url('admin/orders/show.php?id=' . $r['id'])) ?>">Detail</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Tidak ada pesanan.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?= pagination_links($pg) ?>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

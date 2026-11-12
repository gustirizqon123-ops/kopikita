<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();

if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = ?')->execute([$id]);
        flash('success', 'Email dihapus.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $pdo->prepare('UPDATE newsletter_subscribers SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    }
    redirect('admin/newsletter/index.php');
}

// Ekspor CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Status', 'Tanggal Daftar']);
    foreach ($pdo->query('SELECT email, is_active, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC') as $r) {
        $mail = $r['email'] !== '' && strpos('=+-@', $r['email'][0]) !== false ? "'" . $r['email'] : $r['email'];
        fputcsv($out, [$mail, $r['is_active'] ? 'Aktif' : 'Nonaktif', $r['subscribed_at']]);
    }
    exit;
}

$pg = paginate((int) $pdo->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn(), 20);
$rows = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}")->fetchAll();
$pageTitle = 'Newsletter';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card">
    <div class="card-header"><h2>Pendaftar (<?= (int) $pg['total'] ?>)</h2>
        <a class="btn btn-success btn-sm" href="<?= e(url('admin/newsletter/index.php?export=1')) ?>"><?= icon('download') ?> Ekspor CSV</a></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Email</th><th>Status</th><th>Terdaftar</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= e($r['email']) ?></td><td><?= $r['is_active'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-off">Nonaktif</span>' ?></td>
                <td><?= e(tanggal($r['subscribed_at'])) ?></td>
                <td><div class="actions">
                    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                    <form method="post" class="inline-form" data-confirm="Hapus email ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                </div></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="4" class="table-empty">Belum ada pendaftar.</td></tr><?php endif; ?>
        </tbody></table></div>
</div>
<?= pagination_links($pg) ?>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

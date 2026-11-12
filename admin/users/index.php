<?php
require_once __DIR__ . '/../../includes/auth.php';
$me = require_role(['admin']);
$pdo = db();
$errors = [];
$form = ['name' => '', 'username' => '', 'email' => ''];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $me['id']) {
            flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        } else {
            $pdo->prepare('UPDATE users SET is_active = 1 - is_active, updated_at = ? WHERE id = ?')->execute([now(), $id]);
            flash('success', 'Status akun diperbarui.');
        }
        redirect('admin/users/index.php');
    }
    if ($action === 'create') {
        $form = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'username' => strtolower(trim((string) ($_POST['username'] ?? ''))),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
        ];
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 100) { $errors[] = 'Nama 3–100 karakter.'; }
        if (!preg_match('/^[a-z0-9_]{4,50}$/', $form['username'])) { $errors[] = 'Username 4–50 karakter: huruf kecil, angka, atau _.'; }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 150) { $errors[] = 'Format email tidak valid.'; }
        if (strlen($password) < 8) { $errors[] = 'Password minimal 8 karakter.'; }
        if (!$errors) {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE username = ? OR email = ?');
            $st->execute([$form['username'], $form['email']]);
            if ($st->fetch()) { $errors[] = 'Username atau email sudah terdaftar.'; }
        }
        if (!$errors) {
            $pdo->prepare('INSERT INTO users (name, username, email, password, role, is_active, created_at, updated_at) VALUES (?,?,?,?,"cashier",1,?,?)')
                ->execute([$form['name'], $form['username'], $form['email'], password_hash($password, PASSWORD_DEFAULT), now(), now()]);
            flash('success', 'Akun kasir ditambahkan.');
            redirect('admin/users/index.php');
        }
    }
}

$role = (string) ($_GET['role'] ?? '');
$where = isset(ROLE_LABELS[$role]) ? 'WHERE role = ?' : '';
$st = $pdo->prepare("SELECT * FROM users $where ORDER BY FIELD(role,'admin','cashier','customer'), name");
$st->execute($where ? [$role] : []);
$rows = $st->fetchAll();

$pageTitle = 'Kelola Pengguna';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="grid-3">
    <div class="card">
        <div class="card-header"><h2>Akun (<?= count($rows) ?>)</h2>
            <div class="actions"><a class="btn btn-outline btn-sm" href="?">Semua</a>
            <?php foreach (ROLE_LABELS as $k => $l): ?><a class="btn btn-outline btn-sm" href="?role=<?= e($k) ?>"><?= e($l) ?></a><?php endforeach; ?></div></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Login terakhir</th><th>Status</th><th></th></tr></thead><tbody>
            <?php foreach ($rows as $u): ?>
                <tr><td><strong><?= e($u['name']) ?></strong><div class="text-muted"><?= e($u['email']) ?></div></td><td><?= e($u['username']) ?></td>
                    <td><span class="badge badge-role-<?= e($u['role']) ?>"><?= e(ROLE_LABELS[$u['role']]) ?></span></td>
                    <td><?= e(tanggal($u['last_login_at'])) ?></td>
                    <td><?= $u['is_active'] ? '<span class="badge badge-ok">Aktif</span>' : '<span class="badge badge-off">Nonaktif</span>' ?></td>
                    <td><?php if ((int) $u['id'] !== (int) $me['id']): ?>
                        <form method="post" class="inline-form" data-confirm="Ubah status akun ini?"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button class="btn btn-outline btn-sm" type="submit"><?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                        <?php else: ?><span class="text-muted">Anda</span><?php endif; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
    </div>
    <div class="card"><div class="card-header"><h2>Tambah Akun Kasir</h2></div><div class="card-body">
        <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <div class="form-group"><label class="form-label" for="name">Nama lengkap</label><input class="form-control" id="name" name="name" required value="<?= e($form['name']) ?>"></div>
            <div class="form-group"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" required value="<?= e($form['username']) ?>"></div>
            <div class="form-group"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" required value="<?= e($form['email']) ?>"></div>
            <div class="form-group"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" required minlength="8" autocomplete="new-password"><div class="form-hint">Minimal 8 karakter.</div></div>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('plus') ?> Tambah Kasir</button>
        </form>
    </div></div>
</div>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

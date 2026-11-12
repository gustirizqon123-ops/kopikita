<?php
require_once __DIR__ . '/includes/auth.php';

if ($u = current_user()) {
    redirect(home_for_role($u['role']));
}

$errors = [];
$data = ['name' => '', 'username' => '', 'email' => '', 'phone' => ''];

if (is_post()) {
    verify_csrf();
    foreach ($data as $k => $_) {
        $data[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $data['username'] = strtolower($data['username']);
    $data['email'] = strtolower($data['email']);
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($data['name']) < 3 || mb_strlen($data['name']) > 100) {
        $errors['name'] = 'Nama lengkap 3–100 karakter.';
    }
    if (!preg_match('/^[a-z0-9_]{4,50}$/', $data['username'])) {
        $errors['username'] = 'Username 4–50 karakter: huruf kecil, angka, atau _.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 150) {
        $errors['email'] = 'Format email tidak valid.';
    }
    if ($data['phone'] !== '' && !preg_match('/^[0-9+\-\s]{8,25}$/', $data['phone'])) {
        $errors['phone'] = 'Nomor telepon tidak valid.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        $st = db()->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $st->execute([$data['username'], $data['email']]);
        foreach ($st->fetchAll() as $row) {
            if ($row['username'] === $data['username']) {
                $errors['username'] = 'Username sudah dipakai.';
            }
            if ($row['email'] === $data['email']) {
                $errors['email'] = 'Email sudah terdaftar.';
            }
        }
    }

    if (!$errors) {
        $pdo = db();
        try {
            // users + customers dalam satu transaksi (ERD aturan no. 3)
            $pdo->beginTransaction();
            $now = now();
            $pdo->prepare('INSERT INTO users (name, username, email, password, role, is_active, created_at, updated_at) VALUES (?,?,?,?,"customer",1,?,?)')
                ->execute([$data['name'], $data['username'], $data['email'], password_hash($password, PASSWORD_DEFAULT), $now, $now]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO customers (user_id, name, phone, email, created_at, updated_at) VALUES (?,?,?,?,?,?)')
                ->execute([$userId, $data['name'], $data['phone'] ?: null, $data['email'], $now, $now]);
            $pdo->commit();
            flash('success', 'Registrasi berhasil! Silakan login dengan akun baru Anda.');
            redirect('login.php');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            $errors['general'] = $ex->getCode() === '23000' ? 'Username atau email sudah terdaftar.' : 'Registrasi gagal, silakan coba lagi.';
        }
    }
}

$pageTitle = 'Registrasi';
$hideNav = true;
$hideFooter = true;
require __DIR__ . '/includes/header.php';
$err = fn($k) => isset($errors[$k]) ? '<div class="form-error">' . e($errors[$k]) . '</div>' : '';
$inv = fn($k) => isset($errors[$k]) ? ' is-invalid' : '';
?>
<div class="auth-wrap">
    <div class="auth-visual">
        <a href="<?= e(url('index.php')) ?>" class="brand">
            <span class="brand-logo">K</span>
            <span class="brand-text"><span class="brand-name">KopiKita</span><span class="brand-est">Est. <?= e(APP_EST) ?></span></span>
        </a>
        <div>
            <h2>Jadi bagian dari <em>keluarga</em> KopiKita.</h2>
            <p class="text-cream">Buat akun untuk memesan lebih cepat dan menyimpan riwayat pesanan Anda.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <h1>Create <em>account</em></h1>
            <p class="sub">Registrasi khusus pelanggan.</p>
            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert"><?= e($errors['general'] ?? 'Periksa kembali data yang Anda isi.') ?><button type="button" class="alert-close" aria-label="Tutup">&times;</button></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('register.php')) ?>" data-validate novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input type="text" class="form-control<?= $inv('name') ?>" id="name" name="name" required minlength="3" maxlength="100" value="<?= e($data['name']) ?>" autocomplete="name">
                    <?= $err('name') ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control<?= $inv('username') ?>" id="username" name="username" required pattern="[a-z0-9_]{4,50}" maxlength="50" value="<?= e($data['username']) ?>" autocomplete="username">
                        <?= $err('username') ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">No. HP <span class="text-muted">(opsional)</span></label>
                        <input type="tel" class="form-control<?= $inv('phone') ?>" id="phone" name="phone" maxlength="25" value="<?= e($data['phone']) ?>" autocomplete="tel">
                        <?= $err('phone') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control<?= $inv('email') ?>" id="email" name="email" required maxlength="150" value="<?= e($data['email']) ?>" autocomplete="email">
                    <?= $err('email') ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control<?= $inv('password') ?>" id="password" name="password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="toggle-pass" data-toggle-pass="password" aria-label="Tampilkan password"><?= icon('eye') ?></button>
                        </div>
                        <?= $err('password') ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Ulangi Password</label>
                        <input type="password" class="form-control<?= $inv('password_confirm') ?>" id="password_confirm" name="password_confirm" required data-match="password" autocomplete="new-password">
                        <?= $err('password_confirm') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Daftar <span class="arrow">›</span></button>
            </form>
            <p class="auth-foot">Sudah punya akun? <a href="<?= e(url('login.php')) ?>">Sign In</a></p>
        </div>
    </div>
</div>
<?php
$extraScripts = ['validation.js'];
require __DIR__ . '/includes/footer.php';

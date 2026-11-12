<?php
require_once __DIR__ . '/includes/auth.php';

// Sudah login → langsung ke halaman sesuai role
if ($u = current_user()) {
    redirect(home_for_role($u['role']));
}

$errors = [];
$redirectTo = (string) ($_POST['redirect'] ?? $_GET['redirect'] ?? '');

if (is_post()) {
    verify_csrf();
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $errors[] = 'Username/email dan password wajib diisi.';
    } else {
        // Cari berdasarkan username ATAU email
        $st = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $st->execute([strtolower($login), strtolower($login)]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Username/email atau password salah.';
        } elseif ((int) $user['is_active'] !== 1) {
            $errors[] = 'Akun Anda dinonaktifkan. Hubungi admin KopiKita.';
        } else {
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            login_user($user);
            flash('success', 'Selamat datang, ' . $user['name'] . '!');
            // Redirect ke halaman asal hanya untuk pelanggan & bila path internal
            $target = home_for_role($user['role']);
            if ($redirectTo !== '') {
                $safe = safe_redirect_path($redirectTo, $target);
                $allowed = $user['role'] === 'customer' ? '#/(customer/|menu|product-detail|index)#' : '#/(admin|cashier)/#';
                if (preg_match($allowed, $safe) && !($user['role'] === 'cashier' && strpos($safe, '/admin/') !== false)) {
                    redirect($safe);
                }
            }
            redirect($target);
        }
    }
    usleep(250000); // perlambat percobaan brute force
}

$pageTitle = 'Sign In';
$hideNav = true;
$hideFooter = true;
$bodyClass = 'auth-page';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-visual">
        <a href="<?= e(url('index.php')) ?>" class="brand">
            <span class="brand-logo">K</span>
            <span class="brand-text"><span class="brand-name">KopiKita</span><span class="brand-est">Est. <?= e(APP_EST) ?></span></span>
        </a>
        <div>
            <h2>A Little Coffee, <em>A Lot</em> of Happiness.</h2>
            <p class="text-cream">Masuk untuk memesan kopi favorit Anda dan pantau pesanan secara real-time.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card">
            <h1>Welcome <em>back</em></h1>
            <p class="sub">Masuk dengan username atau email Anda.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert"><?= e(implode(' ', $errors)) ?><button type="button" class="alert-close" aria-label="Tutup">&times;</button></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login.php')) ?>" data-validate novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
                <div class="form-group">
                    <label class="form-label" for="login">Username atau Email</label>
                    <input type="text" class="form-control" id="login" name="login" required autocomplete="username"
                           value="<?= e($_POST['login'] ?? '') ?>" placeholder="contoh: pelanggan atau nama@email.com" autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="toggle-pass" data-toggle-pass="password" aria-label="Tampilkan password"><?= icon('eye') ?></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Login <span class="arrow">›</span></button>
            </form>

            <p class="auth-foot">Belum punya akun? <a href="<?= e(url('register.php')) ?>">Daftar sebagai pelanggan</a></p>
            <p class="auth-foot mt-0"><a href="<?= e(url('index.php')) ?>">← Kembali ke beranda</a></p>

            <div class="demo-accounts">
                <strong class="text-cream">Akun demo:</strong><br>
                Admin <code>admin</code> / <code>admin123</code> ·
                Kasir <code>kasir</code> / <code>kasir123</code> ·
                Pelanggan <code>pelanggan</code> / <code>pelanggan123</code>
            </div>
        </div>
    </div>
</div>
<?php
$extraScripts = ['validation.js'];
require __DIR__ . '/includes/footer.php';

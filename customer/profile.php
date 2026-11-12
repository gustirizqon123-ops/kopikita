<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['customer']);
$pdo = db();

$st = $pdo->prepare('SELECT * FROM customers WHERE user_id = ?');
$st->execute([$user['id']]);
$cust = $st->fetch();
if (!$cust) {
    abort(403);
}

$errors = [];
$tab = (string) ($_POST['form'] ?? 'profile');

if (is_post()) {
    verify_csrf();
    if ($tab === 'password') {
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $conf = (string) ($_POST['confirm_password'] ?? '');
        $row = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $row->execute([$user['id']]);
        if (!password_verify($cur, (string) $row->fetchColumn())) {
            $errors[] = 'Password saat ini salah.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $conf) {
            $errors[] = 'Konfirmasi password baru tidak sama.';
        } else {
            $pdo->prepare('UPDATE users SET password = ?, updated_at = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), now(), $user['id']]);
            flash('success', 'Password berhasil diubah.');
            redirect('customer/profile.php');
        }
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors[] = 'Nama lengkap 3–100 karakter.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,25}$/', $phone)) {
            $errors[] = 'Nomor telepon tidak valid.';
        }
        if (mb_strlen($address) > 500) {
            $errors[] = 'Alamat maksimal 500 karakter.';
        }
        if (!$errors) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET name = ?, updated_at = ? WHERE id = ?')->execute([$name, now(), $user['id']]);
            $pdo->prepare('UPDATE customers SET name = ?, phone = ?, address = ?, updated_at = ? WHERE id = ?')
                ->execute([$name, $phone ?: null, $address ?: null, now(), $cust['id']]);
            $pdo->commit();
            flash('success', 'Profil berhasil diperbarui.');
            redirect('customer/profile.php');
        }
        $cust['name'] = $name; $cust['phone'] = $phone; $cust['address'] = $address;
    }
}

$pageTitle = 'Profil Saya';
$extraScripts = ['validation.js'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('customer/index.php')) ?>">Home</a> / Profil</div>
        <h1>Profil <em>Saya</em></h1>
    </section>
    <?php foreach ($errors as $er): ?><div class="alert alert-error mt-3"><?= e($er) ?></div><?php endforeach; ?>
    <div class="contact-grid">
        <section class="panel card-block" style="padding:28px">
            <h3>Data Diri</h3>
            <form method="post" data-validate>
                <?= csrf_field() ?><input type="hidden" name="form" value="profile">
                <div class="form-group"><label class="form-label">Username</label><input class="form-control" value="<?= e($user['username']) ?>" disabled></div>
                <div class="form-group"><label class="form-label">Email</label><input class="form-control" value="<?= e($user['email']) ?>" disabled></div>
                <div class="form-group"><label class="form-label" for="name">Nama lengkap</label><input class="form-control" id="name" name="name" required minlength="3" maxlength="100" value="<?= e($cust['name']) ?>"></div>
                <div class="form-group"><label class="form-label" for="phone">Telepon</label><input class="form-control" id="phone" name="phone" maxlength="25" value="<?= e($cust['phone']) ?>"></div>
                <div class="form-group"><label class="form-label" for="address">Alamat</label><textarea class="form-control" id="address" name="address" rows="3" maxlength="500"><?= e($cust['address']) ?></textarea></div>
                <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
            </form>
        </section>
        <section class="panel card-block" style="padding:28px">
            <h3>Ubah Password</h3>
            <form method="post" data-validate>
                <?= csrf_field() ?><input type="hidden" name="form" value="password">
                <div class="form-group"><label class="form-label" for="cp">Password saat ini</label><input class="form-control" type="password" id="cp" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label class="form-label" for="np">Password baru</label><input class="form-control" type="password" id="np" name="new_password" required minlength="8" autocomplete="new-password"></div>
                <div class="form-group"><label class="form-label" for="pc">Konfirmasi password baru</label><input class="form-control" type="password" id="pc" name="confirm_password" required data-match="np" autocomplete="new-password"></div>
                <button class="btn btn-secondary" type="submit">Ubah Password</button>
            </form>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

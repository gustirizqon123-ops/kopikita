<?php
require_once __DIR__ . '/../includes/auth.php';

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$back = safe_redirect_path((string) ($_POST['back'] ?? ''), 'index.php');
if (strpos($back, '#') === false) {
    $back .= '#newsletter';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    flash('error', 'Format email tidak valid.');
    header('Location: ' . $back);
    exit;
}

$pdo = db();
$st = $pdo->prepare('SELECT id, is_active FROM newsletter_subscribers WHERE email = ?');
$st->execute([$email]);
$row = $st->fetch();
if ($row && (int) $row['is_active'] === 1) {
    flash('info', 'Email ini sudah terdaftar di newsletter kami.');
} elseif ($row) {
    $pdo->prepare('UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = ? WHERE id = ?')->execute([now(), $row['id']]);
    flash('success', 'Selamat datang kembali! Langganan Anda diaktifkan lagi.');
} else {
    $pdo->prepare('INSERT INTO newsletter_subscribers (email, is_active, subscribed_at) VALUES (?,1,?)')->execute([$email, now()]);
    flash('success', 'Terima kasih! Anda berhasil berlangganan newsletter KopiKita.');
}
header('Location: ' . $back);
exit;

<?php
/**
 * Bootstrap aplikasi + autentikasi.
 * Setiap halaman cukup: require_once __DIR__ . '/includes/auth.php';
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('KOPIKITASESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Header keamanan dasar
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Pengguna yang sedang login (dimuat ulang dari DB setiap request,
 * sehingga akun yang dinonaktifkan langsung kehilangan akses).
 */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT u.id, u.name, u.username, u.email, u.role, u.avatar, u.is_active, c.id AS customer_id
                         FROM users u LEFT JOIN customers c ON c.user_id = u.id WHERE u.id = ? LIMIT 1');
    $st->execute([$_SESSION['user_id']]);
    $row = $st->fetch();
    if (!$row || (int) $row['is_active'] !== 1) {
        unset($_SESSION['user_id'], $_SESSION['cart']);
        session_regenerate_id(true);
        return null;
    }
    $user = $row;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): ?string
{
    $u = current_user();
    return $u['role'] ?? null;
}

/** Halaman tujuan setelah login sesuai role. */
function home_for_role(string $role): string
{
    switch ($role) {
        case 'admin':
            return 'admin/index.php';
        case 'cashier':
            return 'cashier/index.php';
        default:
            return 'customer/index.php';
    }
}

/** Wajib login; jika belum, arahkan ke login dengan parameter redirect. */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (is_ajax()) {
            json_response(['ok' => false, 'message' => 'Silakan login terlebih dahulu.', 'login' => url('login.php')], 401);
        }
        flash('info', 'Silakan login terlebih dahulu.');
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    return $u;
}

/**
 * Wajib role tertentu. Contoh: require_role(['admin']), require_role(['cashier','admin']).
 * Role salah → 403.
 */
function require_role(array $roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        if (is_ajax()) {
            json_response(['ok' => false, 'message' => 'Akses ditolak.'], 403);
        }
        abort(403);
    }
    return $u;
}

/** Login: regenerasi session id untuk mencegah session fixation. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([now(), $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

<?php
/**
 * Koneksi PDO ke database kopikita_db.
 * Sesuaikan kredensial bila MySQL XAMPP Anda memakai password.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'kopikita_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Mengembalikan instance PDO tunggal.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Samakan zona waktu MySQL dengan PHP (Asia/Makassar = UTC+8)
        $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
        $pdo->exec("SET time_zone = '" . $offset . "'");
    } catch (PDOException $e) {
        http_response_code(500);
        $msg = defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Tidak dapat terhubung ke database.';
        exit('<h1>Koneksi database gagal</h1><p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')
            . '</p><p>Pastikan MySQL XAMPP berjalan dan database <code>kopikita_db</code> sudah di-import.</p>');
    }
    return $pdo;
}

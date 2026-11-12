# KopiKita – Coffee Shop Management System

*A Little Coffee, A Lot of Happiness.*

Website coffee shop lengkap dengan tiga tampilan: **Customer Website** (gelap, krem, oranye), **Admin Dashboard**, dan **Cashier System**.
Dibangun dengan PHP native (PDO), MySQL/MariaDB, HTML/CSS/JavaScript vanilla, dan Chart.js (CDN). Tanpa framework.

## 1. Instalasi (XAMPP, Windows)

1. Pasang XAMPP (PHP 8.0+ disarankan) lalu jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Letakkan folder ini di `C:\xampp\htdocs\kopikita`.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/kopikita_db.sql` → **Go**.
   Atau lewat terminal:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database\kopikita_db.sql
   ```
   Perintah ini membuat database `kopikita_db` (9 tabel) beserta data contoh. **Mengimpor ulang akan mereset semua data.**
4. Buka **http://localhost/kopikita/**.

Konfigurasi:
- Database: `config/database.php` (default host `127.0.0.1`, user `root`, password kosong).
- Aplikasi: `config/app.php` (nama, zona waktu `Asia/Makassar`, harga ukuran Large, alamat kedai). `BASE_URL` terdeteksi otomatis; bila perlu isi manual `define('BASE_URL', '/kopikita');`.
- Mengubah `APP_DEBUG` menjadi `false` sebelum dipakai di luar lokal.
- `.htaccess` hanya berisi aturan dasar (tanpa mod_rewrite); `AllowOverride All` sudah aktif bawaan di XAMPP.

Uji cepat tanpa Apache (opsional): `C:\xampp\php\php.exe -S localhost:8000` dari folder induk, lalu atur `BASE_URL` sesuai.

## 2. Akun contoh

| Role | Username | Email | Password |
|---|---|---|---|
| Admin | `admin` | `admin@kopikita.test` | `admin123` |
| Kasir | `kasir` | `kasir@kopikita.test` | `kasir123` |
| Pelanggan | `pelanggan` | `pelanggan@kopikita.test` | `pelanggan123` |

Login memakai **username atau email**. **Ganti semua password ini sebelum dipakai sungguhan.** Hash disimpan di SQL, bukan plaintext.
Setelah login: admin → `/admin/`, kasir → `/cashier/`, pelanggan → `/customer/`.

## 3. Fitur

**Website publik / pelanggan** – beranda 8 section (hero slider, strip highlight, carousel best seller, tentang, Popular Collections, newsletter, footer), menu dengan filter kategori dan pencarian, detail produk (ukuran, jumlah, catatan), galeri dengan lightbox, about, contact, registrasi/login. Pelanggan: keranjang (session), checkout (dine-in/takeaway, metode bayar), halaman sukses dengan kode pesanan, riwayat dan detail pesanan dengan *timeline* status, profil dan ganti password.

**Cashier System** – dashboard shift, pesanan masuk (auto-refresh 15 detik), detail dan perubahan status sesuai alur, konfirmasi pembayaran (hitung kembalian, nomor referensi QRIS/transfer), POS walk-in, struk 58/80 mm, riwayat transaksi.

**Admin Dashboard** – kartu ringkasan + grafik penjualan + produk terlaris + stok menipis + filter tanggal; CRUD kategori dan produk (upload foto, stok minimum, best seller, hapus-atau-nonaktifkan); kelola pesanan; data pelanggan; laporan penjualan dengan ekspor **Excel (.xls), CSV, dan PDF** (halaman cetak); galeri; newsletter (+ekspor CSV); pengguna (tambah kasir, aktif/nonaktif).

## 4. Aturan bisnis penting

- Alur status: `pending → confirmed → preparing → ready → completed`; pembatalan hanya dari `pending`/`confirmed` dan wajib alasan.
- `completed` hanya bila pembayaran `paid`. Laporan hanya menghitung pesanan `completed` + `paid`.
- Stok dikurangi saat `confirmed` (online) atau saat transaksi POS, ditandai `stock_deducted`, dan dikembalikan saat dibatalkan.
- Harga, ukuran (Large +Rp5.000), subtotal, total, dan stok **selalu dihitung ulang di server**; nilai dari browser diabaikan.
- Checkout/POS menyimpan `orders` + `order_details` (snapshot nama/harga) + `sales` dalam satu transaksi DB dengan `SELECT … FOR UPDATE`.
- Pembayaran **QRIS dan transfer hanyalah simulasi**; tidak ada integrasi payment gateway.

## 5. Struktur folder

```
kopikita/
├── index.php  menu.php  product-detail.php  gallery.php  about.php  contact.php
├── login.php  register.php  logout.php  403.php  404.php
├── actions/subscribe.php            # form newsletter
├── customer/                        # area pelanggan (keranjang, checkout, pesanan, profil)
├── cashier/                         # POS, pesanan masuk, pembayaran, struk, riwayat
├── admin/                           # dashboard, categories, products, orders, customers,
│                                    #   reports, gallery, newsletter, users
├── includes/                        # auth, functions, components, order-view, header/footer
├── config/                          # app.php, database.php
├── database/kopikita_db.sql         # skema 9 tabel + data contoh
├── docs/                            # PROMPT.md, ERD.md, sitemap.md (spesifikasi)
└── assets/ css/ js/ images/{ui,products,gallery}
```

`config/`, `includes/`, `database/`, dan `docs/` ditutup dengan `.htaccess` (Require all denied). Folder upload hanya mengizinkan gambar dan tidak mengeksekusi skrip.

## 6. Mengganti foto placeholder dengan foto asli

Semua gambar produk dan galeri bawaan adalah SVG buatan sendiri (tidak butuh internet).
- **Produk**: Admin → *Produk & Stok* → Edit → pilih foto (JPG/PNG/WEBP, maks. 2 MB).
- **Galeri**: Admin → *Galeri* → Upload foto dan pilih koleksinya.
- Foto hero, tentang, dan interior ada di `assets/images/ui/`; ganti file dengan nama sama atau ubah rujukannya di `index.php` / `assets/css/style.css`.
Gunakan foto milik sendiri atau berlisensi bebas (misal Unsplash/Pexels).

## 7. Catatan keamanan

- PDO prepared statement, `password_hash`/`password_verify`, `session_regenerate_id` saat login, cookie session `HttpOnly` + `SameSite=Lax`.
- Semua output lewat `e()` (`htmlspecialchars`); token CSRF di semua form POST dan endpoint AJAX (termasuk logout).
- Hak akses per role lewat `require_role()` di awal setiap halaman terproteksi; pelanggan hanya dapat membuka pesanannya sendiri (pesanan orang lain → 404, cegah IDOR).
- Upload: validasi MIME asli (`finfo`) + `getimagesize`, maks. 2 MB, nama file diacak.
- Redirect pasca-login hanya ke path internal (cegah open redirect). Ekspor Excel/CSV menetralkan sel berawalan `= + - @`.
- Akun nonaktif ditolak login dan kehilangan akses seketika (user dimuat ulang dari DB tiap request).
- Untuk produksi: aktifkan HTTPS, ubah password akun contoh, set `APP_DEBUG=false`, pakai user MySQL khusus (bukan `root` tanpa password).

## 8. Asumsi dan batasan

- Alamat, telepon, dan peta di halaman Contact adalah **data simulasi**; ganti di `config/app.php` (peta di `contact.php`).
- Metrik di beranda (10+ barista, 5K+ pelanggan, rating) bersifat dekoratif.
- Hanya admin yang menambah akun kasir (halaman *Pengguna*); admin/kasir tidak membuat keranjang online.
- Diskon belum punya antarmuka input (kolom `discount_amount` tetap tersedia dan dihitung di laporan).
- Ekspor PDF memakai halaman cetak browser (*Save as PDF*), bukan library PDF.
- Font (Google Fonts) dan Chart.js dimuat dari CDN; tanpa internet, tampilan memakai font cadangan dan grafik tidak muncul.
- Newsletter hanya menyimpan email; tidak ada pengiriman email.
- Pengujian dilakukan lewat HTTP (curl) dan lint PHP; tampilan visual belum diuji otomatis di berbagai browser/perangkat.

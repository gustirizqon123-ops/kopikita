Kamu adalah senior full-stack PHP developer. Bangun website lengkap **KopiKita – Coffee Shop Management System** (slogan: *A Little Coffee, A Lot of Happiness.*) di folder project ini (`C:\xampp\htdocs\kopikita`), siap dijalankan di XAMPP (Apache + MySQL/MariaDB).

## 0. Sumber kebenaran (WAJIB dibaca dulu)

Tiga dokumen berikut adalah spesifikasi utama. Baca SEMUA sampai habis sebelum menulis kode:

- `C:\Users\ACER\Downloads\PROMPT.md` → kebutuhan, teknologi, panduan desain, detail halaman, hak akses, ketentuan teknis
- `C:\Users\ACER\Downloads\ERD.md` → rancangan database `kopikita_db` (9 tabel, tipe kolom, relasi, aturan bisnis, query laporan)
- `C:\Users\ACER\Downloads\sitemap.md` → daftar halaman, nama file, struktur folder, alur, hak akses, urutan implementasi

Salin ketiganya ke `docs/` di project ini sebagai referensi. Jika ada konflik antar dokumen, urutan prioritas: ERD.md (database) > sitemap.md (file & routing) > PROMPT.md (desain & fitur). Ikuti nama file, nama tabel, nama kolom, dan struktur folder PERSIS seperti dokumen. Jangan menambah tabel di luar 9 tabel ERD.

## 1. Referensi desain visual

Gaya mengikuti 4 gambar referensi coffee shop premium gelap yang saya lampirkan (hanya acuan gaya; **jangan salin nama brand "United Coffee"/"Bodron", logo, atau fotonya** — pakai identitas KopiKita dan foto bebas lisensi Unsplash/Pexels atau placeholder):

1. **Hero**: navbar transparan di dalam panel membulat (logo bulat "K" + "KopiKita" + *Est. 2026*; menu Home/Menu/Gallery/About/Contact; ikon cari, ikon keranjang + badge, tombol pil **Sign In**). Judul Playfair Display besar dengan kata italic ("A Little Coffee, *A Lot* of Happiness."), tombol **Order Now** + **Explore Menu**, visual cup kopi dengan percikan dan biji kopi melayang, strip teks italic di bawah hero (Fresh Roasted Daily • Expert Baristas • Local Beans • Cozy Space).
2. **About**: panel gelap membulat, 2 foto kiri/kanan, judul "Sebuah kedai yang dibangun oleh pecinta kopi", 3 kartu fitur (Biji Pilihan, Diracik Barista Ahli, Ramah Lingkungan), watermark teks "KopiKita" transparan, baris 4 statistik (2026 Berdiri, 10+ Barista, 100% Biji Lokal, 5K+ Pelanggan Bahagia).
3. **Signature Collection**: foto ruangan kedai dengan judul tengah dan tombol, lalu "Discover Our *Signature* Collection" + carousel kartu produk best seller dengan panah bulat.
4. **Popular Collections + Newsletter + Footer**: chip filter (All, Coffee, Drinks, Food, Ambience, Desserts), kartu koleksi (1 foto besar + 3 thumbnail + jumlah foto), section "Stay up to date" dengan input email + Subscribe, footer 4 kolom (logo, Quick Links, Information, Social Media).

Palet, tipografi, tombol pil, chip, kartu, animasi ringan, dan responsif (hamburger di mobile) ikuti **PROMPT.md bagian 2** (token CSS `--espresso`, `--roast`, `--mocha`, `--brown`, `--cream`, `--latte`, `--orange`, `--caramel`, dst.). Admin & kasir: sidebar `--roast`, konten cream `#FAF5EE`, font Inter.

Karena tidak ada foto asli: buat aset dengan SVG/CSS (cup kopi, biji kopi, pola) di `assets/images/ui/`, dan untuk foto produk/galeri gunakan gambar placeholder lokal (SVG/gradient bertema kopi) agar tidak bergantung internet. Sediakan README cara mengganti dengan foto asli lewat upload admin.

## 2. Aturan teknis (tidak boleh dilanggar)

- PHP native + PDO prepared statement, tanpa framework. HTML/CSS/JS vanilla (tanpa framework CSS). Chart.js via CDN. Google Fonts: Poppins, Playfair Display, Inter.
- `password_hash()` / `password_verify()`; semua output lewat `htmlspecialchars()` (helper `e()`); token CSRF di semua form POST dan endpoint pengubah data; `session_regenerate_id()` saat login.
- Setiap file PHP area terproteksi memanggil `require_role([...])` di awal (admin boleh masuk area kasir). Pelanggan hanya bisa melihat pesanannya sendiri (cek kepemilikan di server, cegah IDOR).
- Harga, ukuran (Large +Rp5.000 via `SIZE_PRICES`), subtotal, diskon, total, stok **dihitung ulang di server**; abaikan nilai dari browser.
- Checkout & POS: `orders` + `order_details` (snapshot nama/harga) + `sales` (pending) dalam **satu transaksi DB** (BEGIN/COMMIT/ROLLBACK), lock stok dengan `SELECT ... FOR UPDATE`.
- Aturan status & stok persis seperti **ERD.md bagian 4** (stok dikurangi saat `confirmed`/POS + flag `stock_deducted`, dikembalikan saat batal; urutan status pending→confirmed→preparing→ready→completed; completed hanya jika `payment_status='paid'`; laporan hanya completed + paid).
- Upload gambar: hanya JPG/PNG/WEBP, maks 2 MB, validasi MIME asli (`finfo`), nama file diacak.
- Format rupiah `Rp25.000`; timezone `Asia/Makassar`; charset `utf8mb4`.
- Pembayaran QRIS/transfer hanya simulasi — jangan tampilkan seolah terhubung payment gateway.
- Kode bersih, ada komentar singkat seperlunya, tidak ada kredensial sungguhan.

## 3. Cara kerja (kerjakan bertahap, jangan sekaligus)

Gunakan task list. Ikuti urutan implementasi di **sitemap.md bagian 8**, dalam fase berikut. Selesaikan dan verifikasi satu fase sebelum lanjut; setelah tiap fase ringkas apa yang selesai.

1. **Fondasi**: struktur folder, `database/kopikita_db.sql` (CREATE DATABASE, 9 tabel InnoDB + FK + index, data contoh: 4 kategori, **minimal 4 produk per kategori**, 3 akun contoh dengan hash valid, pelanggan "Pelanggan Umum", galeri, beberapa pesanan contoh `completed`+`paid` agar dashboard berisi), `config/database.php`, `config/app.php`, `includes/auth.php`, `includes/functions.php`.
2. **Desain dasar**: `assets/css/style.css`, `admin.css`, `print.css` + aset UI SVG; `includes/header.php`, `footer.php`, `admin-header/footer.php`, `cashier-header/footer.php`.
3. **Auth**: `register.php`, `login.php` (username **atau** email, redirect per role, pesan error jelas, akun nonaktif ditolak), `logout.php`, `403.php`, `404.php`.
4. **Halaman publik**: `index.php` (8 section sesuai sitemap), `menu.php` (filter chip + pencarian), `product-detail.php`, `gallery.php` (lightbox), `about.php`, `contact.php`, `actions/subscribe.php`; JS `script.js`.
5. **Admin – katalog**: CRUD kategori & produk (upload foto, stok minimum, best seller, hapus-atau-nonaktifkan).
6. **Pelanggan**: `customer/*` (cart via session + `cart-action.php`, checkout, order-success, orders, order-detail dengan timeline status, profile); `cart.js`, `validation.js`.
7. **Kasir**: dashboard, pesanan masuk (auto-refresh), detail & ubah status, konfirmasi pembayaran (hitung kembalian), POS walk-in (`pos.js`), struk 58/80 mm, riwayat transaksi.
8. **Admin – operasional & laporan**: dashboard (kartu, Chart.js, terbaru, top 5, stok menipis, filter tanggal), kelola pesanan, pelanggan, laporan + ekspor Excel (`.xls`/CSV dari PHP) dan PDF (halaman cetak), galeri, newsletter (+ekspor CSV), pengguna.
9. **Pengujian & dokumentasi**: uji seluruh alur dan hak akses tiap role, tulis `README.md` (instalasi XAMPP, import SQL via phpMyAdmin, akun contoh, struktur folder, catatan keamanan).

## 4. Verifikasi (lakukan sendiri, jangan hanya mengklaim selesai)

- Jalankan `php -l` pada semua file PHP (cari `php.exe` di `C:\xampp\php\`); perbaiki semua error.
- Import SQL ke MySQL XAMPP (`C:\xampp\mysql\bin\mysql.exe`) dan pastikan tanpa error; cek 9 tabel terbentuk.
- Jika Apache/MySQL belum jalan, beri tahu saya cara menyalakannya (atau jalankan `php -S localhost:8000` hanya untuk uji cepat).
- Uji dengan browser/HTTP: login 3 role, akses halaman terlarang → 403/redirect, alur customer (keranjang → checkout → kode pesanan), kasir konfirmasi bayar → completed → muncul di laporan, stok berkurang/kembali saat batal, ekspor laporan.
- Cek tampilan desktop dan mobile (hamburger menu, tabel tidak meluber).
- Laporkan jujur: apa yang lulus, apa yang belum teruji, dan temuan bug.

## 5. Akun contoh (hash disimpan di SQL, bukan plaintext)

| Role | Username | Email | Password |
|---|---|---|---|
| Admin | `admin` | `admin@kopikita.test` | `admin123` |
| Kasir | `kasir` | `kasir@kopikita.test` | `kasir123` |
| Pelanggan | `pelanggan` | `pelanggan@kopikita.test` | `pelanggan123` |

## 6. Hasil akhir

Satu folder `kopikita/` yang langsung jalan di `http://localhost/kopikita/` dengan tiga tampilan: **Customer Website** (gelap, krem, oranye), **Admin Dashboard**, dan **Cashier System**, plus `database/kopikita_db.sql` dan `README.md`.

Mulai sekarang dengan: (a) membaca ketiga dokumen, (b) menyalin ke `docs/`, (c) menyampaikan rencana singkat per fase, lalu langsung kerjakan Fase 1. Jangan berhenti untuk bertanya hal yang sudah dijawab di dokumen; ambil keputusan wajar dan catat asumsi di README.

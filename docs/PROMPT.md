# PROMPT – KopiKita Coffee Shop Management System

Buatkan saya **1 folder project website** lengkap yang siap dijalankan di XAMPP dengan ketentuan berikut. Ikuti juga dokumen pendukung **ERD.md** (rancangan database) dan **sitemap.md** (struktur halaman dan folder).

| Properti | Isi |
|---|---|
| Judul | **KopiKita – Coffee Shop Management System** |
| Slogan | *A Little Coffee, A Lot of Happiness.* |
| Tema | Coffee Modern • Brown, Cream, Orange (nuansa gelap/espresso yang elegan) |
| Nama folder | `kopikita/` |
| Nama database | `kopikita_db` |

---

## 1. Teknologi yang digunakan

| Teknologi | Kegunaan |
|---|---|
| HTML | Membuat struktur halaman |
| CSS | Mengatur desain dan tampilan (tanpa framework CSS wajib; boleh memakai CSS Grid/Flexbox) |
| JavaScript | Interaksi (slider, filter kategori, keranjang, modal) dan validasi form |
| PHP (native, PDO) | Mengolah login, registrasi, keranjang, transaksi, dan laporan |
| MySQL / MariaDB | Menyimpan data website |
| XAMPP | Menjalankan Apache dan MySQL |
| phpMyAdmin | Mengelola database (import `database/kopikita_db.sql`) |
| Chart.js (CDN) | Grafik penjualan di dashboard admin |
| Google Fonts | Poppins, Playfair Display, Inter |

Ekspor laporan:
- **Excel** → file `.xls`/`.csv` dibuat langsung oleh PHP (tanpa library tambahan).
- **PDF** → halaman laporan versi cetak (`window.print()` → *Save as PDF*). Jika ingin file PDF langsung, boleh memakai Dompdf melalui Composer (opsional).

---

## 2. Panduan desain (berdasarkan referensi desain)

Gaya visual mengikuti referensi: **website coffee shop premium bernuansa gelap (dark espresso)** dengan teks krem, aksen oranye-karamel, foto kopi yang hangat dan berkontras tinggi, serta kartu-kartu berwarna cokelat tua dengan sudut membulat. Referensi hanya dipakai sebagai acuan gaya — **nama brand, logo, dan foto pada referensi tidak boleh disalin**; gunakan identitas KopiKita dan foto bebas lisensi (misalnya Unsplash/Pexels) atau foto milik sendiri.

### 2.1 Palet warna

| Token CSS | Warna | Penggunaan |
|---|---|---|
| `--espresso` | `#1A0F0A` | Latar utama halaman (paling gelap) |
| `--roast` | `#2B1A12` | Latar section/panel besar |
| `--mocha` | `#3B2519` | Kartu, chip filter, input |
| `--brown` | `#6F4E37` | Border, tombol sekunder, hover |
| `--cream` | `#F3E6D3` | Judul dan teks utama di atas latar gelap |
| `--latte` | `#C9B39C` | Teks paragraf/sekunder (muted) |
| `--orange` | `#E08A3C` | Aksen utama: tombol CTA, harga, link aktif, ikon |
| `--caramel` | `#F2A65A` | Hover tombol oranye, highlight |
| `--success` / `--warning` / `--danger` | `#4CAF7A` / `#F2B544` / `#E05A47` | Badge status pesanan & stok |

Admin dan kasir memakai latar terang **cream** (`#FAF5EE`) dengan sidebar `--roast`, agar tabel dan angka mudah dibaca.

### 2.2 Tipografi

| Font | Dipakai untuk |
|---|---|
| **Playfair Display** | Judul hero, judul section, slogan, angka statistik besar. Gunakan *italic* untuk kata penekanan (contoh: "A Little Coffee, *A Lot* of Happiness.") |
| **Poppins** | Teks utama, navigasi, tombol, kartu produk (website pelanggan) |
| **Inter** | Seluruh dashboard admin & kasir: tabel, form, angka, badge |

### 2.3 Elemen visual

- **Container membulat**: section utama berada dalam panel `--roast` dengan `border-radius: 24px` dan bayangan lembut, di atas latar `--espresso` bertekstur biji kopi samar.
- **Navbar transparan/gelap**: logo bulat "K" + teks "KopiKita" (dengan sub-teks kecil *Est. 2026*) di kiri; menu di tengah; ikon cari, ikon keranjang (dengan badge jumlah), dan tombol pil **Sign In** di kanan.
- **Tombol**: berbentuk pil (`border-radius: 999px`). Primer = latar oranye/teks gelap; sekunder = outline krem tipis/teks krem; ikon panah `›` di dalam tombol.
- **Chip filter kategori**: pil kecil `--mocha`; chip aktif berwarna krem dengan teks gelap.
- **Kartu**: latar `--mocha`, radius 16px, foto memenuhi bagian atas, hover sedikit naik (`translateY(-4px)`) dengan bayangan.
- **Strip highlight**: baris teks serif italic berjarak rata di bawah hero (contoh: *Fresh Roasted Daily • Expert Baristas • Local Beans • Cozy Space*).
- **Statistik besar**: angka Playfair Display besar + label kecil, dalam kotak bergaris tipis (contoh: *2026 – Berdiri*, *10+ Barista*, *100% Biji Lokal*, *5K+ Pelanggan Bahagia*).
- **Watermark teks**: tulisan "KopiKita" besar transparan di belakang section About.
- **Animasi ringan**: fade-in saat scroll, elemen biji kopi melayang pada hero, carousel produk dengan tombol panah bulat.
- **Responsif**: desktop, tablet, dan mobile (navbar menjadi menu hamburger di layar kecil).

---

## 3. Rancangan database MySQL

Detail kolom, relasi, dan aturan ada di **ERD.md**. Ringkasan:

| Nama tabel | Data yang disimpan |
|---|---|
| `users` | Nama, username, email, password (hash), role (`admin`, `cashier`, `customer`) |
| `customers` | Nama dan kontak pelanggan (terhubung ke akun, atau pelanggan *walk-in* di kasir) |
| `categories` | Kategori produk |
| `products` | Nama produk, harga, stok, batas stok minimum, foto, status best seller |
| `orders` | Nomor pesanan, tanggal, jenis, status, total |
| `order_details` | Detail produk yang dipesan (ukuran, jumlah, catatan, harga snapshot) |
| `sales` | Data penjualan dan pembayaran (metode, nominal dibayar, kembalian, status bayar, kasir) |
| `gallery_images` | Foto galeri "Popular Collections" (Coffee, Drinks, Food, Ambience, Desserts) |
| `newsletter_subscribers` | Email pendaftar newsletter "Stay up to date" |

---

## 4. Detail halaman

### A. Halaman login (`login.php`)
Dibuka melalui tombol **Sign In** di navbar, atau otomatis ketika pengunjung menekan *Order Now* / *Tambah ke Keranjang* sebelum login. Setelah login, pengguna diarahkan sesuai role.

Elemen:
- Layout dua kolom: kiri foto kopi gelap dengan slogan Playfair Display; kanan kartu form `--mocha`.
- Logo KopiKita.
- Form **username atau email**.
- Form **password** (dengan ikon tampil/sembunyikan).
- Tombol **Login** (pil oranye).
- Link **registrasi pelanggan** (`register.php`).
- Pesan error yang jelas jika data salah atau akun nonaktif.

### B. Halaman beranda / homepage (`index.php`)
Menampilkan identitas kedai dan menu unggulan. Urutan section mengikuti referensi desain:

1. **Navbar** – Home, Menu, Gallery, About, Contact + ikon cari, keranjang, tombol Sign In.
2. **Hero / banner promosi** – judul besar Playfair Display (contoh: "A Little Coffee, *A Lot* of Happiness."), paragraf singkat, tombol **Order Now** (primer) dan **Explore Menu** (sekunder), foto/ilustrasi cup kopi dengan efek percikan dan biji kopi melayang. Banner promo dapat bergeser (slider).
3. **Strip highlight** – 4 keunggulan kedai dalam teks italic.
4. **Menu best seller** – judul "Discover Our *Signature* Collection", carousel kartu produk best seller (foto, nama, harga, tombol tambah).
5. **Informasi kedai (About singkat)** – dua foto di kiri/kanan, teks "Sebuah kedai yang dibangun oleh pecinta kopi", 3 kartu fitur (*Biji Pilihan*, *Diracik Barista Ahli*, *Ramah Lingkungan*), dan baris statistik.
6. **Popular Collections (galeri)** – chip filter (All, Coffee, Drinks, Food, Ambience, Desserts), kartu koleksi berisi 1 foto besar + 3 thumbnail + jumlah foto.
7. **Newsletter "Stay up to date"** – foto cangkir kopi + biji kopi, input email, tombol Subscribe.
8. **Footer** – logo dan slogan, Quick Links, Informasi (jam buka, alamat, FAQ), Social Media, hak cipta.

### C. Halaman katalog menu (`menu.php`)
Menampilkan produk kopi dan non-kopi.

Elemen:
- Header section dengan judul Playfair Display dan kolom pencarian.
- **Kategori menu** sebagai chip filter: All, Coffee, Non-Coffee, Food & Pastry, Signature Drinks.
- Kartu produk: **foto produk**, badge kategori/best seller, **nama minuman**, **deskripsi** singkat, **harga** (oranye), status stok.
- Tombol **Tambah ke Keranjang**; tombol nonaktif jika stok habis.
- Klik kartu → `product-detail.php` (pilih ukuran, jumlah, catatan).

### D. Halaman pemesanan (keranjang & checkout)
Digunakan pelanggan untuk mengatur pesanan (`customer/cart.php` → `customer/checkout.php`).

Elemen:
- **Daftar produk yang dipilih** (foto kecil, nama, harga satuan).
- **Jumlah pesanan** (tombol − / +).
- **Pilihan ukuran**: Regular / Large (Large + Rp5.000; hanya untuk minuman).
- **Catatan khusus** per item (less sugar, extra shot, dll.) dan catatan pesanan.
- Jenis pesanan: **Dine-in** (isi nomor meja) atau **Takeaway**.
- Metode pembayaran: Cash di kasir / QRIS / Transfer (simulasi).
- **Subtotal**, diskon (jika ada), dan **total pembayaran** — dihitung ulang di server.
- Tombol **Buat Pesanan** → halaman sukses berisi kode pesanan.

### E. Dashboard admin (`admin/index.php`)
Halaman khusus admin untuk mengelola seluruh aktivitas kedai. Layout: sidebar gelap `--roast` + konten cream, font Inter.

Elemen:
- Kartu ringkasan: **total penjualan**, **jumlah pesanan**, **jumlah produk**, **jumlah pelanggan**.
- **Grafik penjualan** (Chart.js, garis/batang per hari atau per bulan).
- **Daftar pesanan terbaru** dengan badge status berwarna.
- **Produk paling banyak terjual** (top 5).
- **Produk dengan stok menipis** (stok ≤ batas minimum).
- **Filter laporan berdasarkan tanggal** (dari – sampai).
- **Ekspor laporan ke PDF atau Excel**.

### F. Kategori katalog produk
1. **Coffee** – Espresso, Americano, Cappuccino, Caffè Latte, Kopi Susu Aren, dll.
2. **Non-Coffee** – Matcha Latte, Chocolate, Red Velvet, Lemon Tea, dll.
3. **Food & Pastry** – Croissant, Cheesecake, Tiramisu, Banana Bread, dll.
4. **Signature Drinks** – racikan khas KopiKita (contoh: *KopiKita Caramel Cloud*, *Orange Espresso Tonic*).

Sediakan data contoh minimal 4 produk per kategori pada file SQL.

### G. Sistem pengguna dan hak akses

**Admin** – akses penuh untuk mengelola kedai kopi:
- Melihat dashboard penjualan.
- Menambah, mengedit, dan menghapus/menonaktifkan produk serta kategori.
- Mengatur harga dan stok.
- Melihat data pelanggan.
- Memproses dan memantau pesanan.
- Melihat dan mengekspor laporan penjualan.
- Mengelola akun pengguna, galeri, dan pelanggan newsletter.

**Kasir (cashier)** – mengurus transaksi di kedai:
- Melihat pesanan masuk dan memperbarui statusnya.
- Mengonfirmasi pembayaran (cash/QRIS/transfer) dan menghitung kembalian.
- Membuat transaksi langsung untuk pelanggan *walk-in* (POS).
- Mencetak struk dan melihat riwayat transaksi.

**Pelanggan (customer)** – memesan melalui website:
- Registrasi dan login.
- Melihat katalog menu.
- Memilih produk.
- Menambahkan produk ke keranjang.
- Melakukan checkout.
- Melihat riwayat dan status pesanan.

Setiap halaman PHP wajib memeriksa session dan role di sisi server.

### H. Alur kerja website

1. Pelanggan membuka website.
2. Pelanggan masuk ke beranda.
3. Memilih katalog produk (login/registrasi bila belum masuk).
4. Menambahkan ke keranjang.
5. Melakukan checkout.
6. Admin/kasir menerima pesanan dan mengonfirmasi pembayaran.
7. Pesanan selesai.
8. Data masuk ke database dan laporan penjualan.

Status pesanan:

| Status | Keterangan | Warna badge |
|---|---|---|
| Pending | Pesanan baru masuk | Abu-abu |
| Confirmed | Pesanan dikonfirmasi | Biru |
| Preparing | Sedang dibuat barista | Oranye |
| Ready | Siap disajikan | Kuning |
| Completed | Pesanan selesai | Hijau |
| Cancelled | Pesanan dibatalkan | Merah |

### I. Hasil akhir yang ingin dicapai

KopiKita memiliki tiga tampilan utama:

1. **Customer Website** – tampilan seperti toko online profesional bergaya coffee shop premium (gelap, krem, oranye) untuk melihat dan memesan kopi.
2. **Admin Dashboard** – tampilan khusus untuk mengelola produk, kategori, pelanggan, pesanan, galeri, pengguna, dan laporan penjualan.
3. **Cashier System** – tampilan khusus untuk mengonfirmasi pembayaran, membuat transaksi walk-in, mencetak struk, dan menyelesaikan transaksi.

---

## 5. Ketentuan teknis tambahan

- Password disimpan dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Semua query memakai **PDO prepared statement**; output ditampilkan dengan `htmlspecialchars()`.
- Form POST dilindungi **token CSRF**.
- Upload foto produk/galeri: hanya JPG/PNG/WEBP, maksimal 2 MB, nama file diacak.
- Keranjang disimpan di **session PHP** sebelum checkout.
- Harga, total, dan stok **dihitung ulang di server**; jangan percaya nilai dari browser.
- Penyimpanan pesanan, detail, data penjualan, dan perubahan stok memakai **transaksi database** (`BEGIN` / `COMMIT` / `ROLLBACK`).
- Format rupiah: `Rp25.000`; zona waktu `Asia/Makassar` (sesuaikan bila perlu).
- Sertakan file `database/kopikita_db.sql` (struktur + data contoh) dan `README.md` berisi langkah instalasi.

Akun contoh pada data awal:

| Role | Username | Email | Password |
|---|---|---|---|
| Admin | `admin` | `admin@kopikita.test` | `admin123` |
| Kasir | `kasir` | `kasir@kopikita.test` | `kasir123` |
| Pelanggan | `pelanggan` | `pelanggan@kopikita.test` | `pelanggan123` |

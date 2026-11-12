<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/components.php';

$pdo = db();
$best = $pdo->query('SELECT p.*, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id
                     WHERE p.is_best_seller = 1 AND p.is_available = 1 AND c.is_active = 1 ORDER BY p.name LIMIT 10')->fetchAll();

// Koleksi galeri: 1 foto besar + 3 thumbnail per koleksi
$collections = [];
foreach (GALLERY_COLLECTIONS as $key => $label) {
    $st = $pdo->prepare('SELECT * FROM gallery_images WHERE collection = ? AND is_active = 1 ORDER BY sort_order, id');
    $st->execute([$key]);
    $imgs = $st->fetchAll();
    if ($imgs) {
        $collections[$key] = ['label' => $label, 'images' => $imgs];
    }
}

$user = current_user();
$customerArea = $user && $user['role'] === 'customer';
$menuUrl = $customerArea ? 'customer/menu.php' : 'menu.php';
$bodyClass = 'home';
require __DIR__ . '/includes/header.php';
?>
<!-- 2. Hero -->
<section class="hero">
    <div class="container">
        <div class="panel hero-panel">
            <div class="hero-slider">
                <div class="hero-slide active">
                    <span class="promo-tag"><?= icon('coffee') ?> Fresh Roasted Daily</span>
                    <h1>A Little Coffee, <em>A Lot</em> of Happiness.</h1>
                    <p class="hero-lead">Secangkir kopi hangat, ruang yang nyaman, dan barista yang peduli. Selamat datang di KopiKita.</p>
                    <div class="hero-actions">
                        <a href="<?= e(url($menuUrl)) ?>" class="btn btn-primary btn-lg">Order Now</a>
                        <a href="<?= e(url($menuUrl)) ?>" class="btn btn-secondary btn-lg">Explore Menu</a>
                    </div>
                </div>
                <div class="hero-slide">
                    <span class="promo-tag"><?= icon('star') ?> Signature Baru</span>
                    <h1>Coba <em>Caramel Cloud</em> Racikan Kami.</h1>
                    <p class="hero-lead">Cold brew dengan cream karamel selembut awan. Favorit baru di KopiKita.</p>
                    <div class="hero-actions">
                        <a href="<?= e(url($menuUrl . '?category=signature-drinks')) ?>" class="btn btn-primary btn-lg">Lihat Signature</a>
                        <a href="<?= e(url('about.php')) ?>" class="btn btn-secondary btn-lg">Tentang Kami</a>
                    </div>
                </div>
                <div class="hero-slide">
                    <span class="promo-tag"><?= icon('leaf') ?> 100% Biji Lokal</span>
                    <h1>Dari Kebun <em>Nusantara</em> ke Cangkirmu.</h1>
                    <p class="hero-lead">Kami bekerja sama dengan petani kopi lokal agar setiap tegukan punya cerita.</p>
                    <div class="hero-actions">
                        <a href="<?= e(url($menuUrl)) ?>" class="btn btn-primary btn-lg">Pesan Sekarang</a>
                        <a href="<?= e(url('gallery.php')) ?>" class="btn btn-secondary btn-lg">Lihat Galeri</a>
                    </div>
                </div>
                <div class="slider-dots"></div>
                <div class="hero-meta">
                    <div><strong>17+</strong><span>Menu pilihan</span></div>
                    <div><strong>4.9</strong><span>Rating pelanggan</span></div>
                    <div><strong>07–22</strong><span>Jam buka harian</span></div>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <img class="cup" src="<?= e(url('assets/images/ui/hero-cup.svg')) ?>" alt="">
                <img class="bean b1" src="<?= e(url('assets/images/ui/bean.svg')) ?>" alt="">
                <img class="bean b2" src="<?= e(url('assets/images/ui/bean.svg')) ?>" alt="">
                <img class="bean b3" src="<?= e(url('assets/images/ui/bean.svg')) ?>" alt="">
                <img class="bean b4" src="<?= e(url('assets/images/ui/bean.svg')) ?>" alt="">
                <img class="bean b5" src="<?= e(url('assets/images/ui/bean.svg')) ?>" alt="">
            </div>
        </div>

        <!-- 3. Strip highlight -->
        <div class="highlight-strip reveal">
            <span>Fresh Roasted Daily</span><i class="dot">•</i>
            <span>Expert Baristas</span><i class="dot">•</i>
            <span>Local Beans</span><i class="dot">•</i>
            <span>Cozy Space</span>
        </div>
    </div>
</section>

<!-- 4. Best seller -->
<section class="section" id="signature">
    <div class="container">
        <div class="signature-banner reveal">
            <div>
                <span class="eyebrow">Signature</span>
                <h2>Ruang hangat untuk <em>setiap cerita</em></h2>
                <p>Duduk, seruput, dan nikmati waktu. Dari pagi yang tenang sampai malam yang hangat.</p>
                <a href="<?= e(url('about.php')) ?>" class="btn btn-cream">Kenali Kami</a>
            </div>
        </div>

        <div class="section-head reveal">
            <h2 class="section-title">Discover Our <em>Signature</em> Collection</h2>
            <div class="carousel-nav">
                <button type="button" class="round-arrow" data-prev aria-label="Sebelumnya"><?= icon('left') ?></button>
                <button type="button" class="round-arrow" data-next aria-label="Berikutnya"><?= icon('right') ?></button>
            </div>
        </div>
        <div class="carousel" data-carousel>
            <div class="carousel-track">
                <?php foreach ($best as $p) { echo render_product_card($p, $customerArea); } ?>
            </div>
        </div>
    </div>
</section>

<!-- 5. Informasi kedai -->
<section class="section" id="about">
    <div class="container">
        <div class="panel about-panel reveal">
            <span class="watermark" aria-hidden="true">KopiKita</span>
            <div class="about-grid">
                <div class="about-photo"><img src="<?= e(url('assets/images/ui/about-1.svg')) ?>" alt="Barista menyeduh kopi" loading="lazy"></div>
                <div class="about-copy">
                    <span class="eyebrow">Tentang Kami</span>
                    <h2 class="mt-2">Sebuah kedai yang dibangun oleh <em>pecinta kopi</em></h2>
                    <p>KopiKita lahir dari kecintaan sederhana: kopi enak yang bisa dinikmati siapa saja. Kami menyangrai biji lokal setiap hari dan menyajikannya dengan hangat.</p>
                    <div class="feature-cards">
                        <div class="feature-card"><div class="ico"><?= icon('bean') ?></div><h4>Biji Pilihan</h4><p>Arabika dan robusta lokal terbaik.</p></div>
                        <div class="feature-card"><div class="ico"><?= icon('award') ?></div><h4>Diracik Barista Ahli</h4><p>Setiap cangkir dibuat dengan teliti.</p></div>
                        <div class="feature-card"><div class="ico"><?= icon('leaf') ?></div><h4>Ramah Lingkungan</h4><p>Kemasan dan ampas kopi didaur ulang.</p></div>
                    </div>
                </div>
                <div class="about-photo offset"><img src="<?= e(url('assets/images/ui/about-2.svg')) ?>" alt="Suasana kedai" loading="lazy"></div>
            </div>
            <div class="stats">
                <div class="stat"><strong>2026</strong><span>Berdiri</span></div>
                <div class="stat"><strong>10+</strong><span>Barista</span></div>
                <div class="stat"><strong>100%</strong><span>Biji Lokal</span></div>
                <div class="stat"><strong>5K+</strong><span>Pelanggan Bahagia</span></div>
            </div>
        </div>
    </div>
</section>

<!-- 6. Popular Collections -->
<section class="section" id="collections">
    <div class="container">
        <div class="section-head reveal">
            <h2 class="section-title">Popular <em>Collections</em></h2>
            <a href="<?= e(url('gallery.php')) ?>" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="chips" data-filter-group="#collectionGrid" style="margin-bottom:22px">
            <button type="button" class="chip active" data-filter="all">All</button>
            <?php foreach ($collections as $key => $c): ?>
                <button type="button" class="chip" data-filter="<?= e($key) ?>"><?= e($c['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="collection-grid" id="collectionGrid">
            <?php foreach ($collections as $key => $c): $imgs = $c['images']; ?>
                <a class="collection-card" href="<?= e(url('gallery.php?collection=' . $key)) ?>" data-cat="<?= e($key) ?>">
                    <div class="main"><img src="<?= e(gallery_image_url($imgs[0]['image'])) ?>" alt="<?= e($imgs[0]['title']) ?>" loading="lazy"></div>
                    <div class="thumbs">
                        <?php foreach (array_slice($imgs, 1, 3) as $im): ?>
                            <div><img src="<?= e(gallery_image_url($im['image'])) ?>" alt="<?= e($im['title']) ?>" loading="lazy"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="meta"><h4><?= e($c['label']) ?></h4><span><?= count($imgs) ?> foto</span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

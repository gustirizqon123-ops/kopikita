<?php
require_once __DIR__ . '/includes/auth.php';

$rows = db()->query('SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
$active = (string) ($_GET['collection'] ?? 'all');
if ($active !== 'all' && !isset(GALLERY_COLLECTIONS[$active])) {
    $active = 'all';
}

$pageTitle = 'Gallery';
$pageDesc = 'Galeri foto KopiKita: kopi, minuman, makanan, suasana kedai, dan dessert.';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <section class="panel page-hero reveal">
        <div class="breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a> / Gallery</div>
        <h1>Our <em>Gallery</em></h1>
        <p>Potongan momen dari dapur, bar, dan sudut-sudut hangat KopiKita.</p>
    </section>

    <div class="toolbar">
        <div class="chips" data-filter-group="#galleryGrid">
            <button type="button" class="chip <?= $active === 'all' ? 'active' : '' ?>" data-filter="all">All</button>
            <?php foreach (GALLERY_COLLECTIONS as $key => $label): ?>
                <button type="button" class="chip <?= $active === $key ? 'active' : '' ?>" data-filter="<?= e($key) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
        <span class="text-muted"><?= count($rows) ?> foto</span>
    </div>

    <?php if (!$rows): ?>
        <div class="empty-state"><div class="big">Galeri masih kosong</div>Foto akan segera ditambahkan.</div>
    <?php else: ?>
        <div class="gallery-grid section-pad" id="galleryGrid">
            <?php foreach ($rows as $g): $src = gallery_image_url($g['image']); ?>
                <figure class="gallery-item <?= $active !== 'all' && $active !== $g['collection'] ? 'hidden' : '' ?>"
                        data-cat="<?= e($g['collection']) ?>" data-full="<?= e($src) ?>" data-title="<?= e($g['title']) ?>" tabindex="0">
                    <img src="<?= e($src) ?>" alt="<?= e($g['title']) ?>" loading="lazy">
                    <figcaption><?= e($g['title']) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="lightbox" id="lightbox" role="dialog" aria-label="Pratinjau foto">
    <button type="button" class="lb-close icon-btn" aria-label="Tutup"><?= icon('close') ?></button>
    <button type="button" class="lb-prev round-arrow" aria-label="Sebelumnya"><?= icon('left') ?></button>
    <img src="" alt="">
    <button type="button" class="lb-next round-arrow" aria-label="Berikutnya"><?= icon('right') ?></button>
    <div class="lb-caption"></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * Newsletter "Stay up to date" + footer website publik/pelanggan.
 * Variabel opsional: $hideNewsletter, $hideFooter, $extraScripts (array nama file di assets/js)
 */
?>
</main>

<?php if (empty($hideFooter)): ?>
<?php if (empty($hideNewsletter)): ?>
<section class="section" id="newsletter">
    <div class="container">
        <div class="panel newsletter reveal">
            <div>
                <span class="eyebrow">Newsletter</span>
                <h2 class="mt-2">Stay <em>up to date</em></h2>
                <p>Dapatkan kabar menu baru, promo musiman, dan acara kecil di kedai langsung ke email Anda. Tanpa spam, janji.</p>
                <form class="newsletter-form" action="<?= e(url('actions/subscribe.php')) ?>" method="post" data-validate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('index.php')) ?>">
                    <label for="nlEmail" class="sr-only">Email</label>
                    <input type="email" id="nlEmail" name="email" placeholder="Alamat email Anda" required maxlength="150">
                    <button class="btn btn-primary" type="submit">Subscribe <span class="arrow">›</span></button>
                </form>
            </div>
            <div class="newsletter-art">
                <img src="<?= e(url('assets/images/ui/newsletter-cup.svg')) ?>" alt="Secangkir kopi dengan biji kopi" loading="lazy">
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<footer class="site-footer">
    <div class="container">
        <div class="panel footer-panel">
            <div class="footer-grid">
                <div>
                    <a href="<?= e(url('index.php')) ?>" class="brand">
                        <span class="brand-logo">K</span>
                        <span class="brand-text"><span class="brand-name">KopiKita</span><span class="brand-est">Est. <?= e(APP_EST) ?></span></span>
                    </a>
                    <p class="footer-slogan"><?= e(APP_SLOGAN) ?></p>
                    <p>Kedai kopi lokal yang menyajikan biji pilihan, diracik barista ahli, dalam ruang yang hangat.</p>
                </div>
                <div>
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                        <li><a href="<?= e(url('menu.php')) ?>">Menu</a></li>
                        <li><a href="<?= e(url('gallery.php')) ?>">Gallery</a></li>
                        <li><a href="<?= e(url('about.php')) ?>">About</a></li>
                        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h5>Information</h5>
                    <ul>
                        <?php foreach (SHOP_HOURS as $day => $hour): ?>
                            <li><?= e($day) ?>: <span class="text-cream"><?= e($hour) ?></span></li>
                        <?php endforeach; ?>
                        <li><?= e(SHOP_ADDRESS) ?></li>
                        <li><a href="<?= e(url('contact.php#faq')) ?>">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h5>Social Media</h5>
                    <p>Ikuti keseharian kedai kami.</p>
                    <div class="socials">
                        <a href="#" aria-label="Instagram"><?= icon('instagram') ?></a>
                        <a href="#" aria-label="Facebook"><?= icon('facebook') ?></a>
                        <a href="#" aria-label="TikTok"><?= icon('tiktok') ?></a>
                        <a href="https://wa.me/<?= e(SHOP_WHATSAPP) ?>" aria-label="WhatsApp" target="_blank" rel="noopener"><?= icon('whatsapp') ?></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> KopiKita. Hak cipta dilindungi.</span>
                <span>Dibuat dengan ☕ di Makassar</span>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>
<script src="<?= e(asset('js/script.js')) ?>"></script>
<?php foreach (($extraScripts ?? []) as $js): ?>
<script src="<?= e(asset('js/' . $js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>

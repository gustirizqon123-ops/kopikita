<?php
/**
 * Logika form admin untuk kategori & produk (dipakai create.php dan edit.php).
 * Setiap fungsi memproses POST, lalu menampilkan form.
 */

/** Validasi + simpan kategori. $cat = data lama (edit) atau null (tambah). */
function handle_category_form(?array $cat): void
{
    $pdo = db();
    $errors = [];
    $data = [
        'name'        => $cat['name'] ?? '',
        'description' => $cat['description'] ?? '',
        'icon'        => $cat['icon'] ?? 'coffee',
        'sort_order'  => $cat['sort_order'] ?? 0,
        'is_active'   => $cat['is_active'] ?? 1,
    ];

    if (is_post()) {
        verify_csrf();
        $data = [
            'name'        => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'icon'        => trim((string) ($_POST['icon'] ?? '')),
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($data['name'] === '' || mb_strlen($data['name']) > 80) {
            $errors[] = 'Nama kategori wajib diisi (maks. 80 karakter).';
        }
        if (mb_strlen($data['icon']) > 50) {
            $errors[] = 'Ikon maksimal 50 karakter.';
        }
        $st = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND id <> ?');
        $st->execute([$data['name'], $cat['id'] ?? 0]);
        if ($st->fetch()) {
            $errors[] = 'Nama kategori sudah digunakan.';
        }
        if (!$errors) {
            if ($cat) {
                $pdo->prepare('UPDATE categories SET name=?, slug=?, description=?, icon=?, sort_order=?, is_active=?, updated_at=? WHERE id=?')
                    ->execute([$data['name'], unique_slug($pdo, 'categories', slugify($data['name']), (int) $cat['id']), $data['description'] ?: null,
                        $data['icon'] ?: null, $data['sort_order'], $data['is_active'], now(), $cat['id']]);
                flash('success', 'Kategori diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO categories (name, slug, description, icon, sort_order, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$data['name'], unique_slug($pdo, 'categories', slugify($data['name'])), $data['description'] ?: null,
                        $data['icon'] ?: null, $data['sort_order'], $data['is_active'], now(), now()]);
                flash('success', 'Kategori ditambahkan.');
            }
            redirect('admin/categories/index.php');
        }
    }

    $pageTitle = $cat ? 'Edit Kategori' : 'Tambah Kategori';
    require APP_ROOT . '/includes/admin-header.php'; ?>
    <div class="card" style="max-width:640px">
        <div class="card-header"><h2><?= e($pageTitle) ?></h2></div>
        <div class="card-body">
            <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nama kategori *</label>
                    <input class="form-control" id="name" name="name" maxlength="80" required value="<?= e($data['name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($data['description']) ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="icon">Ikon</label>
                        <input class="form-control" id="icon" name="icon" maxlength="50" value="<?= e($data['icon']) ?>">
                        <div class="form-hint">Contoh: coffee, leaf, croissant, star.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sort_order">Urutan</label>
                        <input class="form-control" type="number" id="sort_order" name="sort_order" value="<?= (int) $data['sort_order'] ?>">
                    </div>
                </div>
                <label class="check"><input type="checkbox" name="is_active" <?= $data['is_active'] ? 'checked' : '' ?>> Aktif (tampil di website)</label>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/categories/index.php')) ?>">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <?php
    require APP_ROOT . '/includes/admin-footer.php';
}

/** Validasi + simpan produk (termasuk upload foto). */
function handle_product_form(?array $p): void
{
    $pdo = db();
    $errors = [];
    $cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
    $data = [
        'category_id' => $p['category_id'] ?? ($cats[0]['id'] ?? 0),
        'sku' => $p['sku'] ?? '', 'name' => $p['name'] ?? '', 'description' => $p['description'] ?? '',
        'price' => $p['price'] ?? '', 'stock' => $p['stock'] ?? 0, 'min_stock' => $p['min_stock'] ?? 10,
        'has_size_option' => $p['has_size_option'] ?? 1, 'is_best_seller' => $p['is_best_seller'] ?? 0,
        'is_available' => $p['is_available'] ?? 1,
    ];

    if (is_post()) {
        verify_csrf();
        $data = [
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'sku' => strtoupper(trim((string) ($_POST['sku'] ?? ''))),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => money_input($_POST['price'] ?? 0),
            'stock' => (int) ($_POST['stock'] ?? 0),
            'min_stock' => (int) ($_POST['min_stock'] ?? 0),
            'has_size_option' => isset($_POST['has_size_option']) ? 1 : 0,
            'is_best_seller' => isset($_POST['is_best_seller']) ? 1 : 0,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ];
        $id = (int) ($p['id'] ?? 0);

        $st = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
        $st->execute([$data['category_id']]);
        if (!$st->fetch()) {
            $errors[] = 'Kategori tidak valid.';
        }
        if ($data['name'] === '' || mb_strlen($data['name']) > 120) {
            $errors[] = 'Nama produk wajib diisi (maks. 120 karakter).';
        }
        if (!preg_match('/^[A-Z0-9-]{2,40}$/', $data['sku'])) {
            $errors[] = 'SKU wajib diisi (huruf besar, angka, tanda minus; 2-40 karakter).';
        }
        $st = $pdo->prepare('SELECT 1 FROM products WHERE sku = ? AND id <> ?');
        $st->execute([$data['sku'], $id]);
        if ($st->fetch()) {
            $errors[] = 'SKU sudah digunakan produk lain.';
        }
        if ($data['price'] <= 0 || $data['price'] > 99999999) {
            $errors[] = 'Harga harus lebih dari 0.';
        }
        if ($data['stock'] < 0 || $data['min_stock'] < 0) {
            $errors[] = 'Stok dan stok minimum tidak boleh negatif.';
        }

        // Upload foto (opsional)
        $newImage = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $up = upload_image($_FILES['image'], 'products');
            if ($up['ok']) {
                $newImage = $up['filename'];
            } else {
                $errors[] = $up['error'];
            }
        }

        if (!$errors) {
            $slug = unique_slug($pdo, 'products', slugify($data['name']), $id ?: null);
            if ($p) {
                $image = $newImage ?? $p['image'];
                $pdo->prepare('UPDATE products SET category_id=?, sku=?, name=?, slug=?, description=?, price=?, stock=?, min_stock=?, image=?,
                               has_size_option=?, is_best_seller=?, is_available=?, updated_at=? WHERE id=?')
                    ->execute([$data['category_id'], $data['sku'], $data['name'], $slug, $data['description'] ?: null, $data['price'],
                        $data['stock'], $data['min_stock'], $image, $data['has_size_option'], $data['is_best_seller'], $data['is_available'], now(), $id]);
                if ($newImage) {
                    delete_upload($p['image'], 'products');
                }
                flash('success', 'Produk diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO products (category_id, sku, name, slug, description, price, stock, min_stock, image,
                               has_size_option, is_best_seller, is_available, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$data['category_id'], $data['sku'], $data['name'], $slug, $data['description'] ?: null, $data['price'],
                        $data['stock'], $data['min_stock'], $newImage, $data['has_size_option'], $data['is_best_seller'], $data['is_available'], now(), now()]);
                flash('success', 'Produk ditambahkan.');
            }
            redirect('admin/products/index.php');
        }
        if ($newImage) {
            delete_upload($newImage, 'products'); // validasi lain gagal → buang file baru
        }
    }

    $pageTitle = $p ? 'Edit Produk' : 'Tambah Produk';
    require APP_ROOT . '/includes/admin-header.php'; ?>
    <div class="card" style="max-width:860px">
        <div class="card-header"><h2><?= e($pageTitle) ?></h2></div>
        <div class="card-body">
            <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
            <form method="post" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="name">Nama produk *</label>
                            <input class="form-control" id="name" name="name" maxlength="120" required value="<?= e($data['name']) ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="category_id">Kategori *</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <?php foreach ($cats as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>" <?= (int) $data['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="sku">SKU *</label>
                                <input class="form-control" id="sku" name="sku" maxlength="40" required value="<?= e($data['sku']) ?>" placeholder="CF-010">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="price">Harga (Rp) *</label>
                            <input class="form-control" id="price" name="price" inputmode="numeric" required value="<?= e($data['price'] !== '' ? (int) $data['price'] : '') ?>">
                            <div class="form-hint">Ukuran Large otomatis +<?= e(rupiah(SIZE_PRICES['large'])) ?>.</div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="stock">Stok</label>
                                <input class="form-control" type="number" min="0" id="stock" name="stock" value="<?= (int) $data['stock'] ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="min_stock">Stok minimum</label>
                                <input class="form-control" type="number" min="0" id="min_stock" name="min_stock" value="<?= (int) $data['min_stock'] ?>">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">Foto produk</label>
                            <img id="imgPreview" class="img-preview" src="<?= e(product_image_url($p['image'] ?? null)) ?>" alt="Pratinjau">
                            <input class="form-control mt-2" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-preview="imgPreview">
                            <div class="form-hint">JPG/PNG/WEBP, maks. 2 MB. Kosongkan untuk mempertahankan foto saat ini.</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($data['description']) ?></textarea>
                </div>
                <div class="flex" style="gap:22px;flex-wrap:wrap">
                    <label class="check"><input type="checkbox" name="has_size_option" <?= $data['has_size_option'] ? 'checked' : '' ?>> Ada pilihan ukuran</label>
                    <label class="check"><input type="checkbox" name="is_best_seller" <?= $data['is_best_seller'] ? 'checked' : '' ?>> Best seller</label>
                    <label class="check"><input type="checkbox" name="is_available" <?= $data['is_available'] ? 'checked' : '' ?>> Tersedia / aktif</label>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a class="btn btn-outline" href="<?= e(url('admin/products/index.php')) ?>">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <?php
    require APP_ROOT . '/includes/admin-footer.php';
}

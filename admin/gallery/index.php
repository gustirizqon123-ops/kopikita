<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin']);
$pdo = db();
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'upload') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $coll = (string) ($_POST['collection'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        if ($title === '' || mb_strlen($title) > 120) { $errors[] = 'Judul wajib diisi (maks. 120 karakter).'; }
        if (!isset(GALLERY_COLLECTIONS[$coll])) { $errors[] = 'Koleksi tidak valid.'; }
        if (!$errors) {
            $up = upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'gallery');
            if (!$up['ok']) {
                $errors[] = $up['error'];
            } else {
                $pdo->prepare('INSERT INTO gallery_images (title, collection, image, sort_order, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?)')
                    ->execute([$title, $coll, $up['filename'], $sort, now(), now()]);
                flash('success', 'Foto ditambahkan.');
                redirect('admin/gallery/index.php');
            }
        }
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE gallery_images SET is_active = 1 - is_active, updated_at = ? WHERE id = ?')->execute([now(), $id]);
        redirect('admin/gallery/index.php');
    } elseif ($action === 'sort') {
        $pdo->prepare('UPDATE gallery_images SET sort_order = ?, updated_at = ? WHERE id = ?')->execute([(int) ($_POST['sort_order'] ?? 0), now(), $id]);
        flash('success', 'Urutan disimpan.');
        redirect('admin/gallery/index.php');
    } elseif ($action === 'delete') {
        $st = $pdo->prepare('SELECT image FROM gallery_images WHERE id = ?');
        $st->execute([$id]);
        if ($img = $st->fetchColumn()) {
            $pdo->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$id]);
            delete_upload($img, 'gallery');
            flash('success', 'Foto dihapus.');
        }
        redirect('admin/gallery/index.php');
    }
}

$rows = $pdo->query('SELECT * FROM gallery_images ORDER BY collection, sort_order, id')->fetchAll();
$pageTitle = 'Kelola Galeri';
require __DIR__ . '/../../includes/admin-header.php';
?>
<div class="card mb-3">
    <div class="card-header"><h2>Tambah Foto</h2></div>
    <div class="card-body">
        <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" enctype="multipart/form-data" class="filter-bar">
            <?= csrf_field() ?><input type="hidden" name="action" value="upload">
            <div class="form-group"><label class="form-label" for="title">Judul</label><input class="form-control" id="title" name="title" maxlength="120" required></div>
            <div class="form-group"><label class="form-label" for="collection">Koleksi</label><select class="form-control" id="collection" name="collection">
                <?php foreach (GALLERY_COLLECTIONS as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="form-group" style="min-width:90px"><label class="form-label" for="sort_order">Urutan</label><input class="form-control" type="number" id="sort_order" name="sort_order" value="0"></div>
            <div class="form-group"><label class="form-label" for="image">Foto (JPG/PNG/WEBP, maks 2 MB)</label><input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" required></div>
            <button class="btn btn-primary btn-sm" type="submit"><?= icon('plus') ?> Upload</button>
        </form>
    </div>
</div>

<div class="card"><div class="card-header"><h2>Foto (<?= count($rows) ?>)</h2></div><div class="card-body">
    <?php if (!$rows): ?><p class="text-muted">Belum ada foto.</p><?php endif; ?>
    <div class="gallery-admin">
        <?php foreach ($rows as $g): ?>
            <div class="g-item <?= $g['is_active'] ? '' : 'off' ?>">
                <img src="<?= e(gallery_image_url($g['image'])) ?>" alt="<?= e($g['title']) ?>" loading="lazy">
                <div class="g-body">
                    <strong><?= e($g['title']) ?></strong>
                    <span class="text-muted"><?= e(GALLERY_COLLECTIONS[$g['collection']]) ?> · <?= $g['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                    <form method="post" class="inline-form flex" style="gap:6px;margin-top:8px">
                        <?= csrf_field() ?><input type="hidden" name="action" value="sort"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <input class="form-control" type="number" name="sort_order" value="<?= (int) $g['sort_order'] ?>" style="width:70px;padding:5px 8px" aria-label="Urutan">
                        <button class="btn btn-outline btn-sm" type="submit">Simpan</button>
                    </form>
                    <div class="actions" style="margin-top:8px">
                        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                            <button class="btn btn-outline btn-sm" type="submit"><?= $g['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                        <form method="post" class="inline-form" data-confirm="Hapus foto ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div></div>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>

<?php /** @var array $categories @var ?array $editing */ $maxMb = round(config('upload.max_bytes') / 1048576, 1); ?>
<div class="page-head"><div><h1>Kategori</h1><p>Kategori yang disembunyikan tidak dipaparkan kepada pembeli, termasuk produknya.</p></div></div>
<div class="split split--wide-side">
  <section>
    <?php if (!$categories): ?>
      <?= view_partial('empty', ['icon' => 'tag', 'title' => 'Belum ada kategori', 'text' => 'Cipta kategori pertama menggunakan borang di sebelah.', 'compact' => true]) ?>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table table--stack">
          <thead><tr><th>Kategori</th><th>Produk</th><th>Susunan</th><th>Paparan</th><th><span class="sr-only">Tindakan</span></th></tr></thead>
          <tbody>
            <?php foreach ($categories as $c): ?>
              <tr>
                <td class="cell-main"><div class="cluster" style="flex-wrap:nowrap"><span class="category-tile__icon"><?php if ($c['image_path']): ?><img src="<?= e(upload_url($c['image_path'])) ?>" alt=""><?php else: ?><?= icon('tag', 18) ?><?php endif; ?></span><div><strong><?= e($c['name']) ?></strong><div class="table-sub"><?= e((string) $c['description']) ?></div></div></div></td>
                <td data-label="Produk"><?= (int) $c['product_count'] ?></td>
                <td data-label="Susunan"><?= (int) $c['sort_order'] ?></td>
                <td data-label="Paparan"><?= (int) $c['is_visible'] ? badge('active', 'Dipaparkan') : badge('hidden', 'Disembunyikan') ?></td>
                <td data-label="">
                  <div class="btn-row" style="flex-wrap:nowrap">
                    <a class="btn btn--sm" href="<?= e(url('/admin/categories', ['edit' => $c['id']])) ?>"><?= icon('edit', 15) ?> Edit</a>
                    <?php if ((int) $c['product_count'] === 0): ?>
                      <form method="post" action="<?= e(url('/admin/categories/' . $c['id'] . '/delete')) ?>" data-confirm="Padam kategori “<?= e($c['name']) ?>”?" data-confirm-ok="Padam" data-confirm-tone="danger">
                        <?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit" aria-label="Padam <?= e($c['name']) ?>"><?= icon('trash', 15) ?></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="hint mt-2">Kategori yang masih mempunyai produk tidak boleh dipadam. Sembunyikannya atau pindahkan produk dahulu.</p>
    <?php endif; ?>
  </section>

  <aside class="panel sticky">
    <h2><?= $editing ? 'Edit kategori' : 'Kategori baharu' ?></h2>
    <?= view_partial('validation_summary') ?>
    <form method="post" action="<?= e(url($editing ? '/admin/categories/' . $editing['id'] : '/admin/categories')) ?>" enctype="multipart/form-data" class="form mt-4" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="cname">Nama</label><input class="input<?= error_for('name') ? ' is-invalid' : '' ?>" id="cname" name="name" value="<?= e(old('name', $editing['name'] ?? '')) ?>" required maxlength="60"><?= view_partial('field_error', ['name' => 'name']) ?></div>
      <div class="field"><label for="cdesc">Penerangan</label><input class="input" id="cdesc" name="description" value="<?= e(old('description', (string) ($editing['description'] ?? ''))) ?>" maxlength="255"></div>
      <div class="field"><label for="csort">Susunan paparan</label><input class="input" id="csort" type="number" name="sort_order" min="0" max="999" value="<?= e(old('sort_order', (string) ($editing['sort_order'] ?? '0'))) ?>"><span class="hint">Nombor lebih kecil dipaparkan dahulu.</span></div>
      <div class="field"><label for="cimg">Gambar (pilihan)</label><input id="cimg" type="file" name="image" accept="image/jpeg,image/png,image/webp"><span class="hint">Maksimum <?= e($maxMb) ?> MB.</span><?= view_partial('field_error', ['name' => 'image']) ?></div>
      <label class="check"><input type="checkbox" name="is_visible" value="1" <?= ($editing ? (int) $editing['is_visible'] : 1) ? 'checked' : '' ?>> Paparkan kepada pembeli</label>
      <div class="btn-row">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Simpan perubahan' : 'Cipta kategori' ?></button>
        <?php if ($editing): ?><a class="btn btn--ghost" href="<?= e(url('/admin/categories')) ?>">Batal</a><?php endif; ?>
      </div>
    </form>
  </aside>
</div>

<?php
/** @var ?array $product @var array $images @var array $categories @var bool $needsApproval */
$editing = $product !== null;
$action = $editing ? '/seller/products/' . $product['id'] : '/seller/products';
$val = fn (string $k, $default = '') => old($k, $editing ? (string) ($product[$k] ?? '') : $default);
$priceValue = old('price', $editing ? number_format((float) $product['price'], 2, '.', '') : '');
$maxMb = round(config('upload.max_bytes') / 1048576, 1);
$available = $editing ? (int) $product['is_available'] === 1 : true;
if (errors()) {
    $available = old('is_available') === '1';
}
?>
<nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/seller/products')) ?>">Pengurusan Produk</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= $editing ? e($product['name']) : 'Tambah produk' ?></span></nav>
<div class="page-head">
  <div>
    <h1><?= $editing ? 'Edit produk' : 'Tambah produk' ?></h1>
    <?php if ($editing): ?>
      <p class="cluster">Status: <?= badge($product['approval_status']) ?> <?= $product['archived_at'] ? badge('archived', 'Diarkib') : '' ?></p>
    <?php else: ?>
      <p><?= $needsApproval ? 'Produk baharu akan disemak oleh pentadbir sebelum dipaparkan kepada pembeli.' : 'Produk akan terus dipaparkan selepas disimpan.' ?></p>
    <?php endif; ?>
  </div>
  <?php if ($editing && $product['approval_status'] === 'approved' && !$product['archived_at']): ?>
    <a class="btn" href="<?= e(url('/product/' . $product['slug'])) ?>"><?= icon('external', 16) ?> Lihat di kedai</a>
  <?php endif; ?>
</div>

<?php if ($editing && $product['moderation_note'] && in_array($product['approval_status'], ['rejected', 'suspended'], true)): ?>
  <div class="alert alert--error mb-4"><?= icon('alert', 18) ?><div class="alert__body"><strong>Nota pentadbir:</strong> <?= e($product['moderation_note']) ?><?= $product['approval_status'] === 'rejected' ? ' Simpan perubahan untuk menghantar semula untuk kelulusan.' : '' ?></div></div>
<?php endif; ?>

<?= view_partial('validation_summary') ?>

<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="split split--wide-side mt-4" novalidate>
  <?= csrf_field() ?>
  <section class="panel form">
    <div class="field">
      <label for="name">Nama produk <span class="req" aria-hidden="true">*</span></label>
      <input class="input<?= error_for('name') ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= e($val('name')) ?>" required maxlength="120">
      <?= view_partial('field_error', ['name' => 'name']) ?>
    </div>
    <div class="field">
      <label for="category_id">Kategori <span class="req" aria-hidden="true">*</span></label>
      <select class="select<?= error_for('category_id') ? ' is-invalid' : '' ?>" id="category_id" name="category_id" required>
        <option value="">Pilih kategori</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $val('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <?= view_partial('field_error', ['name' => 'category_id']) ?>
    </div>
    <div class="field">
      <label for="description">Penerangan <span class="req" aria-hidden="true">*</span></label>
      <textarea class="textarea<?= error_for('description') ? ' is-invalid' : '' ?>" id="description" name="description" required maxlength="3000" rows="6" placeholder="Bahan, saiz hidangan, pilihan rasa, alahan…"><?= e($val('description')) ?></textarea>
      <?php if ($editing && $needsApproval && $product['approval_status'] === 'approved'): ?><span class="hint">Mengubah nama atau penerangan akan menghantar produk semula untuk kelulusan.</span><?php endif; ?>
      <?= view_partial('field_error', ['name' => 'description']) ?>
    </div>
    <div class="form-grid form-grid--2">
      <div class="field">
        <label for="price">Harga <span class="req" aria-hidden="true">*</span></label>
        <div class="input-group"><span class="input-group__addon">RM</span><input class="input<?= error_for('price') ? ' is-invalid' : '' ?>" id="price" name="price" value="<?= e($priceValue) ?>" required inputmode="decimal" placeholder="0.00"></div>
        <?= view_partial('field_error', ['name' => 'price']) ?>
      </div>
      <div class="field">
        <label for="stock">Stok <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= error_for('stock') ? ' is-invalid' : '' ?>" id="stock" type="number" name="stock" min="0" max="99999" value="<?= e($val('stock', '0')) ?>" required inputmode="numeric">
        <span class="hint">Stok ditolak automatik apabila pesanan dibuat, dan dipulangkan jika pesanan dibatalkan.</span>
        <?= view_partial('field_error', ['name' => 'stock']) ?>
      </div>
    </div>
    <div class="field">
      <label for="sku">SKU (pilihan)</label>
      <input class="input" id="sku" name="sku" value="<?= e($val('sku')) ?>" maxlength="40" placeholder="Kod dalaman anda">
    </div>
    <label class="check"><input type="checkbox" name="is_available" value="1" <?= $available ? 'checked' : '' ?>> <span><strong>Dijual sekarang</strong><br><span class="muted">Nyahtanda untuk menyembunyikan produk sementara, contohnya apabila gerai tutup.</span></span></label>
  </section>

  <aside class="stack">
    <section class="panel">
      <h2>Gambar</h2>
      <?php if ($images): ?>
        <div class="file-previews mb-4">
          <?php foreach ($images as $img): ?>
            <div style="text-align:center">
              <img src="<?= e(upload_url($img['path'])) ?>" alt="Gambar produk" style="width:96px;height:72px;<?= (int) $img['is_primary'] ? 'outline:3px solid var(--brand);outline-offset:1px' : '' ?>">
              <div class="cluster mt-2" style="justify-content:center;gap:2px">
                <?php if (!(int) $img['is_primary']): ?>
                  <button class="btn btn--ghost btn--sm" type="submit" form="primary-<?= (int) $img['id'] ?>" title="Jadikan gambar utama" aria-label="Jadikan gambar utama"><?= icon('star', 14) ?></button>
                <?php else: ?><span class="tag">Utama</span><?php endif; ?>
                <button class="btn btn--ghost btn--sm" type="submit" form="delete-<?= (int) $img['id'] ?>" title="Padam gambar" aria-label="Padam gambar"><?= icon('trash', 14) ?></button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="file-drop">
        <label class="label" for="images"><?= icon('upload', 16) ?> <?= $images ? 'Tambah gambar' : 'Muat naik gambar' ?></label>
        <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple data-preview="img-preview" data-max-bytes="<?= (int) config('upload.max_bytes') ?>">
        <span class="hint">Sehingga 5 gambar. JPG, PNG atau WebP, maksimum <?= e($maxMb) ?> MB setiap satu. Gambar pertama menjadi gambar utama.</span>
        <div class="file-previews" id="img-preview"></div>
      </div>
      <?= view_partial('field_error', ['name' => 'images']) ?>
    </section>
    <div class="panel panel--tight stack-sm">
      <button class="btn btn--primary btn--lg btn--block" type="submit"><?= $editing ? 'Simpan perubahan' : 'Simpan produk' ?></button>
      <a class="btn btn--ghost btn--block" href="<?= e(url('/seller/products')) ?>">Batal</a>
    </div>
  </aside>
</form>

<?php /* Image actions live in separate forms (forms cannot be nested); the buttons above target them with form="" */ ?>
<?php foreach ($images as $img): ?>
  <form id="delete-<?= (int) $img['id'] ?>" method="post" action="<?= e(url('/seller/products/' . $product['id'] . '/images/' . $img['id'] . '/delete')) ?>" data-confirm="Padam gambar ini?" data-confirm-ok="Padam" data-confirm-tone="danger" hidden><?= csrf_field() ?></form>
  <form id="primary-<?= (int) $img['id'] ?>" method="post" action="<?= e(url('/seller/products/' . $product['id'] . '/images/' . $img['id'] . '/primary')) ?>" hidden><?= csrf_field() ?></form>
<?php endforeach; ?>

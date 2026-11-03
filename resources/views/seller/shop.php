<?php /** @var array $shop */ $maxMb = round(config('upload.max_bytes') / 1048576, 1); ?>
<div class="page-head">
  <div><h1>Profil kedai</h1><p>Maklumat ini dipaparkan di halaman kedai dan pada setiap pesanan.</p></div>
  <a class="btn" href="<?= e(url('/shop/' . $shop['slug'])) ?>"><?= icon('external', 16) ?> Lihat halaman kedai</a>
</div>
<?= view_partial('validation_summary') ?>
<form method="post" action="<?= e(url('/seller/shop')) ?>" enctype="multipart/form-data" class="panel form mt-4" style="max-width:820px" novalidate>
  <?= csrf_field() ?>
  <div class="cluster">
    <?php if ($shop['logo_path']): ?><img class="shop-logo shop-logo--lg" src="<?= e(upload_url($shop['logo_path'])) ?>" alt="Logo semasa"><?php else: ?><span class="shop-logo shop-logo--lg"><?= e(mb_strtoupper(mb_substr($shop['shop_name'], 0, 1))) ?></span><?php endif; ?>
    <div class="field" style="flex:1;min-width:220px">
      <label for="logo">Tukar logo</label>
      <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-preview="logo-preview" data-max-bytes="<?= (int) config('upload.max_bytes') ?>">
      <span class="hint">JPG, PNG atau WebP, maksimum <?= e($maxMb) ?> MB.</span>
      <div class="file-previews" id="logo-preview"></div>
      <?= view_partial('field_error', ['name' => 'logo']) ?>
    </div>
  </div>
  <div class="field">
    <label for="shop_name">Nama kedai</label>
    <input class="input<?= error_for('shop_name') ? ' is-invalid' : '' ?>" id="shop_name" name="shop_name" value="<?= e(old('shop_name', $shop['shop_name'])) ?>" required maxlength="100">
    <?= view_partial('field_error', ['name' => 'shop_name']) ?>
  </div>
  <div class="field">
    <label for="campus_name">Kolej / kampus</label>
    <input class="input<?= error_for('campus_name') ? ' is-invalid' : '' ?>" id="campus_name" name="campus_name" value="<?= e(old('campus_name', $shop['campus_name'] ?? '')) ?>" required maxlength="150">
    <span class="hint">Nama penuh kolej vokasional tempat kedai ini beroperasi.</span>
    <?= view_partial('field_error', ['name' => 'campus_name']) ?>
  </div>
  <div class="field">
    <label for="description">Penerangan</label>
    <textarea class="textarea<?= error_for('description') ? ' is-invalid' : '' ?>" id="description" name="description" required maxlength="1000"><?= e(old('description', $shop['description'])) ?></textarea>
    <?= view_partial('field_error', ['name' => 'description']) ?>
  </div>
  <div class="form-grid form-grid--2">
    <div class="field">
      <label for="phone">Telefon kedai</label>
      <input class="input<?= error_for('phone') ? ' is-invalid' : '' ?>" id="phone" type="tel" name="phone" value="<?= e(old('phone', $shop['phone'])) ?>" required>
      <?= view_partial('field_error', ['name' => 'phone']) ?>
    </div>
    <div class="field">
      <label for="collection_location">Lokasi pengambilan</label>
      <input class="input<?= error_for('collection_location') ? ' is-invalid' : '' ?>" id="collection_location" name="collection_location" value="<?= e(old('collection_location', $shop['collection_location'])) ?>" required maxlength="150">
      <?= view_partial('field_error', ['name' => 'collection_location']) ?>
    </div>
  </div>
  <div class="field">
    <label for="collection_hours">Waktu operasi</label>
    <input class="input" id="collection_hours" name="collection_hours" value="<?= e(old('collection_hours', (string) $shop['collection_hours'])) ?>" maxlength="100">
  </div>
  <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan profil kedai</button></div>
</form>

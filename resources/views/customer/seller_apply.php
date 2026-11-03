<?php
/** @var ?array $profile */
$status = $profile['status'] ?? null;
$canEdit = $status === null || $status === 'rejected';
$maxMb = round(config('upload.max_bytes') / 1048576, 1);
?>
<div class="container page" style="max-width:820px">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Buka kedai</span></nav>
  <div class="page-head"><div><h1>Buka kedai di E-FoodHub</h1><p>Isi maklumat kedai dan institusi anda. E-FoodHub menyokong penjual daripada kolej vokasional yang berbeza. Pentadbir akan menyemak permohonan sebelum kedai diaktifkan.</p></div></div>

  <?php if ($status === 'pending'): ?>
    <div class="empty">
      <div class="empty__icon"><?= icon('clock', 30) ?></div>
      <h2>Permohonan sedang disemak</h2>
      <p>“<?= e($profile['shop_name']) ?>” dihantar pada <?= e(format_date($profile['updated_at'])) ?>. Anda akan menerima notifikasi apabila pentadbir membuat keputusan.</p>
      <a class="btn" href="<?= e(url('/notifications')) ?>">Lihat notifikasi</a>
    </div>
  <?php elseif ($status === 'approved'): ?>
    <div class="empty">
      <div class="empty__icon"><?= icon('check', 30) ?></div>
      <h2>Kedai anda aktif</h2>
      <p>“<?= e($profile['shop_name']) ?>” telah diluluskan.</p>
      <a class="btn btn--primary" href="<?= e(url('/seller')) ?>">Buka Dashboard Penjual</a>
    </div>
  <?php elseif ($status === 'suspended'): ?>
    <div class="empty">
      <div class="empty__icon"><?= icon('lock', 30) ?></div>
      <h2>Kedai anda digantung</h2>
      <p><?= $profile['review_note'] ? 'Sebab: ' . e($profile['review_note']) . '. ' : '' ?>Produk anda tidak dipaparkan kepada pembeli. Hubungi pentadbir di <?= e(setting('contact_email')) ?> untuk maklumat lanjut.</p>
    </div>
  <?php endif; ?>

  <?php if ($canEdit): ?>
    <?php if ($status === 'rejected'): ?>
      <div class="alert alert--error mb-4"><?= icon('alert', 18) ?><div class="alert__body"><strong>Permohonan sebelum ini ditolak.</strong> <?= $profile['review_note'] ? 'Sebab: ' . e($profile['review_note']) . '. ' : '' ?>Kemas kini maklumat di bawah dan hantar semula.</div></div>
    <?php endif; ?>
    <?= view_partial('validation_summary') ?>
    <form method="post" action="<?= e(url('/seller/apply')) ?>" enctype="multipart/form-data" class="panel form mt-4" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="shop_name">Nama kedai <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= error_for('shop_name') ? ' is-invalid' : '' ?>" id="shop_name" name="shop_name" value="<?= e(old('shop_name', $profile['shop_name'] ?? '')) ?>" required maxlength="100">
        <?= view_partial('field_error', ['name' => 'shop_name']) ?>
      </div>
      <div class="field">
        <label for="campus_name">Kolej / kampus <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= error_for('campus_name') ? ' is-invalid' : '' ?>" id="campus_name" name="campus_name" value="<?= e(old('campus_name', $profile['campus_name'] ?? '')) ?>" required maxlength="150" placeholder="Contoh: Kolej Vokasional Pasir Mas">
        <span class="hint">Nyatakan nama penuh institusi tempat kedai anda beroperasi.</span>
        <?= view_partial('field_error', ['name' => 'campus_name']) ?>
      </div>
      <div class="field">
        <label for="description">Penerangan kedai <span class="req" aria-hidden="true">*</span></label>
        <textarea class="textarea<?= error_for('description') ? ' is-invalid' : '' ?>" id="description" name="description" required maxlength="1000" placeholder="Apa yang anda jual, dan apa yang istimewa tentangnya?"><?= e(old('description', $profile['description'] ?? '')) ?></textarea>
        <span class="hint">20 hingga 1000 aksara.</span>
        <?= view_partial('field_error', ['name' => 'description']) ?>
      </div>
      <div class="form-grid form-grid--2">
        <div class="field">
          <label for="phone">Telefon kedai <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= error_for('phone') ? ' is-invalid' : '' ?>" id="phone" type="tel" name="phone" value="<?= e(old('phone', $profile['phone'] ?? (string) auth_user()['phone'])) ?>" required>
          <span class="hint">Dipaparkan kepada pembeli yang log masuk.</span>
          <?= view_partial('field_error', ['name' => 'phone']) ?>
        </div>
        <div class="field">
          <label for="collection_location">Lokasi pengambilan <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= error_for('collection_location') ? ' is-invalid' : '' ?>" id="collection_location" name="collection_location" value="<?= e(old('collection_location', $profile['collection_location'] ?? '')) ?>" required maxlength="150" placeholder="Contoh: Kantin Utama, Gerai 2">
          <?= view_partial('field_error', ['name' => 'collection_location']) ?>
        </div>
      </div>
      <div class="field">
        <label for="collection_hours">Waktu operasi (pilihan)</label>
        <input class="input" id="collection_hours" name="collection_hours" value="<?= e(old('collection_hours', $profile['collection_hours'] ?? '')) ?>" maxlength="100" placeholder="Contoh: Isnin–Jumaat, 8 pagi – 2 petang">
      </div>
      <div class="field">
        <label for="logo">Logo kedai (pilihan)</label>
        <div class="file-drop">
          <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-preview="logo-preview" data-max-bytes="<?= (int) config('upload.max_bytes') ?>">
          <span class="hint">JPG, PNG atau WebP, maksimum <?= e($maxMb) ?> MB.</span>
          <div class="file-previews" id="logo-preview"></div>
        </div>
        <?= view_partial('field_error', ['name' => 'logo']) ?>
      </div>
      <label class="check"><input type="checkbox" name="agree" value="1"> <span>Saya mengesahkan maklumat ini benar, dan saya akan mematuhi <a href="<?= e(url('/info/terms')) ?>" target="_blank">terma penggunaan</a> serta peraturan kolej tentang jualan di kampus.</span></label>
      <?= view_partial('field_error', ['name' => 'agree']) ?>
      <div class="btn-row"><button class="btn btn--primary btn--lg" type="submit">Hantar permohonan</button></div>
    </form>
  <?php endif; ?>
</div>

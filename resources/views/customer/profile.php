<?php
/** @var array $user @var ?array $stats @var ?array $shop */
$inDashboard = $user['role'] !== 'customer';
?>
<div class="<?= $inDashboard ? '' : 'container page' ?>" style="max-width:860px">
  <?php if (!$inDashboard): ?>
    <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Akaun saya</span></nav>
  <?php endif; ?>
  <div class="page-head"><div><h1>Akaun saya</h1><p><?= e(role_label($user['role'])) ?> sejak <?= e(format_date($user['created_at'], false)) ?></p></div></div>

  <?php if ($stats && (int) $stats['orders'] > 0): ?>
    <div class="stats mb-4" style="grid-template-columns:repeat(2,minmax(0,1fr))">
      <a class="stat" href="<?= e(url('/orders')) ?>"><span class="stat__label"><?= icon('receipt', 14) ?> Jumlah pesanan</span><span class="stat__value"><?= (int) $stats['orders'] ?></span></a>
      <a class="stat" href="<?= e(url('/orders', ['status' => 'open'])) ?>"><span class="stat__label"><?= icon('clock', 14) ?> Dalam proses</span><span class="stat__value"><?= (int) $stats['open_parts'] ?></span></a>
    </div>
  <?php endif; ?>

  <section class="panel">
    <h2>Maklumat peribadi</h2>
    <?= view_partial('validation_summary') ?>
    <form method="post" action="<?= e(url('/account')) ?>" class="form mt-4" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid form-grid--2">
        <div class="field">
          <label for="name">Nama penuh</label>
          <input class="input<?= error_for('name') ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= e(old('name', $user['name'])) ?>" required maxlength="100" autocomplete="name">
          <?= view_partial('field_error', ['name' => 'name']) ?>
        </div>
        <div class="field">
          <label for="phone">Nombor telefon</label>
          <input class="input<?= error_for('phone') ? ' is-invalid' : '' ?>" id="phone" type="tel" name="phone" value="<?= e(old('phone', (string) $user['phone'])) ?>" required autocomplete="tel">
          <span class="hint">Digunakan sebagai nombor lalai semasa membuat pesanan.</span>
          <?= view_partial('field_error', ['name' => 'phone']) ?>
        </div>
      </div>
      <div class="field">
        <label for="email">E-mel</label>
        <input class="input" id="email" type="email" value="<?= e($user['email']) ?>" disabled aria-describedby="email-hint">
        <span class="hint" id="email-hint">E-mel digunakan untuk log masuk dan tidak boleh ditukar sendiri. Hubungi pentadbir jika perlu.</span>
      </div>
      <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan perubahan</button></div>
    </form>
  </section>

  <section class="panel">
    <div class="panel__head"><h2>Keselamatan</h2><a class="btn btn--sm" href="<?= e(url('/account/password')) ?>"><?= icon('lock', 15) ?> Tukar kata laluan</a></div>
    <dl class="facts mt-0">
      <dt>Status akaun</dt><dd><?= badge($user['status']) ?></dd>
      <dt>Log masuk terakhir</dt><dd><?= e(format_date($user['last_login_at'])) ?></dd>
    </dl>
  </section>

  <?php if ($user['role'] !== 'admin'): ?>
    <section class="panel">
      <div class="panel__head"><h2>Kedai</h2></div>
      <?php if (!$shop): ?>
        <p>Anda belum mempunyai kedai. Pelajar, warga kolej dan pengusaha gerai kolej vokasional boleh memohon untuk menjual di E-FoodHub.</p>
        <a class="btn btn--primary" href="<?= e(url('/seller/apply')) ?>">Mohon buka kedai</a>
      <?php else: ?>
        <p class="cluster"><strong><?= e($shop['shop_name']) ?></strong> <?= badge($shop['status']) ?></p>
        <a class="btn" href="<?= e(url($shop['status'] === 'approved' ? '/seller' : '/seller/apply')) ?>"><?= $shop['status'] === 'approved' ? 'Buka Dashboard Penjual' : 'Lihat status permohonan' ?></a>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

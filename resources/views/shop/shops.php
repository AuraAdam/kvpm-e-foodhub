<?php /** @var array $shops @var array $campuses @var string $selectedCampus */ ?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Kedai</span></nav>
  <div class="page-head"><div><h1>Kedai</h1><p>Kedai daripada kolej vokasional yang telah diluluskan oleh pentadbir.</p></div></div>
  <form method="get" action="<?= e(url('/shops')) ?>" class="toolbar mb-4">
    <label class="field" for="campus-filter" style="margin:0;min-width:min(100%,340px)">
      <span class="small">Tapis mengikut kolej / kampus</span>
      <select class="select" id="campus-filter" name="campus">
        <option value="">Semua kolej vokasional</option>
        <?php foreach ($campuses as $campus): ?><option value="<?= e($campus['campus_name']) ?>" <?= $selectedCampus === $campus['campus_name'] ? 'selected' : '' ?>><?= e($campus['campus_name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="btn btn--dark" type="submit">Tapis kedai</button>
    <?php if ($selectedCampus !== ''): ?><a class="btn btn--ghost" href="<?= e(url('/shops')) ?>">Kosongkan</a><?php endif; ?>
  </form>
  <?php if ($shops): ?>
    <div class="shop-grid">
      <?php foreach ($shops as $s): ?>
        <a class="shop-card" href="<?= e(url('/shop/' . $s['slug'])) ?>">
          <div class="cluster">
            <?php if ($s['logo_path']): ?><img class="shop-logo" src="<?= e(upload_url($s['logo_path'])) ?>" alt=""><?php else: ?><span class="shop-logo"><?= e(mb_strtoupper(mb_substr($s['shop_name'], 0, 1))) ?></span><?php endif; ?>
            <div><strong><?= e($s['shop_name']) ?></strong><br><small class="muted"><?= e($s['campus_name'] ?? 'Kolej vokasional') ?></small><br><small class="muted"><?= (int) $s['product_count'] ?> produk</small></div>
          </div>
          <p><?= e($s['description']) ?></p>
          <small class="muted"><?= icon('pin', 13) ?> <?= e($s['collection_location']) ?></small>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <?= view_partial('empty', ['icon' => 'store', 'title' => 'Belum ada kedai aktif', 'text' => $selectedCampus !== '' ? 'Tiada kedai aktif untuk kolej ini buat masa sekarang.' : 'Kedai akan dipaparkan selepas permohonan diluluskan.', 'actionUrl' => '/seller/apply', 'actionLabel' => 'Mohon buka kedai']) ?>
  <?php endif; ?>
</div>

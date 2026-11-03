<?php
/** @var array $shop @var array $filters @var array $products @var array $pagination @var array $rating @var array $sorts */
use App\Core\Auth;
?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><a href="<?= e(url('/shops')) ?>">Kedai</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($shop['shop_name']) ?></span></nav>

  <section class="shop-hero">
    <?php if ($shop['logo_path']): ?><img class="shop-logo shop-logo--lg" src="<?= e(upload_url($shop['logo_path'])) ?>" alt="Logo <?= e($shop['shop_name']) ?>"><?php else: ?><span class="shop-logo shop-logo--lg" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($shop['shop_name'], 0, 1))) ?></span><?php endif; ?>
    <div class="shop-hero__body">
      <div class="cluster"><h1><?= e($shop['shop_name']) ?></h1><?= badge('approved', 'Kedai disahkan') ?></div>
      <p><?= e($shop['description']) ?></p>
      <p class="small muted"><?= icon('graduation-cap', 15) ?> <?= e($shop['campus_name'] ?? 'Kolej vokasional') ?></p>
      <div class="shop-meta">
        <span><?= icon('pin', 16) ?> <?= e($shop['collection_location']) ?></span>
        <?php if ($shop['collection_hours']): ?><span><?= icon('clock', 16) ?> <?= e($shop['collection_hours']) ?></span><?php endif; ?>
        <?php if (Auth::check()): ?><span><?= icon('phone', 16) ?> <?= e($shop['phone']) ?></span><?php endif; ?>
        <?php if ((int) $rating['total'] > 0): ?><span><?= stars((float) $rating['avg_rating'], 14) ?> <?= number_format((float) $rating['avg_rating'], 1) ?> (<?= (int) $rating['total'] ?> ulasan)</span><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section" style="margin-top:var(--s5)">
    <div class="toolbar">
      <form method="get" action="<?= e(url('/shop/' . $shop['slug'])) ?>" class="cluster" role="search">
        <label class="sr-only" for="shop-q">Cari dalam kedai</label>
        <div class="search-field"><?= icon('search', 18) ?><input id="shop-q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari dalam kedai ini" maxlength="80"></div>
        <label class="check"><input type="checkbox" name="availability" value="in_stock" <?= $filters['availability'] ? 'checked' : '' ?>> Ada stok</label>
        <select class="select" name="sort" aria-label="Susun">
          <?php foreach ($sorts as $key => $s): ?><option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($s['label']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--dark" type="submit">Tapis</button>
      </form>
      <span class="result-count"><?= (int) $pagination['total'] ?> produk</span>
    </div>
    <?php if ($products): ?>
      <div class="product-grid product-grid--4">
        <?php foreach ($products as $p): ?><?= view_partial('product_card', ['p' => $p]) ?><?php endforeach; ?>
      </div>
      <?= view_partial('pagination', ['pagination' => $pagination]) ?>
    <?php else: ?>
      <?= view_partial('empty', ['icon' => 'package', 'title' => 'Tiada produk ditemui', 'text' => $filters['q'] !== '' ? 'Cuba kata kunci lain.' : 'Kedai ini belum mempunyai produk yang dijual.', 'compact' => true]) ?>
    <?php endif; ?>
  </section>
</div>

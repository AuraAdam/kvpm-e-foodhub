<?php
/**
 * @var array $filters @var array $products @var array $pagination @var array $categories
 * @var ?array $currentCategory @var array $sorts @var string $title
 */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['min'] !== '' || $filters['max'] !== '' || $filters['availability'] !== '';
$keep = fn (array $extra) => array_merge(array_filter($filters, fn ($v) => $v !== ''), $extra);
?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan">
    <a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?>
    <?php if ($currentCategory): ?>
      <a href="<?= e(url('/products')) ?>">Produk</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($currentCategory['name']) ?></span>
    <?php else: ?>
      <span aria-current="page">Produk</span>
    <?php endif; ?>
  </nav>
  <div class="page-head">
    <div>
      <h1><?= e($title) ?></h1>
      <?php if ($currentCategory && $currentCategory['description']): ?><p><?= e($currentCategory['description']) ?></p><?php endif; ?>
    </div>
  </div>

  <div class="catalogue">
    <div>
      <button type="button" class="btn filters-toggle btn--block mb-4" data-filters-toggle aria-controls="filters-panel" aria-expanded="false"><?= icon('filter', 18) ?> Tapis produk</button>
      <div class="filters-panel" id="filters-panel">
        <form class="filters panel panel--tight" method="get" action="<?= e(url('/products')) ?>">
          <div>
            <h3>Kategori</h3>
            <div class="cat-list">
              <a href="<?= e(url('/products', $keep(['category' => '', 'page' => '']))) ?>" class="<?= $filters['category'] === '' ? 'is-active' : '' ?>"><span>Semua</span></a>
              <?php foreach ($categories as $c): ?>
                <a href="<?= e(url('/products', $keep(['category' => $c['slug'], 'page' => '']))) ?>" class="<?= $filters['category'] === $c['slug'] ? 'is-active' : '' ?>"><span><?= e($c['name']) ?></span><small class="muted"><?= (int) $c['product_count'] ?></small></a>
              <?php endforeach; ?>
            </div>
          </div>
          <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
          <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
          <div class="field">
            <label for="f-q">Kata kunci</label>
            <input class="input" id="f-q" type="search" name="q" value="<?= e($filters['q']) ?>" maxlength="80" placeholder="Nama produk atau kedai">
          </div>
          <div>
            <h3 id="price-label">Harga (RM)</h3>
            <div class="price-range" role="group" aria-labelledby="price-label">
              <div class="field"><label class="sr-only" for="f-min">Minimum</label><input class="input" id="f-min" type="number" name="min" min="0" step="0.5" inputmode="decimal" value="<?= e($filters['min']) ?>" placeholder="Min"></div>
              <div class="field"><label class="sr-only" for="f-max">Maksimum</label><input class="input" id="f-max" type="number" name="max" min="0" step="0.5" inputmode="decimal" value="<?= e($filters['max']) ?>" placeholder="Maks"></div>
            </div>
          </div>
          <label class="check"><input type="checkbox" name="availability" value="in_stock" <?= $filters['availability'] === 'in_stock' ? 'checked' : '' ?>> Hanya produk yang ada stok</label>
          <button type="submit" class="btn btn--dark btn--block">Tapis</button>
          <?php if ($hasFilters): ?><a class="btn btn--ghost btn--block" href="<?= e(url('/products')) ?>">Kosongkan penapis</a><?php endif; ?>
        </form>
      </div>
    </div>

    <div>
      <div class="toolbar">
        <span class="result-count"><?= (int) $pagination['total'] ?> produk dijumpai</span>
        <form method="get" action="<?= e(url('/products')) ?>" class="cluster">
          <?php foreach (array_filter($filters, fn ($v) => $v !== '') as $k => $v): if ($k === 'sort') { continue; } ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endforeach; ?>
          <label for="sort" class="small muted">Susun</label>
          <select class="select" id="sort" name="sort" data-autosubmit>
            <?php foreach ($sorts as $key => $s): ?>
              <option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($s['label']) ?></option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn--sm" type="submit">Susun</button></noscript>
        </form>
      </div>

      <?php if ($hasFilters): ?>
        <div class="active-filters" aria-label="Penapis aktif">
          <?php if ($filters['q'] !== ''): ?><a href="<?= e(url('/products', $keep(['q' => '', 'page' => '']))) ?>">“<?= e($filters['q']) ?>” <?= icon('x', 12) ?><span class="sr-only">buang</span></a><?php endif; ?>
          <?php if ($currentCategory): ?><a href="<?= e(url('/products', $keep(['category' => '', 'page' => '']))) ?>"><?= e($currentCategory['name']) ?> <?= icon('x', 12) ?><span class="sr-only">buang</span></a><?php endif; ?>
          <?php if ($filters['min'] !== '' || $filters['max'] !== ''): ?><a href="<?= e(url('/products', $keep(['min' => '', 'max' => '', 'page' => '']))) ?>">RM<?= e($filters['min'] ?: '0') ?>–<?= e($filters['max'] ?: '∞') ?> <?= icon('x', 12) ?><span class="sr-only">buang</span></a><?php endif; ?>
          <?php if ($filters['availability'] !== ''): ?><a href="<?= e(url('/products', $keep(['availability' => '', 'page' => '']))) ?>">Ada stok <?= icon('x', 12) ?><span class="sr-only">buang</span></a><?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($products): ?>
        <div class="product-grid">
          <?php foreach ($products as $p): ?><?= view_partial('product_card', ['p' => $p]) ?><?php endforeach; ?>
        </div>
        <?= view_partial('pagination', ['pagination' => $pagination]) ?>
      <?php else: ?>
        <?= view_partial('empty', [
            'icon' => 'search',
            'title' => 'Tiada produk sepadan',
            'text' => $hasFilters ? 'Cuba kata kunci lain atau kosongkan penapis harga dan kategori.' : 'Belum ada produk yang dijual buat masa ini.',
            'actionUrl' => $hasFilters ? '/products' : null,
            'actionLabel' => 'Kosongkan penapis',
        ]) ?>
      <?php endif; ?>
    </div>
  </div>
</div>

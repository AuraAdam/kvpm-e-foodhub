<?php /** @var array $products */ ?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Senarai simpanan</span></nav>
  <div class="page-head"><div><h1>Senarai simpanan</h1><p>Produk yang anda simpan untuk dibeli kemudian.</p></div></div>
  <?php if (!$products): ?>
    <?= view_partial('empty', ['icon' => 'heart', 'title' => 'Senarai simpanan kosong', 'text' => 'Tekan "Simpan untuk kemudian" di halaman produk untuk menyimpannya di sini.', 'actionUrl' => '/products', 'actionLabel' => 'Lihat produk']) ?>
  <?php else: ?>
    <div class="product-grid product-grid--4">
      <?php foreach ($products as $p): ?>
        <div>
          <?= view_partial('product_card', ['p' => $p]) ?>
          <form method="post" action="<?= e(url('/wishlist/toggle')) ?>" class="mt-2">
            <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn--ghost btn--sm" type="submit"><?= icon('x', 14) ?> Buang</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
/**
 * @var array $product @var array $images @var array $reviews @var array $rating @var array $related
 * @var bool $inWishlist @var bool $isOwner @var int $maxQty
 */
use App\Core\Auth;

$inStock = (int) $product['is_available'] === 1 && (int) $product['stock'] > 0;
$threshold = (int) setting('low_stock_threshold', '5');
$main = $images[0]['path'] ?? null;
$canShop = !Auth::is('admin') && !$isOwner;
?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan">
    <a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?>
    <a href="<?= e(url('/products', ['category' => $product['category_slug']])) ?>"><?= e($product['category_name']) ?></a><?= icon('chevron-right', 14) ?>
    <span aria-current="page"><?= e($product['name']) ?></span>
  </nav>

  <div class="product-layout">
    <div class="gallery">
      <div class="gallery__main img-wrap"><img id="gallery-main" src="<?= e(upload_url($main)) ?>" alt="<?= e($product['name']) ?>" width="640" height="640"></div>
      <?php if (count($images) > 1): ?>
        <div class="gallery__thumbs" role="group" aria-label="Gambar produk">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-gallery-thumb data-full="<?= e(upload_url($img['path'])) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Gambar <?= $i + 1 ?>">
              <img src="<?= e(upload_url($img['path'])) ?>" alt="" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="product-info">
      <a class="small" href="<?= e(url('/shop/' . $product['shop_slug'])) ?>"><?= e($product['shop_name']) ?></a>
      <h1><?= e($product['name']) ?></h1>
      <?php if ((int) $rating['total'] > 0): ?>
        <a href="#reviews" class="cluster small" style="text-decoration:none;color:var(--ink-2)"><?= stars((float) $rating['avg_rating']) ?> <?= number_format((float) $rating['avg_rating'], 1) ?> · <?= (int) $rating['total'] ?> ulasan</a>
      <?php else: ?>
        <span class="small muted">Belum ada ulasan</span>
      <?php endif; ?>

      <div class="product-price"><?= e(money($product['price'])) ?></div>

      <?php if (!$inStock): ?>
        <span class="badge badge--danger">Stok habis buat masa ini</span>
      <?php elseif ((int) $product['stock'] <= $threshold): ?>
        <span class="badge badge--warning">Tinggal <?= (int) $product['stock'] ?> unit</span>
      <?php else: ?>
        <span class="badge badge--success">Ada stok · <?= (int) $product['stock'] ?> unit</span>
      <?php endif; ?>

      <div class="buy-box">
        <?php if ($isOwner): ?>
          <p class="mb-0 small">Ini produk kedai anda. <a href="<?= e(url('/seller/products/' . $product['id'] . '/edit')) ?>">Edit produk</a></p>
        <?php elseif (Auth::is('admin')): ?>
          <p class="mb-0 small">Akaun pentadbir tidak boleh membuat pesanan. <a href="<?= e(url('/admin/products/' . $product['id'])) ?>">Buka dalam moderasi</a></p>
        <?php elseif ($inStock): ?>
          <form method="post" action="<?= e(url('/cart/add')) ?>" class="stack-sm">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <label class="label" for="qty">Kuantiti</label>
            <div class="buy-box__row">
              <div class="qty">
                <button type="button" data-step="-1" aria-label="Kurangkan"><?= icon('minus', 16) ?></button>
                <input id="qty" type="number" name="quantity" value="1" min="1" max="<?= (int) $maxQty ?>" inputmode="numeric">
                <button type="button" data-step="1" aria-label="Tambah"><?= icon('plus', 16) ?></button>
              </div>
              <span class="hint">Maksimum <?= (int) $maxQty ?> unit</span>
            </div>
            <div class="buy-box__row">
              <button class="btn btn--primary btn--lg" type="submit"><?= icon('cart', 18) ?> Tambah ke troli</button>
              <button class="btn btn--lg" type="submit" name="buy_now" value="1">Beli sekarang</button>
            </div>
          </form>
        <?php else: ?>
          <p class="mb-0 small">Produk ini tidak boleh dipesan sekarang. Simpan ke senarai simpanan dan semak semula kemudian.</p>
        <?php endif; ?>

        <?php if ($canShop): ?>
          <form method="post" action="<?= e(url('/wishlist/toggle')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <button type="submit" class="btn btn--ghost btn--sm"><?= icon('heart', 16) ?> <?= $inWishlist ? 'Buang dari senarai simpanan' : 'Simpan untuk kemudian' ?></button>
          </form>
        <?php endif; ?>
      </div>

      <dl class="facts">
        <dt>Kategori</dt><dd><?= e($product['category_name']) ?></dd>
        <dt>Pengambilan</dt><dd><?= e($product['collection_location']) ?></dd>
        <?php if ($product['collection_hours']): ?><dt>Waktu</dt><dd><?= e($product['collection_hours']) ?></dd><?php endif; ?>
        <?php if ($product['sku']): ?><dt>SKU</dt><dd><?= e($product['sku']) ?></dd><?php endif; ?>
      </dl>

      <h2 class="mt-5" style="font-size:1.05rem">Penerangan</h2>
      <p class="description"><?= e($product['description']) ?></p>

      <div class="shop-strip">
        <?php if ($product['shop_logo']): ?><img class="shop-logo" src="<?= e(upload_url($product['shop_logo'])) ?>" alt=""><?php else: ?><span class="shop-logo"><?= e(mb_strtoupper(mb_substr($product['shop_name'], 0, 1))) ?></span><?php endif; ?>
        <div style="flex:1">
          <strong><?= e($product['shop_name']) ?></strong>
          <div class="small muted"><?= icon('pin', 13) ?> <?= e($product['collection_location']) ?></div>
          <?php if (Auth::check()): ?>
            <div class="small"><?= icon('phone', 13) ?> <?= e($product['shop_phone']) ?></div>
          <?php else: ?>
            <div class="small muted">Log masuk untuk melihat nombor telefon penjual.</div>
          <?php endif; ?>
        </div>
        <a class="btn btn--sm" href="<?= e(url('/shop/' . $product['shop_slug'])) ?>">Lawat kedai</a>
      </div>

      <?php if (Auth::check() && $canShop): ?>
        <details class="reveal mt-4">
          <summary class="small muted" style="text-decoration:underline"><?= icon('flag', 14) ?> Laporkan produk ini</summary>
          <form method="post" action="<?= e(url('/product/' . $product['slug'] . '/report')) ?>" class="form panel panel--tight">
            <?= csrf_field() ?>
            <div class="field">
              <label for="report-reason">Sebab</label>
              <select class="select" id="report-reason" name="reason" required>
                <option value="inaccurate">Maklumat tidak tepat</option>
                <option value="prohibited">Barang tidak dibenarkan di kampus</option>
                <option value="offensive">Kandungan tidak sesuai</option>
                <option value="scam">Disyaki penipuan</option>
                <option value="other">Lain-lain</option>
              </select>
            </div>
            <div class="field">
              <label for="report-details">Butiran (pilihan)</label>
              <textarea class="textarea" id="report-details" name="details" maxlength="500"></textarea>
            </div>
            <button class="btn btn--danger" type="submit">Hantar laporan</button>
          </form>
        </details>
      <?php endif; ?>
    </div>
  </div>

  <section class="section panel" id="reviews" aria-labelledby="reviews-title">
    <div class="panel__head">
      <h2 id="reviews-title">Ulasan pembeli</h2>
      <?php if ((int) $rating['total'] > 0): ?>
        <div class="rating-summary"><strong><?= number_format((float) $rating['avg_rating'], 1) ?></strong><div><?= stars((float) $rating['avg_rating'], 16) ?><div class="small muted"><?= (int) $rating['total'] ?> ulasan</div></div></div>
      <?php endif; ?>
    </div>
    <?php if ($reviews): ?>
      <?php foreach ($reviews as $r): ?>
        <article class="review">
          <div class="review__head">
            <strong><?= e($r['reviewer']) ?></strong>
            <span class="cluster small muted"><?= stars((float) $r['rating'], 13) ?> <?= e(format_date($r['created_at'], false)) ?></span>
          </div>
          <?php if ($r['comment']): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="muted mb-0">Belum ada ulasan untuk produk ini. Ulasan hanya boleh ditulis oleh pembeli selepas pesanan mereka selesai.</p>
    <?php endif; ?>
  </section>

  <?php if ($related): ?>
    <section class="section" aria-labelledby="related-title">
      <div class="section__head"><h2 id="related-title">Produk lain dalam <?= e($product['category_name']) ?></h2></div>
      <div class="product-grid product-grid--4">
        <?php foreach ($related as $p): ?><?= view_partial('product_card', ['p' => $p]) ?><?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

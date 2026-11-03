<?php
/** @var array $p product row with Catalogue::CARD_COLUMNS */
$threshold = (int) setting('low_stock_threshold', '5');
$inStock = (int) $p['is_available'] === 1 && (int) $p['stock'] > 0;
$isOwn = auth_user() && \App\Core\Auth::sellerProfile() && \App\Core\Auth::sellerProfile()['slug'] === $p['shop_slug'];
$canBuy = !\App\Core\Auth::is('admin') && !$isOwn;
?>
<article class="product-card">
  <div class="product-card__media img-wrap">
    <img src="<?= e(upload_url($p['image_path'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="400" height="300">
    <?php if (!$inStock): ?>
      <span class="product-card__flag badge badge--danger">Stok habis</span>
    <?php elseif ((int) $p['stock'] <= $threshold): ?>
      <span class="product-card__flag badge badge--warning">Tinggal <?= (int) $p['stock'] ?></span>
    <?php endif; ?>
  </div>
  <div class="product-card__body">
    <span class="product-card__shop"><?= icon('store', 13) ?> <?= e($p['shop_name']) ?></span>
    <?php if (!empty($p['campus_name'])): ?><span class="product-card__meta"><?= icon('graduation-cap', 12) ?> <?= e($p['campus_name']) ?></span><?php endif; ?>
    <a class="product-card__name" href="<?= e(url('/product/' . $p['slug'])) ?>"><?= e($p['name']) ?></a>
    <?php if ((int) ($p['review_count'] ?? 0) > 0): ?>
      <span class="product-card__meta"><?= stars((float) $p['avg_rating'], 12) ?> <?= number_format((float) $p['avg_rating'], 1) ?> (<?= (int) $p['review_count'] ?>)</span>
    <?php endif; ?>
    <div class="product-card__foot">
      <span class="product-card__price"><?= e(money($p['price'])) ?></span>
      <?php if ($inStock && $canBuy): ?>
        <form method="post" action="<?= e(url('/cart/add')) ?>" data-add-to-cart>
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="quantity" value="1">
          <button type="submit" class="add-btn" aria-label="Tambah <?= e($p['name']) ?> ke troli"><?= icon('plus', 20) ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</article>

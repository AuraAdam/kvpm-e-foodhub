<?php /** @var array $cart from CartService::contents() */ ?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Troli</span></nav>
  <div class="page-head"><div><h1>Troli</h1><p>Harga dan stok disemak semula semasa anda membuat pesanan.</p></div></div>

  <?php if (!$cart['groups']): ?>
    <?= view_partial('empty', ['icon' => 'cart', 'title' => 'Troli anda kosong', 'text' => 'Cari makanan, minuman atau alat tulis daripada gerai di kampus.', 'actionUrl' => '/products', 'actionLabel' => 'Lihat produk']) ?>
  <?php else: ?>
    <div class="split">
      <div>
        <?php if ($cart['has_issues']): ?>
          <div class="alert alert--warning mb-4" role="alert"><?= icon('alert', 18) ?><div class="alert__body">Sesetengah item perlu dikemas kini sebelum anda boleh membuat pesanan. Lihat nota berwarna di bawah.</div></div>
        <?php endif; ?>
        <?php foreach ($cart['groups'] as $group): ?>
          <section class="cart-shop" aria-label="Item dari <?= e($group['shop_name']) ?>">
            <div class="cart-shop__head">
              <a href="<?= e(url('/shop/' . $group['shop_slug'])) ?>"><?= icon('store', 18) ?> <?= e($group['shop_name']) ?></a>
              <span class="small muted"><?= icon('pin', 13) ?> Ambil di <?= e($group['collection_location']) ?></span>
            </div>
            <?php foreach ($group['items'] as $item): ?>
              <div class="cart-line<?= $item['issue'] ? ' has-issue' : '' ?>">
                <img src="<?= e(upload_url($item['image_path'])) ?>" alt="" width="72" height="72" loading="lazy">
                <div>
                  <div class="cart-line__top">
                    <a class="cart-line__name" href="<?= e(url('/product/' . $item['slug'])) ?>"><?= e($item['name']) ?></a>
                    <strong class="num"><?= e(money(sen_to_decimal($item['line_sen']))) ?></strong>
                  </div>
                  <div class="small muted"><?= e(money($item['price'])) ?> seunit</div>
                  <?php if ($item['issue']): ?><p class="field-error mt-2 mb-0"><?= icon('alert', 13) ?> <?= e($item['issue']) ?></p><?php endif; ?>
                  <div class="cart-line__controls">
                    <form method="post" action="<?= e(url('/cart/update')) ?>" class="cluster" data-autosubmit>
                      <?= csrf_field() ?>
                      <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                      <label class="sr-only" for="q-<?= (int) $item['product_id'] ?>">Kuantiti <?= e($item['name']) ?></label>
                      <div class="qty qty--sm">
                        <button type="button" data-step="-1" aria-label="Kurangkan"><?= icon('minus', 14) ?></button>
                        <input id="q-<?= (int) $item['product_id'] ?>" type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="<?= max(1, min((int) $item['stock'], (int) setting('max_quantity_per_item', '20'))) ?>" inputmode="numeric">
                        <button type="button" data-step="1" aria-label="Tambah"><?= icon('plus', 14) ?></button>
                      </div>
                      <noscript><button class="btn btn--sm" type="submit">Kemas kini</button></noscript>
                    </form>
                    <form method="post" action="<?= e(url('/cart/remove')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                      <button class="btn btn--ghost btn--sm" type="submit"><?= icon('trash', 15) ?> Buang</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </section>
        <?php endforeach; ?>
      </div>

      <aside class="panel sticky" aria-label="Ringkasan troli">
        <h2>Ringkasan</h2>
        <div class="summary">
          <?php foreach ($cart['groups'] as $group): ?>
            <div class="summary__row"><span><?= e($group['shop_name']) ?></span><span class="num"><?= e(money(sen_to_decimal($group['subtotal_sen']))) ?></span></div>
          <?php endforeach; ?>
          <div class="summary__row summary__row--total"><span>Jumlah (<?= (int) $cart['item_count'] ?> item)</span><span class="num"><?= e(money(sen_to_decimal($cart['total_sen']))) ?></span></div>
          <?php if (count($cart['groups']) > 1): ?>
            <p class="hint mb-0">Troli ini mengandungi item dari <?= count($cart['groups']) ?> kedai. Setiap kedai akan menerima dan menguruskan bahagian pesanannya sendiri.</p>
          <?php endif; ?>
          <?php if ($cart['has_issues']): ?>
            <button class="btn btn--primary btn--lg btn--block" type="button" disabled aria-disabled="true">Teruskan ke pesanan</button>
            <p class="hint mb-0">Kemas kini item yang bertanda dahulu.</p>
          <?php else: ?>
            <a class="btn btn--primary btn--lg btn--block" href="<?= e(url('/checkout')) ?>">Teruskan ke pesanan</a>
          <?php endif; ?>
          <a class="btn btn--ghost btn--block" href="<?= e(url('/products')) ?>">Tambah lagi produk</a>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</div>

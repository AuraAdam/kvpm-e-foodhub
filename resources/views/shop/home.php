<?php
/**
 * @var array $categories @var array $latest @var array $popular @var array $shops @var array $announcements
 */
use App\Core\Auth;

$counterItems = array_slice($latest, 0, 4);
?>
<section class="hero">
  <div class="container hero__grid">
    <div>
      <h1>Pesan dari gerai kampus, ambil tanpa beratur.</h1>
      <p class="hero__lede">Makanan, minuman, snek dan alat tulis daripada penjual kolej vokasional di Malaysia — semua dalam satu tempat, dengan status pesanan yang jelas.</p>
      <form class="hero-search" action="<?= e(url('/products')) ?>" method="get" role="search">
        <label class="sr-only" for="hero-q">Cari produk atau kedai</label>
        <div class="search-field"><?= icon('search', 20) ?><input id="hero-q" type="search" name="q" placeholder="Nasi lemak, teh ais, pen…" maxlength="80"></div>
        <button class="btn btn--primary" type="submit">Cari</button>
      </form>
      <?php if ($categories): ?>
        <div class="hero__quick">
          <span>Terus ke:</span>
          <?php foreach (array_slice($categories, 0, 4) as $c): ?>
            <a href="<?= e(url('/products', ['category' => $c['slug']])) ?>"><?= e($c['name']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($announcements as $a): ?>
        <div class="announcement" role="note"><?= icon('megaphone', 18) ?><div><strong><?= e($a['title']) ?></strong><br><?= e($a['body']) ?></div></div>
      <?php endforeach; ?>
    </div>

    <aside class="counter" aria-labelledby="counter-title">
      <div class="counter__title"><span id="counter-title" style="font-size:1rem;font-weight:700;opacity:1">Baru di kaunter</span><a href="<?= e(url('/products', ['sort' => 'newest'])) ?>" style="color:#fff;font-size:.875rem">Lihat semua</a></div>
      <?php if ($counterItems): ?>
        <div class="counter__list">
          <?php foreach ($counterItems as $p): ?>
            <a class="counter-item" href="<?= e(url('/product/' . $p['slug'])) ?>">
              <img src="<?= e(upload_url($p['image_path'])) ?>" alt="" width="56" height="56" loading="lazy">
              <span><strong><?= e($p['name']) ?></strong><small><?= e($p['shop_name']) ?></small></span>
              <span class="price"><?= e(money($p['price'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="counter__empty">Belum ada produk dijual. Penjual pertama boleh <a href="<?= e(url('/seller/apply')) ?>" style="color:#fff">membuka kedai</a>.</div>
      <?php endif; ?>
    </aside>
  </div>
</section>

<div class="container">
  <?php if ($categories): ?>
    <section class="section" aria-labelledby="cat-title" style="margin-top:var(--s5)">
      <div class="section__head"><h2 id="cat-title">Kategori</h2><a href="<?= e(url('/products')) ?>">Semua produk</a></div>
      <div class="category-grid">
        <?php foreach ($categories as $c): ?>
          <a class="category-tile" href="<?= e(url('/products', ['category' => $c['slug']])) ?>">
            <span class="category-tile__icon">
              <?php if ($c['image_path']): ?><img src="<?= e(upload_url($c['image_path'])) ?>" alt=""><?php else: ?><?= icon('tag', 20) ?><?php endif; ?>
            </span>
            <strong><?= e($c['name']) ?></strong>
            <small><?= (int) $c['product_count'] ?> produk</small>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($popular): ?>
    <section class="section" aria-labelledby="popular-title">
      <div class="section__head"><h2 id="popular-title">Paling laris</h2><a href="<?= e(url('/products', ['sort' => 'popular'])) ?>">Lihat semua</a></div>
      <p class="muted small" style="margin-top:-8px">Berdasarkan unit terjual dalam pesanan yang telah selesai.</p>
      <div class="product-grid product-grid--4">
        <?php foreach ($popular as $p): ?><?= view_partial('product_card', ['p' => $p]) ?><?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="section" aria-labelledby="latest-title">
    <div class="section__head"><h2 id="latest-title">Baru ditambah</h2><a href="<?= e(url('/products', ['sort' => 'newest'])) ?>">Lihat semua</a></div>
    <?php if ($latest): ?>
      <div class="product-grid product-grid--4">
        <?php foreach ($latest as $p): ?><?= view_partial('product_card', ['p' => $p]) ?><?php endforeach; ?>
      </div>
    <?php else: ?>
      <?= view_partial('empty', ['icon' => 'package', 'title' => 'Belum ada produk', 'text' => 'Produk akan dipaparkan di sini selepas penjual menambahnya dan pentadbir meluluskannya.']) ?>
    <?php endif; ?>
  </section>

  <?php if ($shops): ?>
    <section class="section" aria-labelledby="shops-title">
      <div class="section__head"><h2 id="shops-title">Kedai di kampus</h2><a href="<?= e(url('/shops')) ?>">Semua kedai</a></div>
      <div class="shop-grid">
        <?php foreach ($shops as $s): ?>
          <a class="shop-card" href="<?= e(url('/shop/' . $s['slug'])) ?>">
            <div class="cluster">
              <?php if ($s['logo_path']): ?><img class="shop-logo" src="<?= e(upload_url($s['logo_path'])) ?>" alt=""><?php else: ?><span class="shop-logo"><?= e(mb_strtoupper(mb_substr($s['shop_name'], 0, 1))) ?></span><?php endif; ?>
              <div><strong><?= e($s['shop_name']) ?></strong><br><small class="muted"><?= (int) $s['product_count'] ?> produk</small></div>
            </div>
            <p><?= e($s['description']) ?></p>
            <small class="muted"><?= icon('pin', 13) ?> <?= e($s['collection_location']) ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="section" aria-labelledby="how-title">
    <div class="section__head"><h2 id="how-title">Cara ia berfungsi</h2><a href="<?= e(url('/info/help')) ?>">Panduan penuh</a></div>
    <div class="steps">
      <div class="step"><h3>Pilih dan masukkan ke troli</h3><p>Cari produk dari pelbagai gerai. Satu troli boleh mengandungi barang dari beberapa kedai.</p></div>
      <div class="step"><h3>Buat pesanan</h3><p>Pilih bayar tunai semasa ambil, atau bayaran dalam talian (simulasi). Setiap kedai menerima bahagian pesanan masing-masing.</p></div>
      <div class="step"><h3>Ambil apabila sedia</h3><p>Anda akan menerima notifikasi apabila pesanan disahkan dan sedia diambil. Tunjukkan nombor pesanan di gerai.</p></div>
    </div>
  </section>

  <?php if (!Auth::check() || Auth::is('customer')): ?>
    <section class="section">
      <div class="seller-cta">
        <div>
          <h2>Ada sesuatu untuk dijual?</h2>
          <p>Pelajar, warga kolej dan pengusaha gerai kolej vokasional boleh memohon untuk membuka kedai. Urus produk, stok dan pesanan dari satu dashboard — tidak perlu lagi mengumpul pesanan melalui mesej.</p>
        </div>
        <a class="btn btn--primary btn--lg" href="<?= e(url('/seller/apply')) ?>">Mohon buka kedai</a>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php
/**
 * Customer-facing layout.
 * @var string $content
 * @var string $title
 */
use App\Core\Auth;
use App\Services\CartService;
use App\Services\Notifier;

$user = Auth::user();
$role = Auth::role();
$cartCount = ($user && $role !== 'admin') ? CartService::count((int) $user['id']) : 0;
$unread = $user ? Notifier::unreadCount((int) $user['id']) : 0;
$initial = $user ? mb_strtoupper(mb_substr($user['name'], 0, 1)) : '';
?>
<!doctype html>
<html lang="ms">
<head>
<?php require BASE_PATH . '/resources/views/layouts/head.php'; ?>
</head>
<body data-cart-url="<?= e(url('/cart')) ?>">
<a class="skip-link" href="#main">Langkau ke kandungan</a>

<header class="site-header">
  <div class="container site-header__bar">
    <button type="button" class="btn btn--ghost nav-toggle icon-link" data-drawer-open aria-controls="drawer" aria-label="Buka menu"><?= icon('menu', 22) ?></button>
    <?= view_partial('brand') ?>

    <form class="header-search" action="<?= e(url('/products')) ?>" method="get" role="search">
      <label class="sr-only" for="header-q">Cari produk</label>
      <div class="search-field">
        <?= icon('search', 18) ?>
        <input id="header-q" type="search" name="q" value="<?= e(current_path() === '/products' ? query('q') : '') ?>" placeholder="Cari nasi, air, alat tulis…" maxlength="80" autocomplete="off">
      </div>
    </form>

    <nav class="main-nav" aria-label="Navigasi utama">
      <a href="<?= e(url('/')) ?>" class="<?= nav_active('/', true) ?>">Laman Utama</a>
      <a href="<?= e(url('/products')) ?>" class="<?= nav_active('/products') ?>">Produk</a>
      <a href="<?= e(url('/shops')) ?>" class="<?= nav_active('/shops') ?>">Kedai</a>
    </nav>

    <div class="header-actions">
      <?php if ($user): ?>
        <a class="icon-link" href="<?= e(url('/notifications')) ?>" aria-label="Notifikasi<?= $unread ? " ($unread belum dibaca)" : '' ?>">
          <?= icon('bell', 21) ?>
          <span class="count-badge count-badge--alert"<?= $unread ? '' : ' hidden' ?>><?= $unread > 99 ? '99+' : $unread ?></span>
        </a>
      <?php endif; ?>
      <?php if ($role !== 'admin'): ?>
        <a class="icon-link" href="<?= e(url('/cart')) ?>" aria-label="Troli (<?= $cartCount ?> item)">
          <?= icon('cart', 21) ?>
          <span class="count-badge" id="cart-count"<?= $cartCount ? '' : ' hidden' ?>><?= $cartCount ?></span>
        </a>
      <?php endif; ?>

      <?php if ($user): ?>
        <details class="menu">
          <summary aria-label="Menu akaun"><span class="avatar" aria-hidden="true"><?= e($initial) ?></span><span class="menu-name"><?= e(explode(' ', $user['name'])[0]) ?></span><?= icon('chevron-down', 16) ?></summary>
          <div class="menu__panel">
            <div class="menu__head"><strong><?= e($user['name']) ?></strong><span><?= e(role_label($role)) ?></span></div>
            <?php if ($role === 'admin'): ?>
              <a href="<?= e(url('/admin')) ?>"><?= icon('grid', 18) ?> Dashboard Pentadbir</a>
            <?php else: ?>
              <?php if ($role === 'seller'): ?><a href="<?= e(url('/seller')) ?>"><?= icon('store', 18) ?> Dashboard Penjual</a><?php endif; ?>
              <a href="<?= e(url('/orders')) ?>"><?= icon('receipt', 18) ?> Pesanan Saya</a>
              <a href="<?= e(url('/wishlist')) ?>"><?= icon('heart', 18) ?> Senarai simpanan</a>
              <a href="<?= e(url('/account/reviews')) ?>"><?= icon('star', 18) ?> Ulasan saya</a>
            <?php endif; ?>
            <a href="<?= e(url('/account')) ?>"><?= icon('user', 18) ?> Akaun saya</a>
            <form method="post" action="<?= e(url('/logout')) ?>">
              <?= csrf_field() ?>
              <button type="submit"><?= icon('logout', 18) ?> Log keluar</button>
            </form>
          </div>
        </details>
      <?php else: ?>
        <a class="login-link" href="<?= e(url('/login')) ?>">Log masuk</a>
        <a class="btn btn--primary btn--sm" href="<?= e(url('/register')) ?>">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="container mobile-search">
    <form action="<?= e(url('/products')) ?>" method="get" role="search">
      <label class="sr-only" for="mobile-q">Cari produk</label>
      <div class="search-field"><?= icon('search', 18) ?><input id="mobile-q" type="search" name="q" value="<?= e(current_path() === '/products' ? query('q') : '') ?>" placeholder="Cari produk atau kedai" maxlength="80"></div>
    </form>
  </div>
</header>

<div class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer__backdrop" data-drawer-close></div>
  <nav class="drawer__panel" aria-label="Menu mudah alih">
    <div class="drawer__head"><?= view_partial('brand') ?><button type="button" class="btn btn--ghost icon-link" data-drawer-close aria-label="Tutup menu"><?= icon('x', 22) ?></button></div>
    <a href="<?= e(url('/')) ?>" class="<?= nav_active('/', true) ?>"><?= icon('home') ?> Laman Utama</a>
    <a href="<?= e(url('/products')) ?>" class="<?= nav_active('/products') ?>"><?= icon('grid') ?> Produk</a>
    <a href="<?= e(url('/shops')) ?>" class="<?= nav_active('/shops') ?>"><?= icon('store') ?> Kedai</a>
    <?php if ($user && $role !== 'admin'): ?>
      <a href="<?= e(url('/orders')) ?>" class="<?= nav_active('/orders') ?>"><?= icon('receipt') ?> Pesanan Saya</a>
      <a href="<?= e(url('/wishlist')) ?>"><?= icon('heart') ?> Senarai simpanan</a>
    <?php endif; ?>
    <?php if (!$user || $role === 'customer'): ?>
      <a href="<?= e(url('/seller/apply')) ?>"><?= icon('store') ?> Buka kedai</a>
    <?php endif; ?>
    <a href="<?= e(url('/info/help')) ?>"><?= icon('info') ?> Bantuan</a>
  </nav>
</div>

<main id="main">
  <div class="container"><?= view_partial('flash') ?></div>
  <?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <?= view_partial('brand') ?>
        <p class="mt-4 small">Pasaran dalam talian yang menghubungkan pembeli dengan penjual dari kolej vokasional di Malaysia. Pesan dahulu, ambil di gerai tanpa beratur.</p>
      </div>
      <div>
        <h3>Beli-belah</h3>
        <ul>
          <li><a href="<?= e(url('/products')) ?>">Semua produk</a></li>
          <li><a href="<?= e(url('/shops')) ?>">Senarai kedai</a></li>
          <li><a href="<?= e(url('/orders')) ?>">Pesanan Saya</a></li>
        </ul>
      </div>
      <div>
        <h3>Penjual</h3>
        <ul>
          <li><a href="<?= e(url('/seller/apply')) ?>">Buka kedai</a></li>
          <li><a href="<?= e(url('/seller')) ?>">Dashboard Penjual</a></li>
          <li><a href="<?= e(url('/info/help')) ?>">Cara ia berfungsi</a></li>
        </ul>
      </div>
      <div>
        <h3>Hubungi</h3>
        <ul>
          <li><?= e(setting('contact_email')) ?></li>
          <li><?= e(setting('contact_phone')) ?></li>
          <li><a href="<?= e(url('/info/terms')) ?>">Terma penggunaan</a> · <a href="<?= e(url('/info/privacy')) ?>">Privasi</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-note">
      <span>© <?= date('Y') ?> E-FoodHub. Projek akademik Diploma Teknologi Maklumat.</span>
      <span>Bayaran dalam talian di laman ini ialah simulasi untuk demonstrasi.</span>
    </div>
  </div>
</footer>

<?= view_partial('confirm_dialog') ?>
</body>
</html>

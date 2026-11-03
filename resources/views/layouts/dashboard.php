<?php
/**
 * Seller and administrator layout with a sidebar.
 * @var string $content
 * @var string $title
 */
use App\Core\Auth;
use App\Core\Database;
use App\Services\Notifier;

$user = Auth::user();
$role = Auth::role();
$unread = Notifier::unreadCount((int) $user['id']);
$shop = $role === 'seller' ? Auth::sellerProfile() : null;

if ($role === 'admin') {
    $pendingSellers = (int) Database::value("SELECT COUNT(*) FROM seller_profiles WHERE status = 'pending'");
    $pendingProducts = (int) Database::value("SELECT COUNT(*) FROM products WHERE approval_status = 'pending' AND archived_at IS NULL");
    $openReports = (int) Database::value("SELECT COUNT(*) FROM product_reports WHERE status = 'open'");
    $nav = [
        'Ringkasan' => [
            ['/admin', 'grid', 'Dashboard Pentadbir', 0, true],
        ],
        'Pengurusan' => [
            ['/admin/users', 'users', 'Pengguna', 0, false],
            ['/admin/sellers', 'store', 'Permohonan & Kedai', $pendingSellers, false],
            ['/admin/products', 'package', 'Moderasi Produk', $pendingProducts, false],
            ['/admin/categories', 'tag', 'Kategori', 0, false],
            ['/admin/orders', 'receipt', 'Semua Pesanan', 0, false],
        ],
        'Kandungan' => [
            ['/admin/reviews', 'star', 'Ulasan', 0, false],
            ['/admin/reports', 'flag', 'Laporan produk', $openReports, false],
            ['/admin/announcements', 'megaphone', 'Pengumuman', 0, false],
            ['/admin/settings', 'settings', 'Tetapan sistem', 0, false],
            ['/admin/audit', 'history', 'Log audit', 0, false],
        ],
    ];
} elseif ($role === 'seller') {
    $pendingOrders = (int) Database::value("SELECT COUNT(*) FROM seller_orders WHERE seller_id = ? AND status = 'pending'", [(int) $shop['id']]);
    $nav = [
        'Kedai saya' => [
            ['/seller', 'grid', 'Dashboard Penjual', 0, true],
            ['/seller/orders', 'receipt', 'Pengurusan Pesanan', $pendingOrders, false],
            ['/seller/products', 'package', 'Pengurusan Produk', 0, false],
            ['/seller/reports', 'chart', 'Laporan jualan', 0, false],
            ['/seller/shop', 'store', 'Profil kedai', 0, false],
        ],
        'Sebagai pembeli' => [
            ['/products', 'search', 'Beli-belah', 0, false],
            ['/orders', 'cart', 'Pesanan Saya', 0, false],
        ],
    ];
} else {
    $nav = ['Akaun' => [['/', 'home', 'Laman Utama', 0, true]]];
}
$nav['Akaun'] = array_merge($nav['Akaun'] ?? [], [
    ['/notifications', 'bell', 'Notifikasi', $unread, false],
    ['/account', 'user', 'Akaun saya', 0, false],
]);

$renderNav = function () use ($nav): string {
    $html = '';
    foreach ($nav as $group => $links) {
        $html .= '<p class="dash-nav__label">' . e($group) . '</p>';
        foreach ($links as [$href, $ic, $label, $count, $exact]) {
            $html .= '<a href="' . e(url($href)) . '" class="' . trim(nav_active($href, $exact)) . '">' . icon($ic, 18) . ' ' . e($label)
                . ($count > 0 ? '<span class="nav-count">' . (int) $count . '</span>' : '') . '</a>';
        }
    }
    return $html;
};
?>
<!doctype html>
<html lang="ms">
<head>
<?php require BASE_PATH . '/resources/views/layouts/head.php'; ?>
</head>
<body data-cart-url="<?= e(url('/cart')) ?>">
<a class="skip-link" href="#main">Langkau ke kandungan</a>
<div class="dash">
  <aside class="dash-side" aria-label="Navigasi dashboard">
    <?= view_partial('brand') ?>
    <div class="dash-side__role">
      <strong><?= e($shop['shop_name'] ?? $user['name']) ?></strong>
      <span><?= $role === 'seller' ? 'Penjual · ' . e($user['name']) : e(role_label($role)) ?></span>
    </div>
    <nav class="dash-nav"><?= $renderNav() ?></nav>
    <form method="post" action="<?= e(url('/logout')) ?>" class="mt-4">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--ghost btn--block"><?= icon('logout', 18) ?> Log keluar</button>
    </form>
  </aside>

  <div class="dash-main">
    <header class="dash-top">
      <button type="button" class="btn btn--ghost nav-toggle icon-link" data-drawer-open aria-controls="drawer" aria-label="Buka menu"><?= icon('menu', 22) ?></button>
      <?= view_partial('brand') ?>
      <div class="header-actions">
        <?php if ($role === 'seller' && $shop): ?>
          <a href="<?= e(url('/shop/' . $shop['slug'])) ?>" class="hide-sm"><?= icon('external', 16) ?> Lihat kedai</a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
          <a href="<?= e(url('/')) ?>" class="hide-sm"><?= icon('external', 16) ?> Lihat laman</a>
        <?php endif; ?>
        <a class="icon-link" href="<?= e(url('/notifications')) ?>" aria-label="Notifikasi<?= $unread ? " ($unread belum dibaca)" : '' ?>">
          <?= icon('bell', 21) ?><span class="count-badge count-badge--alert"<?= $unread ? '' : ' hidden' ?>><?= $unread > 99 ? '99+' : $unread ?></span>
        </a>
      </div>
    </header>

    <div class="drawer" id="drawer" aria-hidden="true">
      <div class="drawer__backdrop" data-drawer-close></div>
      <div class="drawer__panel">
        <div class="drawer__head"><?= view_partial('brand') ?><button type="button" class="btn btn--ghost icon-link" data-drawer-close aria-label="Tutup menu"><?= icon('x', 22) ?></button></div>
        <nav class="dash-nav" aria-label="Navigasi dashboard"><?= $renderNav() ?></nav>
        <form method="post" action="<?= e(url('/logout')) ?>" class="mt-4">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--block"><?= icon('logout', 18) ?> Log keluar</button>
        </form>
      </div>
    </div>

    <main id="main" class="dash-content">
      <?= view_partial('flash') ?>
      <div class="mt-4"><?= $content ?></div>
    </main>
  </div>
</div>
<?= view_partial('confirm_dialog') ?>
</body>
</html>

<?php
/**
 * @var array $shop @var array $counts @var string $sales30 @var string $awaitingCash @var int $activeProducts
 * @var int $pendingProducts @var array $lowStock @var int $threshold @var array $recent @var array $daily
 */
?>
<div class="page-head">
  <div><h1>Dashboard Penjual</h1><p><?= e($shop['shop_name']) ?> · <?= e(format_date(now(), false)) ?></p></div>
  <div class="btn-row">
    <a class="btn" href="<?= e(url('/seller/orders', ['status' => 'pending'])) ?>"><?= icon('receipt', 16) ?> Pesanan menunggu</a>
    <a class="btn btn--primary" href="<?= e(url('/seller/products/create')) ?>"><?= icon('plus', 16) ?> Tambah produk</a>
  </div>
</div>

<div class="stats">
  <a class="stat<?= (int) $counts['pending'] > 0 ? ' stat--attention' : '' ?>" href="<?= e(url('/seller/orders', ['status' => 'pending'])) ?>">
    <span class="stat__label"><?= icon('clock', 14) ?> Perlu disahkan</span>
    <span class="stat__value"><?= (int) $counts['pending'] ?></span>
    <span class="stat__note">pesanan menunggu</span>
  </a>
  <a class="stat" href="<?= e(url('/seller/orders', ['status' => 'preparing'])) ?>">
    <span class="stat__label"><?= icon('package', 14) ?> Dalam proses</span>
    <span class="stat__value"><?= (int) $counts['in_progress'] + (int) $counts['ready'] ?></span>
    <span class="stat__note"><?= (int) $counts['ready'] ?> sedia diambil</span>
  </a>
  <div class="stat">
    <span class="stat__label"><?= icon('wallet', 14) ?> Jualan selesai, 30 hari</span>
    <span class="stat__value"><?= e(money($sales30)) ?></span>
    <span class="stat__note"><?= (int) $counts['completed'] ?> pesanan selesai sepanjang masa</span>
  </div>
  <a class="stat" href="<?= e(url('/seller/products')) ?>">
    <span class="stat__label"><?= icon('grid', 14) ?> Produk dijual</span>
    <span class="stat__value"><?= $activeProducts ?></span>
    <span class="stat__note"><?= $pendingProducts ?> menunggu kelulusan</span>
  </a>
</div>
<?php if ((float) $awaitingCash > 0): ?>
  <p class="small muted mt-3"><?= icon('info', 14) ?> <?= e(money($awaitingCash)) ?> lagi daripada pesanan aktif belum dibayar (tunai semasa ambil). Jumlah ini belum dikira sebagai jualan.</p>
<?php endif; ?>

<div class="dash-grid">
  <section class="panel">
    <div class="panel__head"><h2>Jualan selesai, 14 hari</h2><a class="small" href="<?= e(url('/seller/reports')) ?>">Laporan penuh</a></div>
    <?= view_partial('bar_chart', ['series' => $daily]) ?>
  </section>
  <section class="panel">
    <div class="panel__head"><h2>Stok rendah</h2><span class="small muted">≤ <?= $threshold ?> unit</span></div>
    <?php if ($lowStock): ?>
      <ul class="list-plain">
        <?php foreach ($lowStock as $p): ?>
          <li class="list-row">
            <a href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>"><?= e($p['name']) ?></a>
            <span class="stock-note <?= (int) $p['stock'] === 0 ? 'stock-note--out' : 'stock-note--low' ?>"><?= (int) $p['stock'] === 0 ? 'Habis' : (int) $p['stock'] . ' unit' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted mb-0">Semua produk mempunyai stok mencukupi.</p>
    <?php endif; ?>
  </section>
</div>

<section class="panel mt-4">
  <div class="panel__head"><h2>Pesanan terkini</h2><a class="small" href="<?= e(url('/seller/orders')) ?>">Semua pesanan</a></div>
  <?php if ($recent): ?>
    <div class="table-wrap">
      <table class="table table--stack">
        <thead><tr><th>Rujukan</th><th>Pelanggan</th><th>Item</th><th>Bayaran</th><th>Status</th><th class="num">Jumlah</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $o): ?>
            <tr>
              <td class="cell-main"><a class="table-title" href="<?= e(url('/seller/orders/' . $o['id'])) ?>"><?= e($o['reference']) ?></a><div class="table-sub"><?= e(time_ago($o['created_at'])) ?></div></td>
              <td data-label="Pelanggan"><?= e($o['contact_name']) ?></td>
              <td data-label="Item"><?= (int) $o['units'] ?> unit</td>
              <td data-label="Bayaran"><?= badge($o['payment_status']) ?></td>
              <td data-label="Status"><?= badge($o['status']) ?></td>
              <td data-label="Jumlah" class="num strong"><?= e(money($o['subtotal'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <?= view_partial('empty', ['icon' => 'receipt', 'title' => 'Belum ada pesanan', 'text' => 'Pesanan pertama anda akan muncul di sini. Pastikan produk anda ada stok dan ditandakan sebagai dijual.', 'compact' => true]) ?>
  <?php endif; ?>
</section>

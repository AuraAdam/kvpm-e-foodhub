<?php /** @var int $range @var array $summary @var array $best @var array $lowStock @var array $daily */ ?>
<div class="page-head">
  <div><h1>Laporan jualan</h1><p>Hanya pesanan berstatus <strong>Selesai</strong> dikira sebagai jualan.</p></div>
  <div class="btn-row">
    <nav class="tabs mb-0" aria-label="Tempoh" style="border:0">
      <?php foreach ([7, 30, 90] as $r): ?>
        <a href="<?= e(url('/seller/reports', ['range' => $r])) ?>" class="<?= $range === $r ? 'is-active' : '' ?>"><?= $r ?> hari</a>
      <?php endforeach; ?>
    </nav>
    <a class="btn btn--sm" href="<?= e(url('/seller/reports/export', ['range' => $range])) ?>"><?= icon('upload', 15) ?> Muat turun CSV</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><span class="stat__label">Jualan selesai</span><span class="stat__value"><?= e(money($summary['sales'])) ?></span><span class="stat__note"><?= $range ?> hari lepas</span></div>
  <div class="stat"><span class="stat__label">Pesanan diterima</span><span class="stat__value"><?= (int) $summary['orders'] ?></span><span class="stat__note"><?= (int) $summary['open'] ?> masih dalam proses</span></div>
  <div class="stat"><span class="stat__label">Selesai</span><span class="stat__value"><?= (int) $summary['completed'] ?></span><span class="stat__note"><?= (int) $summary['orders'] > 0 ? round($summary['completed'] / $summary['orders'] * 100) . '% daripada pesanan' : '—' ?></span></div>
  <div class="stat"><span class="stat__label">Ditolak / dibatalkan</span><span class="stat__value"><?= (int) $summary['closed'] ?></span><span class="stat__note">tidak dikira sebagai jualan</span></div>
</div>

<section class="panel mt-4">
  <div class="panel__head"><h2>Jualan selesai harian</h2></div>
  <?= view_partial('bar_chart', ['series' => $daily]) ?>
</section>

<div class="dash-grid">
  <section class="panel">
    <div class="panel__head"><h2>Produk terlaris</h2></div>
    <?php if ($best): ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Produk</th><th class="num">Unit</th><th class="num">Hasil</th></tr></thead>
          <tbody>
            <?php foreach ($best as $b): ?>
              <tr><td><?= e($b['product_name']) ?></td><td class="num"><?= (int) $b['units'] ?></td><td class="num"><?= e(money($b['revenue'])) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted mb-0">Belum ada produk terjual dalam pesanan selesai bagi tempoh ini.</p>
    <?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel__head"><h2>Stok rendah</h2></div>
    <?php if ($lowStock): ?>
      <ul class="list-plain">
        <?php foreach ($lowStock as $p): ?>
          <li class="list-row"><a href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>"><?= e($p['name']) ?></a><span class="stock-note <?= (int) $p['stock'] === 0 ? 'stock-note--out' : 'stock-note--low' ?>"><?= (int) $p['stock'] ?> unit</span></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted mb-0">Tiada produk berstok rendah.</p>
    <?php endif; ?>
  </section>
</div>

<?php
/**
 * @var int $range @var array $users @var array $orders @var int $pendingSellers @var int $pendingProducts
 * @var int $activeProducts @var int $openReports @var int $outOfStock @var array $recentAudit @var array $topShops
 */
use App\Services\AuditLog;
?>
<div class="page-head">
  <div><h1>Dashboard Pentadbir</h1><p>Semua angka dikira terus daripada rekod pangkalan data.</p></div>
  <nav class="tabs mb-0" aria-label="Tempoh jualan" style="border:0">
    <?php foreach ([7, 30, 90] as $r): ?><a href="<?= e(url('/admin', ['range' => $r])) ?>" class="<?= $range === $r ? 'is-active' : '' ?>"><?= $r ?> hari</a><?php endforeach; ?>
  </nav>
</div>

<?php if ($pendingSellers || $pendingProducts || $openReports): ?>
  <div class="stats mb-4" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
    <?php if ($pendingSellers): ?><a class="stat stat--attention" href="<?= e(url('/admin/sellers', ['status' => 'pending'])) ?>"><span class="stat__label"><?= icon('store', 14) ?> Permohonan kedai</span><span class="stat__value"><?= $pendingSellers ?></span><span class="stat__note">perlu disemak</span></a><?php endif; ?>
    <?php if ($pendingProducts): ?><a class="stat stat--attention" href="<?= e(url('/admin/products', ['status' => 'pending'])) ?>"><span class="stat__label"><?= icon('package', 14) ?> Produk menunggu</span><span class="stat__value"><?= $pendingProducts ?></span><span class="stat__note">perlu kelulusan</span></a><?php endif; ?>
    <?php if ($openReports): ?><a class="stat stat--attention" href="<?= e(url('/admin/reports')) ?>"><span class="stat__label"><?= icon('flag', 14) ?> Laporan produk</span><span class="stat__value"><?= $openReports ?></span><span class="stat__note">belum diselesaikan</span></a><?php endif; ?>
  </div>
<?php endif; ?>

<div class="stats">
  <a class="stat" href="<?= e(url('/admin/users', ['role' => 'customer'])) ?>"><span class="stat__label"><?= icon('users', 14) ?> Pelanggan</span><span class="stat__value"><?= (int) $users['customers'] ?></span><span class="stat__note"><?= (int) $users['suspended'] ?> akaun digantung (semua peranan)</span></a>
  <a class="stat" href="<?= e(url('/admin/sellers', ['status' => 'approved'])) ?>"><span class="stat__label"><?= icon('store', 14) ?> Penjual</span><span class="stat__value"><?= (int) $users['sellers'] ?></span><span class="stat__note"><?= $activeProducts ?> produk aktif</span></a>
  <a class="stat" href="<?= e(url('/admin/orders')) ?>"><span class="stat__label"><?= icon('receipt', 14) ?> Pesanan kedai</span><span class="stat__value"><?= (int) $orders['total'] ?></span><span class="stat__note"><?= (int) $orders['completed'] ?> selesai · <?= (int) $orders['open'] ?> aktif · <?= (int) $orders['closed'] ?> batal/tolak</span></a>
  <div class="stat"><span class="stat__label"><?= icon('wallet', 14) ?> Jualan selesai, <?= $range ?> hari</span><span class="stat__value"><?= e(money($orders['sales'])) ?></span><span class="stat__note">Pesanan dibatalkan tidak dikira</span></div>
</div>
<?php if ($outOfStock): ?><p class="small muted mt-3"><?= icon('alert', 14) ?> <a href="<?= e(url('/admin/products', ['status' => 'out_of_stock'])) ?>"><?= $outOfStock ?> produk diluluskan kehabisan stok</a>.</p><?php endif; ?>

<div class="dash-grid">
  <section class="panel">
    <div class="panel__head"><h2>Aktiviti pentadbir terkini</h2><a class="small" href="<?= e(url('/admin/audit')) ?>">Log audit penuh</a></div>
    <?php if ($recentAudit): ?>
      <ul class="list-plain">
        <?php foreach ($recentAudit as $a): ?>
          <li class="list-row"><span><?= e(AuditLog::describe($a['action'])) ?><br><small class="muted"><?= e($a['actor_name'] ?? 'Sistem') ?> · <?= e(time_ago($a['created_at'])) ?></small></span><span class="tag"><?= e($a['entity_type']) ?> #<?= (int) $a['entity_id'] ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted mb-0">Belum ada tindakan pentadbir direkodkan.</p>
    <?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel__head"><h2>Kedai terlaris, <?= $range ?> hari</h2></div>
    <?php if ($topShops): ?>
      <ul class="list-plain">
        <?php foreach ($topShops as $s): ?>
          <li class="list-row"><span><?= e($s['shop_name']) ?><br><small class="muted"><?= (int) $s['orders'] ?> pesanan selesai</small></span><strong class="num"><?= e(money($s['sales'])) ?></strong></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted mb-0">Tiada jualan selesai dalam tempoh ini.</p>
    <?php endif; ?>
  </section>
</div>

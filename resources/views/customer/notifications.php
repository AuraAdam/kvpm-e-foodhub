<?php
/** @var array $notifications @var array $pagination */
$inDashboard = auth_user()['role'] !== 'customer';
$icons = [
    'order_placed' => 'receipt', 'order_confirmed' => 'check', 'order_preparing' => 'clock', 'order_ready' => 'package',
    'order_completed' => 'check', 'order_rejected' => 'x', 'order_cancelled' => 'x', 'payment_received' => 'wallet',
    'low_stock' => 'alert', 'seller_application' => 'store', 'seller_approved' => 'store', 'seller_rejected' => 'store',
    'seller_suspended' => 'lock', 'product_pending' => 'package', 'product_approved' => 'check', 'product_rejected' => 'x',
    'product_suspended' => 'lock', 'product_report' => 'flag', 'role_changed' => 'shield',
];
?>
<div class="<?= $inDashboard ? '' : 'container page' ?>" style="max-width:820px">
  <div class="page-head">
    <div><h1>Notifikasi</h1><p>Kemas kini tentang pesanan dan akaun anda.</p></div>
    <?php if ($notifications): ?>
      <form method="post" action="<?= e(url('/notifications/read')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="all">
        <button class="btn btn--sm" type="submit"><?= icon('check', 15) ?> Tandakan semua dibaca</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$notifications): ?>
    <?= view_partial('empty', ['icon' => 'bell', 'title' => 'Tiada notifikasi', 'text' => 'Anda akan dimaklumkan di sini apabila status pesanan berubah.']) ?>
  <?php else: ?>
    <div class="panel panel--tight">
      <ul class="list-plain">
        <?php foreach ($notifications as $n): ?>
          <li class="list-row" style="align-items:flex-start;<?= (int) $n['is_read'] ? '' : 'background:var(--brand-soft);margin:0 -16px;padding:12px 16px;border-radius:10px' ?>">
            <span class="cluster" style="align-items:flex-start;flex-wrap:nowrap">
              <span class="category-tile__icon" style="width:34px;height:34px;flex:none"><?= icon($icons[$n['type']] ?? 'bell', 17) ?></span>
              <span><strong><?= e($n['title']) ?></strong><?= (int) $n['is_read'] ? '' : ' <span class="sr-only">(belum dibaca)</span>' ?><br><span class="small"><?= e($n['message']) ?></span><br><small class="muted"><?= e(time_ago($n['created_at'])) ?></small></span>
            </span>
            <span class="btn-row" style="flex-wrap:nowrap">
              <?php if ($n['link']): ?>
                <form method="post" action="<?= e(url('/notifications/read')) ?>">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $n['id'] ?>"><input type="hidden" name="open" value="1">
                  <button class="btn btn--sm" type="submit">Buka</button>
                </form>
              <?php elseif (!(int) $n['is_read']): ?>
                <form method="post" action="<?= e(url('/notifications/read')) ?>">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                  <button class="btn btn--ghost btn--sm" type="submit">Dibaca</button>
                </form>
              <?php endif; ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?= view_partial('pagination', ['pagination' => $pagination]) ?>
  <?php endif; ?>
</div>

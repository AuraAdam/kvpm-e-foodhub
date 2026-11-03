<?php /** @var array $orders @var array $pagination @var string $filter @var array $filters */ ?>
<div class="container page" style="max-width:960px">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Pesanan Saya</span></nav>
  <div class="page-head"><div><h1>Pesanan Saya</h1><p>Semua pesanan anda, terbaru dahulu.</p></div></div>

  <nav class="tabs" aria-label="Tapis pesanan">
    <a href="<?= e(url('/orders')) ?>" class="<?= $filter === '' ? 'is-active' : '' ?>">Semua</a>
    <?php foreach ($filters as $key => $label): ?>
      <a href="<?= e(url('/orders', ['status' => $key])) ?>" class="<?= $filter === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$orders): ?>
    <?= view_partial('empty', ['icon' => 'receipt', 'title' => $filter === '' ? 'Belum ada pesanan' : 'Tiada pesanan dalam kategori ini', 'text' => 'Pesanan yang anda buat akan dipaparkan di sini bersama statusnya.', 'actionUrl' => '/products', 'actionLabel' => 'Mula membeli']) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table--stack">
        <thead><tr><th>Pesanan</th><th>Kedai</th><th>Bayaran</th><th>Status</th><th class="num">Jumlah</th><th><span class="sr-only">Tindakan</span></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td class="cell-main"><a class="table-title" href="<?= e(url('/orders/' . $o['order_number'])) ?>"><?= e($o['order_number']) ?></a><div class="table-sub"><?= e(format_date($o['created_at'])) ?></div></td>
              <td data-label="Kedai"><?= e(implode(', ', array_column($o['parts'], 'shop_name'))) ?></td>
              <td data-label="Bayaran"><?= e(payment_method_label($o['payment_method'])) ?></td>
              <td data-label="Status"><?= badge($o['summary_status']) ?><?php if (count($o['parts']) > 1): ?><div class="table-sub"><?= count($o['parts']) ?> kedai</div><?php endif; ?></td>
              <td data-label="Jumlah" class="num strong"><?= e(money($o['total'])) ?></td>
              <td data-label=""><a class="btn btn--sm" href="<?= e(url('/orders/' . $o['order_number'])) ?>">Lihat</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= view_partial('pagination', ['pagination' => $pagination]) ?>
  <?php endif; ?>
</div>

<?php /** @var array $orders @var array $pagination @var array $tabs @var string $status @var array $counts */ ?>
<div class="page-head"><div><h1>Pengurusan Pesanan</h1><p>Pesanan yang dibuat di kedai anda.</p></div></div>

<nav class="tabs" aria-label="Status pesanan">
  <?php foreach ($tabs as $key => $label):
      $n = $key === '' ? array_sum($counts) : ($key === 'closed' ? ($counts['rejected'] ?? 0) + ($counts['cancelled'] ?? 0) : ($counts[$key] ?? 0)); ?>
    <a href="<?= e(url('/seller/orders', ['status' => $key])) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"<?= $status === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="tab-count"><?= (int) $n ?></span></a>
  <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url('/seller/orders')) ?>" class="cluster mb-4" role="search">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <label class="sr-only" for="oq">Cari pesanan</label>
  <div class="search-field" style="flex:1;min-width:200px"><?= icon('search', 18) ?><input id="oq" type="search" name="q" value="<?= e(query('q')) ?>" placeholder="Cari rujukan atau nama pelanggan"></div>
  <button class="btn btn--dark" type="submit">Cari</button>
</form>

<?php if (!$orders): ?>
  <?= view_partial('empty', ['icon' => 'receipt', 'title' => 'Tiada pesanan', 'text' => $status === 'pending' ? 'Tiada pesanan yang menunggu pengesahan. Bagus!' : 'Tiada pesanan dalam senarai ini.', 'compact' => true]) ?>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <thead><tr><th>Rujukan</th><th>Pelanggan</th><th>Item</th><th>Bayaran</th><th>Status</th><th class="num">Jumlah</th><th><span class="sr-only">Tindakan</span></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="cell-main"><a class="table-title" href="<?= e(url('/seller/orders/' . $o['id'])) ?>"><?= e($o['reference']) ?></a><div class="table-sub"><?= e(format_date($o['created_at'])) ?></div></td>
            <td data-label="Pelanggan"><?= e($o['contact_name']) ?></td>
            <td data-label="Item"><?= (int) $o['units'] ?> unit</td>
            <td data-label="Bayaran"><?= badge($o['payment_status']) ?><div class="table-sub"><?= $o['payment_method'] === 'cash' ? 'Tunai' : 'Dalam talian (simulasi)' ?></div></td>
            <td data-label="Status"><?= badge($o['status']) ?></td>
            <td data-label="Jumlah" class="num strong"><?= e(money($o['subtotal'])) ?></td>
            <td data-label=""><a class="btn btn--sm<?= $o['status'] === 'pending' ? ' btn--primary' : '' ?>" href="<?= e(url('/seller/orders/' . $o['id'])) ?>"><?= $o['status'] === 'pending' ? 'Semak' : 'Lihat' ?></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

<?php /** @var array $orders @var array $pagination @var string $status */ ?>
<div class="page-head"><div><h1>Semua Pesanan</h1><p>Setiap baris ialah bahagian pesanan bagi satu kedai.</p></div></div>
<form method="get" action="<?= e(url('/admin/orders')) ?>" class="cluster mb-4" role="search">
  <label class="sr-only" for="aoq">Cari</label>
  <div class="search-field" style="flex:1;min-width:220px"><?= icon('search', 18) ?><input id="aoq" type="search" name="q" value="<?= e(query('q')) ?>" placeholder="Rujukan, pelanggan, kedai atau e-mel"></div>
  <label class="sr-only" for="aos">Status</label>
  <select class="select" id="aos" name="status" style="width:auto">
    <option value="">Semua status</option>
    <?php foreach (['pending', 'confirmed', 'preparing', 'ready', 'completed', 'rejected', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="aop">Bayaran</label>
  <select class="select" id="aop" name="payment" style="width:auto">
    <option value="">Semua bayaran</option>
    <?php foreach (['unpaid', 'paid', 'refund_due', 'void'] as $s): ?><option value="<?= $s ?>" <?= query('payment') === $s ? 'selected' : '' ?>><?= e(payment_status_label($s)) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn--dark" type="submit">Tapis</button>
</form>
<?php if (!$orders): ?>
  <?= view_partial('empty', ['icon' => 'receipt', 'title' => 'Tiada pesanan sepadan', 'compact' => true]) ?>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <thead><tr><th>Rujukan</th><th>Pelanggan</th><th>Kedai</th><th>Bayaran</th><th>Status</th><th class="num">Jumlah</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="cell-main"><a class="table-title" href="<?= e(url('/admin/orders/' . $o['order_number'])) ?>"><?= e($o['reference']) ?></a><div class="table-sub"><?= e(format_date($o['created_at'])) ?></div></td>
            <td data-label="Pelanggan"><?= e($o['contact_name']) ?><div class="table-sub"><?= e($o['customer_email']) ?></div></td>
            <td data-label="Kedai"><?= e($o['shop_name']) ?></td>
            <td data-label="Bayaran"><?= badge($o['payment_status']) ?><div class="table-sub"><?= $o['payment_method'] === 'cash' ? 'Tunai' : 'Simulasi' ?></div></td>
            <td data-label="Status"><?= badge($o['status']) ?></td>
            <td data-label="Jumlah" class="num strong"><?= e(money($o['subtotal'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

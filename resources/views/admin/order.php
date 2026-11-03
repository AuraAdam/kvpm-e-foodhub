<?php /** @var array $order @var array $sellerOrders @var array $payments @var array $notes */ use App\Services\OrderService; ?>
<nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/admin/orders')) ?>">Semua Pesanan</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($order['order_number']) ?></span></nav>
<div class="page-head">
  <div><p class="muted mb-0">Pesanan</p><div class="order-number"><?= e($order['order_number']) ?></div><p><?= e(format_date($order['created_at'])) ?> · <?= e(payment_method_label($order['payment_method'])) ?></p></div>
</div>

<div class="split split--wide-side">
  <div>
    <?php foreach ($sellerOrders as $so): $targets = OrderService::allowedTransitions('admin', $so['status']); ?>
      <article class="slip">
        <div class="slip__head">
          <div><div class="slip__ref"><?= e($so['reference']) ?></div><h2 class="slip__shop"><?= e($so['shop_name']) ?></h2><div class="cluster small"><?= badge($so['payment_status']) ?><?= $so['status_note'] ? '<span class="muted">Nota: ' . e($so['status_note']) . '</span>' : '' ?></div></div>
          <span class="stamp stamp--<?= e($so['status']) ?>"><?= e(order_status_label($so['status'])) ?></span>
        </div>
        <div class="slip__tear" aria-hidden="true"></div>
        <div class="slip__body">
          <ul class="slip-items">
            <?php foreach ($so['items'] as $item): ?>
              <li class="slip-item"><img src="<?= e(upload_url($item['image_path'])) ?>" alt="" width="52" height="52"><div><div class="slip-item__name"><?= e($item['product_name']) ?></div><div class="slip-item__meta"><?= (int) $item['quantity'] ?> × <?= e(money($item['unit_price'])) ?></div></div><strong class="num"><?= e(money($item['line_total'])) ?></strong></li>
            <?php endforeach; ?>
          </ul>
          <div class="slip-total"><span>Jumlah</span><span class="num"><?= e(money($so['subtotal'])) ?></span></div>
          <details class="reveal mt-4">
            <summary class="link-btn small"><?= icon('history', 14) ?> Sejarah status (<?= count($so['history']) ?>)</summary>
            <ul class="history"><?php foreach (array_reverse($so['history']) as $h): ?><li><div><?= e(order_status_label($h['to_status'])) ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?><small><?= e(format_date($h['created_at'])) ?> · <?= e(role_label($h['actor_role'])) ?></small></div></li><?php endforeach; ?></ul>
          </details>
          <?php if ($targets): ?>
            <details class="reveal mt-3">
              <summary class="btn btn--sm btn--danger"><?= icon('shield', 15) ?> Ubah status (pentadbir)</summary>
              <form method="post" action="<?= e(url('/admin/orders/' . $order['order_number'] . '/override')) ?>" class="form panel panel--tight" data-confirm="Ubah status pesanan ini sebagai pentadbir? Tindakan ini direkodkan dalam log audit dan pelanggan serta penjual akan dimaklumkan." data-confirm-tone="danger" data-confirm-ok="Ubah status">
                <?= csrf_field() ?><input type="hidden" name="seller_order_id" value="<?= (int) $so['id'] ?>">
                <div class="field"><label for="to-<?= (int) $so['id'] ?>">Status baharu</label>
                  <select class="select" id="to-<?= (int) $so['id'] ?>" name="to"><?php foreach ($targets as $t): ?><option value="<?= e($t) ?>"><?= e(order_status_label($t)) ?></option><?php endforeach; ?></select>
                  <span class="hint">Membatalkan memulangkan stok sekali sahaja. Pesanan yang telah dibayar akan ditandakan "perlu bayaran balik".</span>
                </div>
                <div class="field"><label for="reason-<?= (int) $so['id'] ?>">Sebab (wajib, direkodkan)</label><textarea class="textarea" id="reason-<?= (int) $so['id'] ?>" name="reason" required maxlength="255" style="min-height:70px"></textarea></div>
                <button class="btn btn--danger-solid" type="submit">Sahkan perubahan</button>
              </form>
            </details>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <aside class="stack">
    <section class="panel">
      <h2>Pelanggan</h2>
      <dl class="facts mt-0">
        <dt>Akaun</dt><dd><?= e($order['customer_name']) ?><br><span class="small muted"><?= e($order['customer_email']) ?></span></dd>
        <dt>Penerima</dt><dd><?= e($order['contact_name']) ?></dd>
        <dt>Telefon</dt><dd><?= e($order['contact_phone']) ?></dd>
        <?php if ($order['notes']): ?><dt>Nota</dt><dd style="white-space:pre-line"><?= e($order['notes']) ?></dd><?php endif; ?>
        <dt>Jumlah asal</dt><dd><?= e(money($order['total'])) ?></dd>
      </dl>
    </section>
    <section class="panel">
      <h2>Rekod bayaran</h2>
      <?php if ($payments): ?>
        <ul class="list-plain"><?php foreach ($payments as $p): ?><li class="list-row"><span class="small"><?= e($p['reference']) ?> · <?= $p['method'] === 'cash' ? 'Tunai' : 'Simulasi' ?><br><span class="muted"><?= e(format_date($p['created_at'])) ?></span></span><span class="cluster"><span class="num small"><?= e(money($p['amount'])) ?></span><?= badge($p['status']) ?></span></li><?php endforeach; ?></ul>
      <?php else: ?><p class="muted mb-0">Tiada rekod bayaran.</p><?php endif; ?>
    </section>
    <section class="panel">
      <h2>Nota penyelesaian</h2>
      <?php foreach ($notes as $n): ?>
        <div class="review"><p><?= e($n['note']) ?></p><small class="muted"><?= e($n['admin_name']) ?> · <?= e(format_date($n['created_at'])) ?></small></div>
      <?php endforeach; ?>
      <form method="post" action="<?= e(url('/admin/orders/' . $order['order_number'] . '/note')) ?>" class="form mt-4">
        <?= csrf_field() ?>
        <div class="field"><label for="anote">Tambah nota (aduan, siasatan, keputusan)</label><textarea class="textarea<?= error_for('note') ? ' is-invalid' : '' ?>" id="anote" name="note" required maxlength="2000"></textarea><?= view_partial('field_error', ['name' => 'note']) ?></div>
        <button class="btn" type="submit">Simpan nota</button>
      </form>
    </section>
  </aside>
</div>

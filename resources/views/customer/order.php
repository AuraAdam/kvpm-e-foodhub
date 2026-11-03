<?php
/**
 * @var array $order @var array $sellerOrders @var array $payable @var array $payments
 * @var array $reviewed @var bool $justPlaced
 */
$payableSen = array_sum(array_map(fn ($so) => to_sen($so['subtotal']), $payable));
?>
<div class="container page" style="max-width:960px">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/orders')) ?>">Pesanan Saya</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($order['order_number']) ?></span></nav>

  <?php if ($justPlaced): ?>
    <div class="alert alert--success mb-4"><?= icon('check', 18) ?><div class="alert__body"><strong>Pesanan diterima.</strong> Penjual akan mengesahkannya tidak lama lagi. Anda akan menerima notifikasi untuk setiap perubahan status.</div></div>
  <?php endif; ?>

  <div class="page-head">
    <div>
      <p class="muted mb-0">Nombor pesanan</p>
      <div class="order-number"><?= e($order['order_number']) ?></div>
      <p><?= e(format_date($order['created_at'])) ?> · <?= e(payment_method_label($order['payment_method'])) ?></p>
    </div>
    <div class="btn-row no-print">
      <button type="button" class="btn" hidden data-print><?= icon('printer', 16) ?> Cetak</button>
      <?php if ($payable): ?>
        <a class="btn btn--primary" href="<?= e(url('/orders/' . $order['order_number'] . '/pay')) ?>"><?= icon('wallet', 16) ?> Bayar <?= e(money(sen_to_decimal($payableSen))) ?></a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($payable): ?>
    <div class="alert alert--warning mb-4"><?= icon('clock', 18) ?><div class="alert__body">Pesanan ini menunggu bayaran dalam talian (simulasi). Penjual tidak boleh mengesahkannya sehingga bayaran selesai.</div></div>
  <?php endif; ?>

  <?php foreach ($sellerOrders as $so): ?>
    <article class="slip" aria-labelledby="slip-<?= (int) $so['id'] ?>">
      <div class="slip__head">
        <div>
          <div class="slip__ref">Rujukan <?= e($so['reference']) ?></div>
          <h2 class="slip__shop" id="slip-<?= (int) $so['id'] ?>"><a href="<?= e(url('/shop/' . $so['shop_slug'])) ?>" style="color:inherit;text-decoration:none"><?= e($so['shop_name']) ?></a></h2>
          <div class="small muted"><?= icon('pin', 13) ?> <?= e($so['collection_location']) ?><?= $so['collection_hours'] ? ' · ' . e($so['collection_hours']) : '' ?></div>
          <div class="small muted"><?= icon('phone', 13) ?> <?= e($so['shop_phone']) ?></div>
        </div>
        <span class="stamp stamp--<?= e($so['status']) ?>"><?= e(order_status_label($so['status'])) ?></span>
      </div>
      <div class="slip__tear" aria-hidden="true"></div>
      <div class="slip__body">
        <?= view_partial('order_track', ['status' => $so['status'], 'note' => $so['status_note']]) ?>

        <ul class="slip-items">
          <?php foreach ($so['items'] as $item): ?>
            <li>
              <div class="slip-item">
                <img src="<?= e(upload_url($item['image_path'])) ?>" alt="" width="52" height="52" loading="lazy">
                <div>
                  <div class="slip-item__name"><?= e($item['product_name']) ?></div>
                  <div class="slip-item__meta"><?= (int) $item['quantity'] ?> × <?= e(money($item['unit_price'])) ?></div>
                </div>
                <strong class="num"><?= e(money($item['line_total'])) ?></strong>
              </div>
              <?php if ($so['status'] === 'completed'): ?>
                <?php if (isset($reviewed[(int) $item['id']])): ?>
                  <p class="small muted mt-2 mb-0" style="padding-left:64px"><?= stars((float) $reviewed[(int) $item['id']], 12) ?> Anda telah mengulas item ini.</p>
                <?php else: ?>
                  <details class="reveal mt-2" style="padding-left:64px">
                    <summary class="link-btn small"><?= icon('star', 14) ?> Tulis ulasan</summary>
                    <form method="post" action="<?= e(url('/reviews')) ?>" class="form panel panel--tight">
                      <?= csrf_field() ?>
                      <input type="hidden" name="order_item_id" value="<?= (int) $item['id'] ?>">
                      <fieldset class="field" style="border:0;padding:0;margin:0">
                        <legend class="label">Penilaian</legend>
                        <div class="cluster">
                          <?php for ($r = 5; $r >= 1; $r--): ?>
                            <label class="check"><input type="radio" name="rating" value="<?= $r ?>" <?= $r === 5 ? 'checked' : '' ?> required> <?= $r ?> bintang</label>
                          <?php endfor; ?>
                        </div>
                      </fieldset>
                      <div class="field">
                        <label for="comment-<?= (int) $item['id'] ?>">Ulasan (pilihan)</label>
                        <textarea class="textarea" id="comment-<?= (int) $item['id'] ?>" name="comment" maxlength="1000" placeholder="Bagaimana rasa, saiz atau kualiti produk ini?"></textarea>
                      </div>
                      <button class="btn btn--primary" type="submit">Terbitkan ulasan</button>
                    </form>
                  </details>
                <?php endif; ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="slip-total"><span>Jumlah kedai ini</span><span class="num"><?= e(money($so['subtotal'])) ?></span></div>
        <div class="cluster mt-3 small">
          <span class="muted">Bayaran:</span> <?= badge($so['payment_status']) ?>
          <?php if ($so['payment_status'] === 'refund_due'): ?><span class="muted">Hubungi penjual atau pentadbir untuk bayaran balik. Sistem ini tidak memproses bayaran balik secara automatik.</span><?php endif; ?>
        </div>

        <?php if (count($so['history']) > 1): ?>
          <details class="reveal mt-4">
            <summary class="link-btn small"><?= icon('history', 14) ?> Sejarah status</summary>
            <ul class="history">
              <?php foreach (array_reverse($so['history']) as $h): ?>
                <li><div><?= e(order_status_label($h['to_status'])) ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?><small><?= e(format_date($h['created_at'])) ?> · <?= e(role_label($h['actor_role'])) ?></small></div></li>
              <?php endforeach; ?>
            </ul>
          </details>
        <?php endif; ?>

        <?php if ($so['status'] === 'pending'): ?>
          <form method="post" action="<?= e(url('/orders/' . $order['order_number'] . '/cancel')) ?>" class="mt-4 no-print"
                data-confirm="Batalkan pesanan dari <?= e($so['shop_name']) ?>? Tindakan ini tidak boleh dibuat asal." data-confirm-ok="Ya, batalkan" data-confirm-tone="danger">
            <?= csrf_field() ?>
            <input type="hidden" name="seller_order_id" value="<?= (int) $so['id'] ?>">
            <button class="btn btn--danger btn--sm" type="submit"><?= icon('x', 15) ?> Batalkan pesanan ini</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>

  <section class="panel mt-5">
    <div class="panel__head"><h2>Ringkasan</h2></div>
    <div class="summary">
      <div class="summary__row"><span>Nama penerima</span><span><?= e($order['contact_name']) ?></span></div>
      <div class="summary__row"><span>Telefon</span><span><?= e($order['contact_phone']) ?></span></div>
      <?php if ($order['notes']): ?><div class="summary__row"><span>Nota</span><span style="text-align:right;white-space:pre-line"><?= e($order['notes']) ?></span></div><?php endif; ?>
      <div class="summary__row summary__row--total"><span>Jumlah semasa pesanan dibuat</span><span class="num"><?= e(money($order['total'])) ?></span></div>
    </div>
    <?php if ($payments): ?>
      <h3 class="mt-5">Rekod bayaran</h3>
      <ul class="list-plain">
        <?php foreach ($payments as $pay): ?>
          <li class="list-row"><span><?= e($pay['reference']) ?> · <?= e(payment_method_label($pay['method'])) ?><br><small class="muted"><?= e(format_date($pay['created_at'])) ?></small></span><span class="cluster"><span class="num"><?= e(money($pay['amount'])) ?></span><?= badge($pay['status']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php
/** @var array $so @var array $items @var array $history @var array $payments @var array $transitions */
use App\Services\OrderService;

$forward = array_values(array_diff($transitions, ['rejected']));
$canReject = in_array('rejected', $transitions, true);
$awaitingPayment = $so['payment_method'] === 'online_sim' && $so['payment_status'] !== 'paid' && $so['status'] === 'pending';
?>
<nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/seller/orders')) ?>">Pengurusan Pesanan</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($so['reference']) ?></span></nav>

<div class="split split--wide-side">
  <article class="slip" aria-labelledby="slip-title">
    <div class="slip__head">
      <div>
        <div class="slip__ref">Dibuat <?= e(format_date($so['ordered_at'])) ?></div>
        <h1 class="slip__shop" id="slip-title" style="font-size:1.5rem"><?= e($so['reference']) ?></h1>
        <div class="small muted">Pesanan induk <?= e($so['order_number']) ?></div>
      </div>
      <span class="stamp stamp--<?= e($so['status']) ?>"><?= e(order_status_label($so['status'])) ?></span>
    </div>
    <div class="slip__tear" aria-hidden="true"></div>
    <div class="slip__body">
      <?= view_partial('order_track', ['status' => $so['status'], 'note' => $so['status_note']]) ?>
      <ul class="slip-items">
        <?php foreach ($items as $item): ?>
          <li class="slip-item">
            <img src="<?= e(upload_url($item['image_path'])) ?>" alt="" width="52" height="52">
            <div><div class="slip-item__name"><?= e($item['product_name']) ?></div><div class="slip-item__meta"><?= e(money($item['unit_price'])) ?> seunit</div></div>
            <strong class="num">× <?= (int) $item['quantity'] ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="slip-total"><span>Jumlah</span><span class="num"><?= e(money($so['subtotal'])) ?></span></div>

      <?php if ($so['notes']): ?>
        <div class="alert alert--info mt-4"><?= icon('info', 18) ?><div class="alert__body"><strong>Nota pelanggan:</strong><br><span style="white-space:pre-line"><?= e($so['notes']) ?></span></div></div>
      <?php endif; ?>
    </div>
  </article>

  <aside class="stack">
    <section class="panel">
      <h2>Tindakan</h2>
      <?php if ($awaitingPayment): ?>
        <div class="alert alert--warning mb-4"><?= icon('clock', 18) ?><div class="alert__body">Menunggu pelanggan membuat bayaran dalam talian (simulasi). Anda boleh mengesahkan selepas bayaran diterima.</div></div>
      <?php endif; ?>
      <?php if (!$transitions): ?>
        <p class="muted mb-0">Tiada tindakan lagi untuk pesanan ini.</p>
      <?php endif; ?>
      <div class="stack-sm">
        <?php foreach ($forward as $to): ?>
          <form method="post" action="<?= e(url('/seller/orders/' . $so['id'] . '/status')) ?>"
                <?= $to === 'completed' ? 'data-confirm="Tandakan pesanan ini selesai?' . ($so['payment_method'] === 'cash' && $so['payment_status'] === 'unpaid' ? ' Ini juga merekodkan bahawa anda telah menerima bayaran tunai ' . e(money($so['subtotal'])) . '.' : '') . '" data-confirm-ok="Ya, selesai"' : '' ?>>
            <?= csrf_field() ?>
            <input type="hidden" name="to" value="<?= e($to) ?>">
            <button class="btn btn--primary btn--block" type="submit" <?= $to === 'confirmed' && $awaitingPayment ? 'disabled' : '' ?>><?= icon('check', 16) ?> <?= e(OrderService::ACTION_LABELS[$to]) ?></button>
          </form>
        <?php endforeach; ?>
        <?php if ($canReject): ?>
          <details class="reveal">
            <summary class="btn btn--danger btn--block"><?= icon('x', 16) ?> Tolak pesanan</summary>
            <form method="post" action="<?= e(url('/seller/orders/' . $so['id'] . '/status')) ?>" class="form" data-confirm="Tolak pesanan ini? Stok akan dipulangkan dan pelanggan akan dimaklumkan." data-confirm-ok="Tolak pesanan" data-confirm-tone="danger">
              <?= csrf_field() ?>
              <input type="hidden" name="to" value="rejected">
              <div class="field">
                <label for="note">Sebab (dipaparkan kepada pelanggan)</label>
                <textarea class="textarea" id="note" name="note" required maxlength="255" placeholder="Contoh: Bahan habis hari ini"></textarea>
              </div>
              <button class="btn btn--danger-solid" type="submit">Hantar penolakan</button>
            </form>
          </details>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <h2>Pelanggan</h2>
      <dl class="facts mt-0">
        <dt>Nama</dt><dd><?= e($so['contact_name']) ?></dd>
        <dt>Telefon</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $so['contact_phone'])) ?>"><?= e($so['contact_phone']) ?></a></dd>
        <dt>Pengambilan</dt><dd>Ambil sendiri di gerai</dd>
        <dt>Bayaran</dt><dd><?= e(payment_method_label($so['payment_method'])) ?><br><?= badge($so['payment_status']) ?></dd>
      </dl>
      <?php if ($so['payment_status'] === 'refund_due'): ?>
        <p class="small text-danger mt-3 mb-0">Pesanan ini telah dibayar sebelum dibatalkan. Selesaikan bayaran balik dengan pelanggan secara manual — sistem tidak memproses bayaran balik.</p>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Sejarah status</h2>
      <ul class="history">
        <?php foreach (array_reverse($history) as $h): ?>
          <li><div><?= e(order_status_label($h['to_status'])) ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?><small><?= e(format_date($h['created_at'])) ?> · <?= e(role_label($h['actor_role'])) ?></small></div></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($payments): ?>
        <h3 class="mt-4" style="font-size:.95rem">Rekod bayaran</h3>
        <ul class="list-plain">
          <?php foreach ($payments as $p): ?>
            <li class="list-row"><span class="small"><?= e($p['reference']) ?><br><span class="muted"><?= e(format_date($p['created_at'])) ?></span></span><span class="cluster"><span class="num small"><?= e(money($p['amount'])) ?></span><?= badge($p['status']) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </aside>
</div>

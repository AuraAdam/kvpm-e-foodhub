<?php /** @var array $order @var array $payable @var int $amountSen @var array $attempts */ ?>
<section class="auth">
  <div class="auth__card">
    <span class="tag tag--sim mb-2">Simulasi — bukan bayaran sebenar</span>
    <h1 class="mt-2">Bayaran dalam talian</h1>
    <p>Pesanan <strong><?= e($order['order_number']) ?></strong></p>

    <?php if (!$payable): ?>
      <div class="alert alert--success"><?= icon('check', 18) ?><div class="alert__body">Tiada bayaran tertunggak untuk pesanan ini.</div></div>
      <a class="btn btn--primary btn--block mt-4" href="<?= e(url('/orders/' . $order['order_number'])) ?>">Lihat pesanan</a>
    <?php else: ?>
      <div class="panel panel--tight mt-4">
        <div class="summary">
          <?php foreach ($payable as $so): ?>
            <div class="summary__row"><span><?= e($so['reference']) ?></span><span class="num"><?= e(money($so['subtotal'])) ?></span></div>
          <?php endforeach; ?>
          <div class="summary__row summary__row--total"><span>Perlu dibayar</span><span class="num"><?= e(money(sen_to_decimal($amountSen))) ?></span></div>
        </div>
      </div>

      <div class="alert alert--info mt-4"><?= icon('info', 18) ?><div class="alert__body">Ini ialah <strong>gerbang bayaran simulasi</strong> untuk demonstrasi projek. Tiada wang dipindahkan, dan sistem ini tidak akan sekali-kali meminta nombor kad, CVV, kata laluan bank atau kod TAC/OTP. Pilih keputusan yang ingin diuji.</div></div>

      <div class="stack mt-4">
        <form method="post" action="<?= e(url('/orders/' . $order['order_number'] . '/pay')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="outcome" value="success">
          <button class="btn btn--primary btn--lg btn--block" type="submit"><?= icon('check', 18) ?> Simulasikan bayaran berjaya</button>
        </form>
        <form method="post" action="<?= e(url('/orders/' . $order['order_number'] . '/pay')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="outcome" value="failure">
          <button class="btn btn--danger btn--block" type="submit"><?= icon('x', 18) ?> Simulasikan bayaran gagal</button>
        </form>
        <a class="btn btn--ghost btn--block" href="<?= e(url('/orders/' . $order['order_number'])) ?>">Bayar kemudian</a>
      </div>
    <?php endif; ?>

    <?php if ($attempts): ?>
      <h2 class="mt-5" style="font-size:1rem">Cubaan bayaran</h2>
      <ul class="list-plain">
        <?php foreach ($attempts as $a): ?>
          <li class="list-row"><span><?= e($a['reference']) ?><br><small class="muted"><?= e(format_date($a['created_at'])) ?></small></span><span class="cluster"><span class="num"><?= e(money($a['amount'])) ?></span><?= badge($a['status']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

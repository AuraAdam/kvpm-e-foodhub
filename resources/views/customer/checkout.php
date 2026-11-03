<?php /** @var array $cart @var array $user @var string $checkoutToken @var bool $onlineEnabled */ ?>
<div class="container page">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/cart')) ?>">Troli</a><?= icon('chevron-right', 14) ?><span aria-current="page">Buat pesanan</span></nav>
  <div class="page-head"><div><h1>Buat pesanan</h1><p>Semak maklumat anda, kemudian hantar pesanan kepada penjual.</p></div></div>

  <form method="post" action="<?= e(url('/checkout')) ?>" class="split" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="checkout_token" value="<?= e($checkoutToken) ?>">
    <div class="stack">
      <?= view_partial('validation_summary') ?>
      <section class="panel">
        <h2>Maklumat pengambilan</h2>
        <div class="form">
          <div class="form-grid form-grid--2">
            <div class="field">
              <label for="contact_name">Nama penerima <span class="req" aria-hidden="true">*</span></label>
              <input class="input<?= error_for('contact_name') ? ' is-invalid' : '' ?>" id="contact_name" name="contact_name" value="<?= e(old('contact_name', $user['name'])) ?>" required maxlength="100" autocomplete="name">
              <?= view_partial('field_error', ['name' => 'contact_name']) ?>
            </div>
            <div class="field">
              <label for="contact_phone">Nombor telefon <span class="req" aria-hidden="true">*</span></label>
              <input class="input<?= error_for('contact_phone') ? ' is-invalid' : '' ?>" id="contact_phone" type="tel" name="contact_phone" value="<?= e(old('contact_phone', (string) $user['phone'])) ?>" required autocomplete="tel">
              <?= view_partial('field_error', ['name' => 'contact_phone']) ?>
            </div>
          </div>
          <div class="field">
            <label for="notes">Nota untuk penjual (pilihan)</label>
            <textarea class="textarea" id="notes" name="notes" maxlength="500" placeholder="Contoh: kurang pedas, teh tarik ais, ambil pukul 12.30"><?= e(old('notes')) ?></textarea>
            <?= view_partial('field_error', ['name' => 'notes']) ?>
          </div>
          <div class="alert alert--info"><?= icon('pin', 18) ?><div class="alert__body"><strong>Ambil sendiri di gerai.</strong> <?= e(setting('collection_info')) ?></div></div>
        </div>
      </section>

      <section class="panel">
        <h2 id="pay-title">Kaedah pembayaran</h2>
        <div class="choice-cards" role="radiogroup" aria-labelledby="pay-title">
          <label class="choice-card">
            <input type="radio" name="payment_method" value="cash" <?= old('payment_method', 'cash') === 'cash' ? 'checked' : '' ?>>
            <div><strong><?= icon('wallet', 16) ?> Tunai semasa ambil</strong><span>Bayar kepada penjual apabila anda mengambil pesanan.</span></div>
          </label>
          <?php if ($onlineEnabled): ?>
            <label class="choice-card">
              <input type="radio" name="payment_method" value="online_sim" <?= old('payment_method') === 'online_sim' ? 'checked' : '' ?>>
              <div><strong>Bayaran dalam talian <span class="tag tag--sim">Simulasi</span></strong><span>Untuk demonstrasi sahaja. Tiada wang sebenar dipindahkan dan tiada maklumat kad atau bank diminta.</span></div>
            </label>
          <?php endif; ?>
        </div>
        <?= view_partial('field_error', ['name' => 'payment_method']) ?>
      </section>
    </div>

    <aside class="panel sticky" aria-label="Ringkasan pesanan">
      <h2>Pesanan anda</h2>
      <?php foreach ($cart['groups'] as $group): ?>
        <div class="mb-4">
          <p class="strong mb-2"><?= icon('store', 15) ?> <?= e($group['shop_name']) ?></p>
          <ul class="list-plain">
            <?php foreach ($group['items'] as $item): ?>
              <li class="summary__row"><span><?= (int) $item['quantity'] ?> × <?= e($item['name']) ?></span><span class="num"><?= e(money(sen_to_decimal($item['line_sen']))) ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <div class="summary">
        <div class="summary__row summary__row--total"><span>Jumlah</span><span class="num"><?= e(money(sen_to_decimal($cart['total_sen']))) ?></span></div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">Hantar pesanan</button>
        <a class="btn btn--ghost btn--block" href="<?= e(url('/cart')) ?>">Kembali ke troli</a>
      </div>
    </aside>
  </form>
</div>

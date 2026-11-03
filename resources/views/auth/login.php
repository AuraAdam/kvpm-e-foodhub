<?php $isDemo = config('app.env') !== 'production'; ?>
<section class="auth">
  <div class="auth__card">
    <h1>Log masuk</h1>
    <p>Selamat kembali. Log masuk untuk membuat dan menjejak pesanan.</p>
    <?php if (error_for('email')): ?>
      <div class="alert alert--error mb-4" role="alert"><?= icon('alert', 18) ?><div class="alert__body"><?= e(error_for('email')) ?></div></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/login')) ?>" class="form" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">E-mel</label>
        <input class="input<?= error_for('email') ? ' is-invalid' : '' ?>" id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="username" autofocus>
      </div>
      <div class="field">
        <label for="password">Kata laluan</label>
        <div class="password-field">
          <input class="input" id="password" type="password" name="password" required autocomplete="current-password">
          <button type="button" class="password-toggle" data-toggle-password aria-label="Tunjuk kata laluan" aria-pressed="false"><?= icon('eye', 18) ?></button>
        </div>
      </div>
      <button class="btn btn--primary btn--lg btn--block" type="submit">Log masuk</button>
      <div class="cluster" style="justify-content:space-between">
        <a class="small" href="<?= e(url('/forgot-password')) ?>">Lupa kata laluan?</a>
        <span class="small">Belum ada akaun? <a href="<?= e(url('/register')) ?>">Daftar</a></span>
      </div>
    </form>
    <?php if ($isDemo): ?>
      <div class="demo-box">
        <strong>Akaun demo (persekitaran pembangunan sahaja)</strong>
        <table>
          <tr><td>Pelanggan</td><td><code>pelanggan@demo.test</code></td></tr>
          <tr><td>Penjual</td><td><code>kantin@demo.test</code></td></tr>
          <tr><td>Pentadbir</td><td><code>admin@demo.test</code></td></tr>
          <tr><td>Kata laluan</td><td><code>Demo@1234</code></td></tr>
        </table>
        <p class="hint mt-2 mb-0">Kotak ini tidak dipaparkan apabila APP_ENV=production. Jangan gunakan akaun demo dalam pengeluaran.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

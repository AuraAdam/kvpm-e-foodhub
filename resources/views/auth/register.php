<?php $min = (int) config('security.password_min_length'); ?>
<section class="auth">
  <div class="auth__card">
    <h1>Daftar akaun</h1>
    <p>Pendaftaran terbuka kepada orang awam. Akaun baharu bermula sebagai pembeli; anda boleh memohon untuk membuka kedai dan menunggu kelulusan pentadbir.</p>
    <?= view_partial('validation_summary') ?>
    <form method="post" action="<?= e(url('/register')) ?>" class="form mt-4" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="name">Nama penuh <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= error_for('name') ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= e(old('name')) ?>" required maxlength="100" autocomplete="name" aria-describedby="name-error">
        <?= view_partial('field_error', ['name' => 'name']) ?>
      </div>
      <div class="field">
        <label for="email">E-mel <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= error_for('email') ? ' is-invalid' : '' ?>" id="email" type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="150" autocomplete="email" aria-describedby="email-error">
        <?= view_partial('field_error', ['name' => 'email']) ?>
      </div>
      <div class="field">
        <label for="phone">Nombor telefon <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= error_for('phone') ? ' is-invalid' : '' ?>" id="phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" required placeholder="012-345 6789" autocomplete="tel" aria-describedby="phone-hint phone-error">
        <span class="hint" id="phone-hint">Penjual menggunakan nombor ini untuk menghubungi anda tentang pesanan.</span>
        <?= view_partial('field_error', ['name' => 'phone']) ?>
      </div>
      <div class="field">
        <label for="password">Kata laluan <span class="req" aria-hidden="true">*</span></label>
        <div class="password-field">
          <input class="input<?= error_for('password') ? ' is-invalid' : '' ?>" id="password" type="password" name="password" required minlength="<?= $min ?>" autocomplete="new-password" aria-describedby="password-hint password-error">
          <button type="button" class="password-toggle" data-toggle-password aria-label="Tunjuk kata laluan" aria-pressed="false"><?= icon('eye', 18) ?></button>
        </div>
        <span class="hint" id="password-hint">Sekurang-kurangnya <?= $min ?> aksara, dengan huruf dan nombor.</span>
        <?= view_partial('field_error', ['name' => 'password']) ?>
      </div>
      <div class="field">
        <label for="password_confirmation">Sahkan kata laluan <span class="req" aria-hidden="true">*</span></label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
      </div>
      <label class="check"><input type="checkbox" name="agree" value="1" <?= old('agree') ? 'checked' : '' ?>> <span>Saya bersetuju dengan <a href="<?= e(url('/info/terms')) ?>" target="_blank">terma penggunaan</a> dan <a href="<?= e(url('/info/privacy')) ?>" target="_blank">notis privasi</a>.</span></label>
      <?= view_partial('field_error', ['name' => 'agree']) ?>
      <button class="btn btn--primary btn--lg btn--block" type="submit">Daftar akaun</button>
      <p class="small text-center mb-0">Sudah ada akaun? <a href="<?= e(url('/login')) ?>">Log masuk</a></p>
    </form>
  </div>
</section>

<?php /** @var bool $forced */ $min = (int) config('security.password_min_length'); ?>
<section class="auth">
  <div class="auth__card">
    <h1>Tukar kata laluan</h1>
    <?php if ($forced): ?>
      <div class="alert alert--warning mb-4"><?= icon('lock', 18) ?><div class="alert__body">Anda log masuk dengan kata laluan sementara. Pilih kata laluan baharu untuk meneruskan.</div></div>
    <?php else: ?>
      <p>Gunakan kata laluan yang tidak anda gunakan di laman lain.</p>
    <?php endif; ?>
    <?= view_partial('validation_summary') ?>
    <form method="post" action="<?= e(url('/account/password')) ?>" class="form mt-4" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="current_password"><?= $forced ? 'Kata laluan sementara' : 'Kata laluan semasa' ?></label>
        <input class="input<?= error_for('current_password') ? ' is-invalid' : '' ?>" id="current_password" type="password" name="current_password" required autocomplete="current-password">
        <?= view_partial('field_error', ['name' => 'current_password']) ?>
      </div>
      <div class="field">
        <label for="password">Kata laluan baharu</label>
        <div class="password-field">
          <input class="input<?= error_for('password') ? ' is-invalid' : '' ?>" id="password" type="password" name="password" required minlength="<?= $min ?>" autocomplete="new-password" aria-describedby="pw-hint">
          <button type="button" class="password-toggle" data-toggle-password aria-label="Tunjuk kata laluan" aria-pressed="false"><?= icon('eye', 18) ?></button>
        </div>
        <span class="hint" id="pw-hint">Sekurang-kurangnya <?= $min ?> aksara, dengan huruf dan nombor.</span>
        <?= view_partial('field_error', ['name' => 'password']) ?>
      </div>
      <div class="field">
        <label for="password_confirmation">Sahkan kata laluan baharu</label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
      </div>
      <button class="btn btn--primary btn--lg btn--block" type="submit">Tukar kata laluan</button>
      <?php if (!$forced): ?><a class="btn btn--ghost btn--block" href="<?= e(url('/account')) ?>">Batal</a><?php endif; ?>
    </form>
  </div>
</section>

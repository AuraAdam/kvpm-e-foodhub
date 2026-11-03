<?php /** @var array $definitions @var array $values */ ?>
<div class="page-head"><div><h1>Tetapan sistem</h1><p>Perubahan direkodkan dalam log audit.</p></div></div>
<?= view_partial('validation_summary') ?>
<form method="post" action="<?= e(url('/admin/settings')) ?>" class="panel form mt-4" style="max-width:760px" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($definitions as $key => $def): $v = old($key, $values[$key] ?? ''); ?>
    <?php if ($def['type'] === 'bool'): ?>
      <label class="check"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= ($values[$key] ?? '0') === '1' ? 'checked' : '' ?>> <?= e($def['label']) ?></label>
    <?php elseif ($def['type'] === 'textarea'): ?>
      <div class="field"><label for="<?= e($key) ?>"><?= e($def['label']) ?></label><textarea class="textarea<?= error_for($key) ? ' is-invalid' : '' ?>" id="<?= e($key) ?>" name="<?= e($key) ?>" maxlength="<?= (int) $def['max'] ?>"><?= e($v) ?></textarea><?= view_partial('field_error', ['name' => $key]) ?></div>
    <?php else: ?>
      <div class="field"><label for="<?= e($key) ?>"><?= e($def['label']) ?></label><input class="input<?= error_for($key) ? ' is-invalid' : '' ?>" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($v) ?>" <?= $def['type'] === 'int' ? 'type="number" min="' . (int) $def['min'] . '" max="' . (int) $def['max'] . '"' : ($def['type'] === 'email' ? 'type="email"' : '') ?>><?= view_partial('field_error', ['name' => $key]) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>
  <div class="alert alert--info"><?= icon('info', 18) ?><div class="alert__body">Bayaran dalam talian sentiasa simulasi. Tiada gerbang bayaran sebenar disambungkan dalam versi ini.</div></div>
  <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan tetapan</button></div>
</form>

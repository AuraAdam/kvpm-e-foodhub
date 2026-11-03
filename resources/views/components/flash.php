<?php
$messages = take_flash();
$icons = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'];
if (!$messages) {
    return;
}
?>
<div class="flash-stack" role="status" aria-live="polite">
  <?php foreach ($messages as $m): $type = isset($icons[$m['type']]) ? $m['type'] : 'info'; ?>
    <div class="alert alert--<?= e($type) ?>">
      <?= icon($icons[$type], 18) ?>
      <div class="alert__body"><?= e($m['message']) ?></div>
      <button type="button" class="alert__close" data-dismiss aria-label="Tutup mesej"><?= icon('x', 16) ?></button>
    </div>
  <?php endforeach; ?>
</div>

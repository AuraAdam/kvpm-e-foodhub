<?php
/**
 * @var string $icon
 * @var string $title
 * @var string $text
 * @var ?string $actionUrl
 * @var ?string $actionLabel
 * @var bool $compact
 */
?>
<div class="empty<?= !empty($compact) ? ' empty--compact' : '' ?>">
  <div class="empty__icon"><?= icon($icon ?? 'package', 30) ?></div>
  <h3><?= e($title) ?></h3>
  <?php if (!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
  <?php if (!empty($actionUrl)): ?>
    <a class="btn btn--primary" href="<?= e(url($actionUrl)) ?>"><?= e($actionLabel ?? 'Teruskan') ?></a>
  <?php endif; ?>
</div>

<?php /** @var string $name */ if ($msg = error_for($name)): ?>
<p class="field-error" id="<?= e($name) ?>-error"><?= icon('alert', 13) ?> <?= e($msg) ?></p>
<?php endif; ?>

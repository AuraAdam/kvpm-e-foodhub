<?php $all = errors(); if (!$all) { return; } ?>
<div class="alert alert--error validation-summary" role="alert" tabindex="-1">
  <?= icon('alert', 18) ?>
  <div class="alert__body">
    <strong>Sila semak <?= count($all) === 1 ? 'ruangan' : count($all) . ' ruangan' ?> di bawah.</strong>
    <ul><?php foreach ($all as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
  </div>
</div>

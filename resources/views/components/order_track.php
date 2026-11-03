<?php
/** Progress track for one seller order. @var string $status */
$flow = \App\Services\OrderService::FLOW;
$closed = in_array($status, ['rejected', 'cancelled'], true);
$position = array_search($status, $flow, true);
$labels = ['pending' => 'Menunggu', 'confirmed' => 'Disahkan', 'preparing' => 'Disediakan', 'ready' => 'Sedia diambil', 'completed' => 'Selesai'];
?>
<?php if ($closed): ?>
  <p class="alert alert--error mt-2"><?= icon('x', 16) ?> <span class="alert__body">Pesanan ini <?= e(mb_strtolower(order_status_label($status))) ?><?= !empty($note) ? ': ' . e($note) : '.' ?></span></p>
<?php else: ?>
  <ol class="track" aria-label="Status pesanan: <?= e(order_status_label($status)) ?>">
    <?php foreach ($flow as $i => $step): ?>
      <li class="<?= $i <= $position ? 'is-done' : '' ?><?= $i === $position ? ' is-current' : '' ?>"<?= $i === $position ? ' aria-current="step"' : '' ?>><?= e($labels[$step]) ?></li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>

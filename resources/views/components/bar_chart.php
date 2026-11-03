<?php
/**
 * Accessible CSS bar chart.
 * @var array $series list of ['day' => 'Y-m-d', 'total' => float, 'orders' => int]
 */
$max = max(array_merge([0], array_column($series, 'total')));
$hasData = $max > 0;
$every = max(1, (int) ceil(count($series) / 10));
?>
<?php if (!$hasData): ?>
  <?= view_partial('empty', ['icon' => 'chart', 'title' => 'Belum ada jualan dalam tempoh ini', 'text' => 'Graf akan dipaparkan selepas pesanan pertama ditandakan selesai.', 'compact' => true]) ?>
<?php else: ?>
  <div class="bars" role="img" aria-label="Jualan harian selesai bagi <?= count($series) ?> hari, tertinggi <?= e(money($max)) ?>">
    <?php foreach ($series as $point): $h = $point['total'] > 0 ? max(3, round($point['total'] / $max * 100)) : 0; ?>
      <div class="bars__col" tabindex="0">
        <span class="bars__tip"><?= e(format_date($point['day'], false)) ?>: <?= e(money($point['total'])) ?> · <?= (int) $point['orders'] ?> pesanan</span>
        <div class="bars__bar<?= $h === 0 ? ' is-zero' : '' ?>" style="height: <?= $h ?>%"></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="bars-axis" aria-hidden="true">
    <?php foreach ($series as $i => $point): ?>
      <span><?= ($i % $every === 0 || $i === count($series) - 1) ? e(date('j/n', strtotime($point['day']))) : '' ?></span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
/** @var array $pagination from paginate() */
if (($pagination['pages'] ?? 1) <= 1) {
    return;
}
$page = $pagination['page'];
$pages = $pagination['pages'];
$window = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], fn ($p) => $p >= 1 && $p <= $pages));
sort($window);
?>
<nav class="pagination" aria-label="Halaman">
  <?php if ($page > 1): ?>
    <a href="<?= e(url_with(['page' => $page - 1])) ?>" aria-label="Halaman sebelum"><?= icon('chevron-left', 16) ?></a>
  <?php else: ?>
    <span class="is-disabled" aria-hidden="true"><?= icon('chevron-left', 16) ?></span>
  <?php endif; ?>
  <?php $prev = 0; foreach ($window as $p): ?>
    <?php if ($p - $prev > 1): ?><span class="is-disabled" aria-hidden="true">…</span><?php endif; ?>
    <?php if ($p === $page): ?>
      <span class="is-current" aria-current="page"><?= $p ?></span>
    <?php else: ?>
      <a href="<?= e(url_with(['page' => $p])) ?>"><?= $p ?></a>
    <?php endif; ?>
  <?php $prev = $p; endforeach; ?>
  <?php if ($page < $pages): ?>
    <a href="<?= e(url_with(['page' => $page + 1])) ?>" aria-label="Halaman seterusnya"><?= icon('chevron-right', 16) ?></a>
  <?php else: ?>
    <span class="is-disabled" aria-hidden="true"><?= icon('chevron-right', 16) ?></span>
  <?php endif; ?>
</nav>

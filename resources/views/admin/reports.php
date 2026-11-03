<?php /** @var array $reports @var string $status */ ?>
<div class="page-head"><div><h1>Laporan produk</h1><p>Laporan daripada pembeli tentang produk yang bermasalah.</p></div></div>
<nav class="tabs" aria-label="Status laporan">
  <?php foreach (['open' => 'Terbuka', 'resolved' => 'Selesai', 'dismissed' => 'Ditolak'] as $k => $label): ?><a href="<?= e(url('/admin/reports', ['status' => $k])) ?>" class="<?= $status === $k ? 'is-active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
</nav>
<?php if (!$reports): ?>
  <?= view_partial('empty', ['icon' => 'flag', 'title' => $status === 'open' ? 'Tiada laporan terbuka' : 'Tiada laporan', 'compact' => true]) ?>
<?php else: ?>
  <div class="stack">
    <?php foreach ($reports as $r): ?>
      <article class="panel">
        <div class="panel__head">
          <div><h2 style="font-size:1.05rem;margin:0"><a href="<?= e(url('/admin/products/' . $r['pid'])) ?>"><?= e($r['product_name']) ?></a></h2><span class="small muted"><?= e($r['shop_name']) ?> · produk <?= badge($r['approval_status']) ?></span></div>
          <span class="tag"><?= e(report_reason_label($r['reason'])) ?></span>
        </div>
        <?php if ($r['details']): ?><p style="white-space:pre-line"><?= e($r['details']) ?></p><?php endif; ?>
        <p class="small muted">Dilaporkan oleh <?= e($r['reporter']) ?> · <?= e(format_date($r['created_at'])) ?></p>
        <?php if ($r['status'] === 'open'): ?>
          <form method="post" action="<?= e(url('/admin/reports/' . $r['id'])) ?>" class="form" style="max-width:560px">
            <?= csrf_field() ?>
            <div class="field"><label for="rn-<?= (int) $r['id'] ?>">Nota penyelesaian</label><input class="input" id="rn-<?= (int) $r['id'] ?>" name="note" required maxlength="255" placeholder="Contoh: Produk digantung; penjual dimaklumkan"></div>
            <div class="btn-row">
              <button class="btn btn--primary btn--sm" type="submit" name="status" value="resolved"><?= icon('check', 15) ?> Tandakan selesai</button>
              <button class="btn btn--sm" type="submit" name="status" value="dismissed">Tolak laporan</button>
              <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/products/' . $r['pid'])) ?>">Moderasi produk</a>
            </div>
          </form>
        <?php else: ?>
          <p class="small mb-0"><?= badge($r['status']) ?> <?= e((string) $r['resolution_note']) ?> — <?= e((string) $r['resolver']) ?>, <?= e(format_date($r['resolved_at'])) ?></p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

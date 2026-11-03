<?php /** @var array $product @var array $images @var array $reports @var array $logs */ use App\Services\AuditLog; ?>
<nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/admin/products')) ?>">Moderasi Produk</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($product['name']) ?></span></nav>
<div class="page-head">
  <div><h1><?= e($product['name']) ?></h1><p class="cluster"><?= e($product['shop_name']) ?> · <?= e($product['category_name']) ?> <?= badge($product['approval_status']) ?> <?= $product['archived_at'] ? badge('archived', 'Diarkib oleh penjual') : '' ?></p></div>
</div>
<div class="split">
  <div class="stack">
    <section class="panel">
      <?php if ($images): ?>
        <div class="file-previews mb-4"><?php foreach ($images as $img): ?><img src="<?= e(upload_url($img['path'])) ?>" alt="Gambar produk" style="width:160px;height:120px"><?php endforeach; ?></div>
      <?php else: ?><p class="muted">Tiada gambar.</p><?php endif; ?>
      <dl class="facts mt-0">
        <dt>Harga</dt><dd><?= e(money($product['price'])) ?></dd>
        <dt>Stok</dt><dd><?= (int) $product['stock'] ?> <?= (int) $product['is_available'] ? '' : '(tidak dijual)' ?></dd>
        <dt>SKU</dt><dd><?= e((string) ($product['sku'] ?? '—')) ?></dd>
        <dt>Dikemas kini</dt><dd><?= e(format_date($product['updated_at'])) ?></dd>
      </dl>
      <h2 class="mt-5" style="font-size:1rem">Penerangan</h2>
      <p class="description mb-0"><?= e($product['description']) ?></p>
    </section>
    <section class="panel">
      <h2>Laporan pembeli</h2>
      <?php if ($reports): ?>
        <ul class="list-plain">
          <?php foreach ($reports as $r): ?>
            <li class="list-row" style="align-items:flex-start"><span><strong><?= e(report_reason_label($r['reason'])) ?></strong> — <?= e((string) $r['details']) ?><br><small class="muted"><?= e($r['reporter']) ?> · <?= e(format_date($r['created_at'])) ?></small></span><?= badge($r['status']) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn btn--sm mt-3" href="<?= e(url('/admin/reports')) ?>">Urus laporan</a>
      <?php else: ?><p class="muted mb-0">Tiada laporan.</p><?php endif; ?>
    </section>
  </div>
  <aside class="stack">
    <section class="panel">
      <h2>Keputusan moderasi</h2>
      <?php if ($product['moderation_note']): ?><p class="small">Nota semasa: <?= e($product['moderation_note']) ?></p><?php endif; ?>
      <div class="stack-sm">
        <?php if ($product['approval_status'] !== 'approved'): ?>
          <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/moderate')) ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="approve">
            <button class="btn btn--primary btn--block" type="submit"><?= icon('check', 16) ?> Luluskan</button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/moderate')) ?>" class="form" data-confirm="Teruskan dengan tindakan ini? Penjual akan dimaklumkan." data-confirm-tone="danger" data-confirm-ok="Sahkan">
          <?= csrf_field() ?>
          <div class="field">
            <label for="action">Tindakan</label>
            <select class="select" id="action" name="action">
              <?php if ($product['approval_status'] !== 'rejected'): ?><option value="reject">Tolak (penjual boleh edit dan hantar semula)</option><?php endif; ?>
              <?php if ($product['approval_status'] !== 'suspended'): ?><option value="suspend">Gantung (disembunyikan sehingga diluluskan semula)</option><?php endif; ?>
            </select>
          </div>
          <div class="field"><label for="note">Sebab (dihantar kepada penjual)</label><textarea class="textarea" id="note" name="note" required maxlength="255" style="min-height:80px"></textarea></div>
          <button class="btn btn--danger btn--block" type="submit">Hantar keputusan</button>
        </form>
      </div>
    </section>
    <section class="panel">
      <h2>Sejarah moderasi</h2>
      <?php if ($logs): ?>
        <ul class="history"><?php foreach ($logs as $l): $d = json_decode((string) $l['details'], true) ?: []; ?><li><div><?= e(AuditLog::describe($l['action'])) ?><?= !empty($d['note']) ? ' — ' . e($d['note']) : '' ?><small><?= e($l['actor_name'] ?? 'Sistem') ?> · <?= e(format_date($l['created_at'])) ?></small></div></li><?php endforeach; ?></ul>
      <?php else: ?><p class="muted mb-0">Belum ada keputusan direkodkan.</p><?php endif; ?>
    </section>
  </aside>
</div>

<?php /** @var array $shops @var string $status @var array $counts */ ?>
<div class="page-head"><div><h1>Permohonan &amp; Kedai</h1><p>Luluskan permohonan baharu dan urus status kedai.</p></div></div>
<nav class="tabs" aria-label="Status kedai">
  <?php foreach (['pending' => 'Menunggu semakan', 'approved' => 'Aktif', 'rejected' => 'Ditolak', 'suspended' => 'Digantung'] as $k => $label): ?>
    <a href="<?= e(url('/admin/sellers', ['status' => $k])) ?>" class="<?= $status === $k ? 'is-active' : '' ?>"><?= e($label) ?> <span class="tab-count"><?= (int) ($counts[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>

<?php if (!$shops): ?>
  <?= view_partial('empty', ['icon' => 'store', 'title' => $status === 'pending' ? 'Tiada permohonan baharu' : 'Tiada kedai dalam senarai ini', 'compact' => true]) ?>
<?php else: ?>
  <div class="stack">
    <?php foreach ($shops as $s): ?>
      <article class="panel">
        <div class="cluster" style="align-items:flex-start;flex-wrap:nowrap">
          <?php if ($s['logo_path']): ?><img class="shop-logo" src="<?= e(upload_url($s['logo_path'])) ?>" alt=""><?php else: ?><span class="shop-logo"><?= e(mb_strtoupper(mb_substr($s['shop_name'], 0, 1))) ?></span><?php endif; ?>
          <div style="flex:1;min-width:0">
            <div class="cluster"><h2 style="margin:0;font-size:1.1rem"><?= e($s['shop_name']) ?></h2><?= badge($s['status']) ?></div>
            <p class="small muted mb-2">Pemilik: <a href="<?= e(url('/admin/users/' . $s['user_id'])) ?>"><?= e($s['owner_name']) ?></a> · <?= e($s['owner_email']) ?> · <?= (int) $s['product_count'] ?> produk</p>
            <p class="mb-2" style="white-space:pre-line"><?= e($s['description']) ?></p>
            <p class="small mb-0"><?= icon('pin', 13) ?> <?= e($s['collection_location']) ?> · <?= icon('phone', 13) ?> <?= e($s['phone']) ?><?= $s['collection_hours'] ? ' · ' . icon('clock', 13) . ' ' . e($s['collection_hours']) : '' ?></p>
            <?php if ($s['review_note']): ?><p class="small text-danger mt-2 mb-0">Nota: <?= e($s['review_note']) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="btn-row mt-4">
          <?php if ($s['status'] === 'pending'): ?>
            <form method="post" action="<?= e(url('/admin/sellers/' . $s['id'] . '/decision')) ?>" data-confirm="Luluskan “<?= e($s['shop_name']) ?>”? Akaun pemilik akan menjadi penjual." data-confirm-ok="Luluskan">
              <?= csrf_field() ?><input type="hidden" name="action" value="approve">
              <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Luluskan</button>
            </form>
          <?php elseif ($s['status'] === 'suspended'): ?>
            <form method="post" action="<?= e(url('/admin/sellers/' . $s['id'] . '/decision')) ?>">
              <?= csrf_field() ?><input type="hidden" name="action" value="reactivate">
              <button class="btn btn--primary" type="submit"><?= icon('refresh', 16) ?> Aktifkan semula</button>
            </form>
          <?php elseif ($s['status'] === 'approved'): ?>
            <a class="btn" href="<?= e(url('/shop/' . $s['slug'])) ?>"><?= icon('external', 16) ?> Lihat kedai</a>
            <a class="btn" href="<?= e(url('/admin/products', ['seller' => $s['id']])) ?>"><?= icon('package', 16) ?> Produk</a>
          <?php endif; ?>
          <?php if (in_array($s['status'], ['pending', 'approved'], true)): $act = $s['status'] === 'pending' ? 'reject' : 'suspend'; ?>
            <details class="reveal" style="flex-basis:100%">
              <summary class="btn btn--danger"><?= icon('x', 16) ?> <?= $act === 'reject' ? 'Tolak permohonan' : 'Gantung kedai' ?></summary>
              <form method="post" action="<?= e(url('/admin/sellers/' . $s['id'] . '/decision')) ?>" class="form" style="max-width:520px" data-confirm="<?= $act === 'reject' ? 'Tolak permohonan ini?' : 'Gantung kedai ini? Semua produknya akan disembunyikan daripada pembeli.' ?>" data-confirm-tone="danger" data-confirm-ok="Sahkan">
                <?= csrf_field() ?><input type="hidden" name="action" value="<?= $act ?>">
                <div class="field"><label for="note-<?= (int) $s['id'] ?>">Sebab (dihantar kepada pemilik)</label><textarea class="textarea" id="note-<?= (int) $s['id'] ?>" name="note" required maxlength="255" style="min-height:80px"></textarea></div>
                <button class="btn btn--danger-solid" type="submit">Hantar</button>
              </form>
            </details>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

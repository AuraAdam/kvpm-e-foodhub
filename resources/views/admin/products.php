<?php /** @var array $products @var array $pagination @var string $status @var array $sellers @var array $categories */ ?>
<div class="page-head"><div><h1>Moderasi Produk</h1><p><?= (int) $pagination['total'] ?> produk · produk menunggu dipaparkan dahulu</p></div></div>
<nav class="tabs" aria-label="Status produk">
  <?php foreach (['' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Diluluskan', 'rejected' => 'Ditolak', 'suspended' => 'Digantung', 'out_of_stock' => 'Stok habis', 'archived' => 'Diarkib'] as $k => $label): ?>
    <a href="<?= e(url('/admin/products', ['status' => $k])) ?>" class="<?= $status === $k ? 'is-active' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
<form method="get" action="<?= e(url('/admin/products')) ?>" class="cluster mb-4" role="search">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <label class="sr-only" for="apq">Cari</label>
  <div class="search-field" style="flex:1;min-width:200px"><?= icon('search', 18) ?><input id="apq" type="search" name="q" value="<?= e(query('q')) ?>" placeholder="Nama produk"></div>
  <label class="sr-only" for="aps">Kedai</label>
  <select class="select" id="aps" name="seller" style="width:auto"><option value="">Semua kedai</option><?php foreach ($sellers as $s): ?><option value="<?= (int) $s['id'] ?>" <?= query_int('seller') === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['shop_name']) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="apc">Kategori</label>
  <select class="select" id="apc" name="category" style="width:auto"><option value="">Semua kategori</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= query_int('category') === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <button class="btn btn--dark" type="submit">Tapis</button>
</form>
<?php if (!$products): ?>
  <?= view_partial('empty', ['icon' => 'package', 'title' => $status === 'pending' ? 'Tiada produk menunggu kelulusan' : 'Tiada produk sepadan', 'compact' => true]) ?>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <thead><tr><th>Produk</th><th>Kedai</th><th class="num">Harga</th><th>Stok</th><th>Status</th><th><span class="sr-only">Tindakan</span></th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td class="cell-main"><div class="cluster" style="flex-wrap:nowrap"><img class="table-thumb" src="<?= e(upload_url($p['image_path'])) ?>" alt="" width="44" height="44"><div><a class="table-title" href="<?= e(url('/admin/products/' . $p['id'])) ?>"><?= e($p['name']) ?></a><div class="table-sub"><?= e($p['category_name']) ?><?= (int) $p['open_reports'] ? ' · <span class="text-danger">' . (int) $p['open_reports'] . ' laporan</span>' : '' ?></div></div></div></td>
            <td data-label="Kedai"><?= e($p['shop_name']) ?></td>
            <td data-label="Harga" class="num"><?= e(money($p['price'])) ?></td>
            <td data-label="Stok"><?= (int) $p['stock'] ?><?= (int) $p['is_available'] ? '' : ' <span class="tag">tidak dijual</span>' ?></td>
            <td data-label="Status"><?= $p['archived_at'] ? badge('archived', 'Diarkib') : badge($p['approval_status']) ?></td>
            <td data-label="">
              <div class="btn-row" style="flex-wrap:nowrap">
                <?php if ($p['approval_status'] === 'pending' && !$p['archived_at']): ?>
                  <form method="post" action="<?= e(url('/admin/products/' . $p['id'] . '/moderate')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="return" value="queue">
                    <button class="btn btn--primary btn--sm" type="submit"><?= icon('check', 15) ?> Luluskan</button>
                  </form>
                <?php endif; ?>
                <a class="btn btn--sm" href="<?= e(url('/admin/products/' . $p['id'])) ?>">Semak</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

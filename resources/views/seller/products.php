<?php /** @var array $products @var array $pagination @var array $filters @var string $filter @var array $categories @var int $threshold */ ?>
<div class="page-head">
  <div><h1>Pengurusan Produk</h1><p><?= (int) $pagination['total'] ?> produk<?= $filter !== '' ? ' · ' . e($filters[$filter]) : '' ?></p></div>
  <a class="btn btn--primary" href="<?= e(url('/seller/products/create')) ?>"><?= icon('plus', 16) ?> Tambah produk</a>
</div>

<nav class="tabs" aria-label="Tapis produk">
  <a href="<?= e(url('/seller/products')) ?>" class="<?= $filter === '' ? 'is-active' : '' ?>">Semua</a>
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e(url('/seller/products', ['availability' => $key])) ?>" class="<?= $filter === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url('/seller/products')) ?>" class="cluster mb-4" role="search">
  <input type="hidden" name="availability" value="<?= e($filter) ?>">
  <label class="sr-only" for="pq">Cari produk</label>
  <div class="search-field" style="flex:1;min-width:200px"><?= icon('search', 18) ?><input id="pq" type="search" name="q" value="<?= e(query('q')) ?>" placeholder="Cari nama atau SKU"></div>
  <label class="sr-only" for="pcat">Kategori</label>
  <select class="select" id="pcat" name="category" style="width:auto">
    <option value="">Semua kategori</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= query_int('category') === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn--dark" type="submit">Cari</button>
</form>

<?php if (!$products): ?>
  <?= view_partial('empty', ['icon' => 'package', 'title' => $filter === '' && query('q') === '' ? 'Belum ada produk' : 'Tiada produk sepadan', 'text' => 'Tambah produk pertama anda dengan nama, harga, stok dan gambar.', 'actionUrl' => '/seller/products/create', 'actionLabel' => 'Tambah produk']) ?>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <thead><tr><th>Produk</th><th class="num">Harga</th><th>Stok</th><th>Kelulusan</th><th>Jualan</th><th><span class="sr-only">Tindakan</span></th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): $archived = $p['archived_at'] !== null; ?>
          <tr>
            <td class="cell-main">
              <div class="cluster" style="flex-wrap:nowrap">
                <img class="table-thumb" src="<?= e(upload_url($p['image_path'])) ?>" alt="" width="44" height="44">
                <div><a class="table-title" href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>"><?= e($p['name']) ?></a><div class="table-sub"><?= e($p['category_name']) ?><?= $p['sku'] ? ' · ' . e($p['sku']) : '' ?></div></div>
              </div>
            </td>
            <td data-label="Harga" class="num strong"><?= e(money($p['price'])) ?></td>
            <td data-label="Stok">
              <form method="post" action="<?= e(url('/seller/products/' . $p['id'] . '/stock')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <label class="sr-only" for="stock-<?= (int) $p['id'] ?>">Stok <?= e($p['name']) ?></label>
                <input class="input<?= (int) $p['stock'] <= $threshold ? ' is-invalid' : '' ?>" id="stock-<?= (int) $p['id'] ?>" type="number" name="stock" min="0" max="999999" value="<?= (int) $p['stock'] ?>" inputmode="numeric">
                <button class="btn btn--sm" type="submit">Simpan</button>
              </form>
            </td>
            <td data-label="Kelulusan"><?= $archived ? badge('archived', 'Diarkib') : badge($p['approval_status']) ?></td>
            <td data-label="Jualan">
              <?php if (!$archived): ?>
                <form method="post" action="<?= e(url('/seller/products/' . $p['id'] . '/availability')) ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm <?= (int) $p['is_available'] ? '' : 'btn--ghost' ?>" type="submit" aria-label="<?= (int) $p['is_available'] ? 'Tandakan tidak dijual' : 'Tandakan dijual' ?>">
                    <?= icon((int) $p['is_available'] ? 'eye' : 'eye-off', 15) ?> <?= (int) $p['is_available'] ? 'Dijual' : 'Disembunyi' ?>
                  </button>
                </form>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td data-label="">
              <div class="btn-row" style="flex-wrap:nowrap">
                <a class="btn btn--sm" href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>"><?= icon('edit', 15) ?> Edit</a>
                <form method="post" action="<?= e(url('/seller/products/' . $p['id'] . '/archive')) ?>"
                      data-confirm="<?= $archived ? 'Pulihkan produk ini?' : 'Arkibkan “' . e($p['name']) . '”? Produk tidak lagi dipaparkan kepada pembeli, tetapi kekal dalam rekod pesanan lama.' ?>"
                      data-confirm-ok="<?= $archived ? 'Pulihkan' : 'Arkibkan' ?>" <?= $archived ? '' : 'data-confirm-tone="danger"' ?>>
                  <?= csrf_field() ?>
                  <button class="btn btn--ghost btn--sm" type="submit" aria-label="<?= $archived ? 'Pulihkan' : 'Arkibkan' ?> <?= e($p['name']) ?>"><?= icon($archived ? 'refresh' : 'archive', 15) ?></button>
                </form>
              </div>
            </td>
          </tr>
          <?php if ($p['moderation_note'] && in_array($p['approval_status'], ['rejected', 'suspended'], true)): ?>
            <tr><td colspan="6" data-label="" class="small text-danger" style="padding-top:0"><?= icon('alert', 13) ?> Nota pentadbir: <?= e($p['moderation_note']) ?></td></tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

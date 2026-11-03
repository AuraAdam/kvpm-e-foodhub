<?php /** @var array $users @var array $pagination */ ?>
<div class="page-head"><div><h1>Pengurusan Pengguna</h1><p><?= (int) $pagination['total'] ?> akaun</p></div></div>
<form method="get" action="<?= e(url('/admin/users')) ?>" class="cluster mb-4" role="search">
  <label class="sr-only" for="uq">Cari</label>
  <div class="search-field" style="flex:1;min-width:220px"><?= icon('search', 18) ?><input id="uq" type="search" name="q" value="<?= e(query('q')) ?>" placeholder="Nama, e-mel atau telefon"></div>
  <label class="sr-only" for="urole">Peranan</label>
  <select class="select" id="urole" name="role" style="width:auto">
    <option value="">Semua peranan</option>
    <?php foreach (['customer', 'seller', 'admin'] as $r): ?><option value="<?= $r ?>" <?= query('role') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="ustatus">Status</label>
  <select class="select" id="ustatus" name="status" style="width:auto">
    <option value="">Semua status</option>
    <option value="active" <?= query('status') === 'active' ? 'selected' : '' ?>>Aktif</option>
    <option value="suspended" <?= query('status') === 'suspended' ? 'selected' : '' ?>>Digantung</option>
  </select>
  <button class="btn btn--dark" type="submit">Tapis</button>
</form>
<?php if (!$users): ?>
  <?= view_partial('empty', ['icon' => 'users', 'title' => 'Tiada pengguna sepadan', 'compact' => true]) ?>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <thead><tr><th>Pengguna</th><th>Peranan</th><th>Kedai</th><th>Status</th><th>Daftar</th><th><span class="sr-only">Tindakan</span></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="cell-main"><a class="table-title" href="<?= e(url('/admin/users/' . $u['id'])) ?>"><?= e($u['name']) ?></a><div class="table-sub"><?= e($u['email']) ?></div></td>
            <td data-label="Peranan"><?= e(role_label($u['role'])) ?></td>
            <td data-label="Kedai"><?= $u['shop_name'] ? e($u['shop_name']) . ' ' . badge($u['shop_status']) : '—' ?></td>
            <td data-label="Status"><?= badge($u['status']) ?></td>
            <td data-label="Daftar" class="small"><?= e(format_date($u['created_at'], false)) ?></td>
            <td data-label=""><a class="btn btn--sm" href="<?= e(url('/admin/users/' . $u['id'])) ?>">Urus</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

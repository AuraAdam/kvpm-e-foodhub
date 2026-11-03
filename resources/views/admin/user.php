<?php /** @var array $user @var ?array $shop @var array $orders @var array $logs @var bool $isSelf */ use App\Services\AuditLog; ?>
<nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/admin/users')) ?>">Pengguna</a><?= icon('chevron-right', 14) ?><span aria-current="page"><?= e($user['name']) ?></span></nav>
<div class="page-head">
  <div><h1><?= e($user['name']) ?></h1><p class="cluster"><?= e(role_label($user['role'])) ?> <?= badge($user['status']) ?> <?= $isSelf ? '<span class="tag">Akaun anda</span>' : '' ?></p></div>
</div>

<div class="split">
  <div class="stack">
    <section class="panel">
      <h2>Butiran</h2>
      <dl class="facts mt-0">
        <dt>E-mel</dt><dd><?= e($user['email']) ?></dd>
        <dt>Telefon</dt><dd><?= e((string) $user['phone']) ?></dd>
        <dt>Didaftarkan</dt><dd><?= e(format_date($user['created_at'])) ?></dd>
        <dt>Log masuk terakhir</dt><dd><?= e(format_date($user['last_login_at'])) ?></dd>
        <?php if ((int) $user['must_change_password']): ?><dt>Kata laluan</dt><dd><?= badge('pending', 'Sementara — perlu ditukar') ?></dd><?php endif; ?>
        <?php if ($shop): ?><dt>Kedai</dt><dd><?= e($shop['shop_name']) ?> <?= badge($shop['status']) ?> <a class="small" href="<?= e(url('/admin/sellers', ['status' => $shop['status']])) ?>">Urus kedai</a></dd><?php endif; ?>
      </dl>
    </section>

    <section class="panel">
      <h2>Pesanan terkini</h2>
      <?php if ($orders): ?>
        <ul class="list-plain">
          <?php foreach ($orders as $o): ?>
            <li class="list-row"><a href="<?= e(url('/admin/orders/' . $o['order_number'])) ?>"><?= e($o['order_number']) ?></a><span class="small muted"><?= e(format_date($o['created_at'], false)) ?></span><strong class="num"><?= e(money($o['total'])) ?></strong></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?><p class="muted mb-0">Tiada pesanan.</p><?php endif; ?>
    </section>

    <section class="panel">
      <h2>Sejarah tindakan</h2>
      <?php if ($logs): ?>
        <ul class="history">
          <?php foreach ($logs as $l): $d = json_decode((string) $l['details'], true) ?: []; ?>
            <li><div><?= e(AuditLog::describe($l['action'])) ?><?= !empty($d['reason']) ? ' — ' . e($d['reason']) : '' ?><small><?= e($l['actor_name'] ?? 'Sistem') ?> · <?= e(format_date($l['created_at'])) ?></small></div></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?><p class="muted mb-0">Tiada tindakan pentadbir direkodkan untuk akaun ini.</p><?php endif; ?>
    </section>
  </div>

  <aside class="stack">
    <?php if (!$isSelf): ?>
      <section class="panel">
        <h2>Status akaun</h2>
        <?php if ($user['status'] === 'active'): ?>
          <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/status')) ?>" class="form" data-confirm="Gantung akaun <?= e($user['name']) ?>? Pengguna ini tidak akan boleh log masuk." data-confirm-ok="Gantung akaun" data-confirm-tone="danger">
            <?= csrf_field() ?><input type="hidden" name="status" value="suspended">
            <div class="field"><label for="reason">Sebab penggantungan</label><textarea class="textarea" id="reason" name="reason" required maxlength="255" style="min-height:80px"></textarea></div>
            <button class="btn btn--danger btn--block" type="submit"><?= icon('lock', 16) ?> Gantung akaun</button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/status')) ?>">
            <?= csrf_field() ?><input type="hidden" name="status" value="active">
            <button class="btn btn--primary btn--block" type="submit"><?= icon('check', 16) ?> Aktifkan semula</button>
          </form>
        <?php endif; ?>
      </section>

      <section class="panel">
        <h2>Kata laluan</h2>
        <p class="small muted">Untuk pengguna yang terlupa kata laluan. Sahkan identiti mereka dahulu. Kata laluan sementara dipaparkan sekali sahaja.</p>
        <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/reset-password')) ?>" data-confirm="Tetapkan kata laluan sementara untuk <?= e($user['email']) ?>? Kata laluan lama tidak lagi berfungsi." data-confirm-ok="Tetapkan">
          <?= csrf_field() ?>
          <button class="btn btn--block" type="submit"><?= icon('refresh', 16) ?> Tetapkan kata laluan sementara</button>
        </form>
      </section>

      <?php if (in_array($user['role'], ['customer', 'admin'], true)): ?>
        <section class="panel">
          <h2>Peranan pentadbir</h2>
          <p class="small muted"><?= $user['role'] === 'admin' ? 'Turunkan pentadbir ini kepada pelanggan biasa.' : 'Beri akses penuh pentadbir. Gunakan dengan berhati-hati.' ?> Masukkan kata laluan anda untuk mengesahkan.</p>
          <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/role')) ?>" class="form" data-confirm="<?= $user['role'] === 'admin' ? 'Turunkan peranan pentadbir ini?' : 'Jadikan ' . e($user['name']) . ' pentadbir? Mereka akan mempunyai akses penuh.' ?>" data-confirm-tone="danger" data-confirm-ok="Sahkan">
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="<?= $user['role'] === 'admin' ? 'demote' : 'admin' ?>">
            <div class="field"><label for="confirm_password">Kata laluan anda</label><input class="input" id="confirm_password" type="password" name="confirm_password" required autocomplete="current-password"></div>
            <button class="btn btn--danger btn--block" type="submit"><?= icon('shield', 16) ?> <?= $user['role'] === 'admin' ? 'Turunkan kepada pelanggan' : 'Jadikan pentadbir' ?></button>
          </form>
        </section>
      <?php endif; ?>
    <?php else: ?>
      <div class="alert alert--info"><?= icon('info', 18) ?><div class="alert__body">Anda tidak boleh menggantung, menurunkan peranan atau menetapkan semula kata laluan akaun anda sendiri dari sini. Ini mengelakkan anda terkunci di luar sistem.</div></div>
    <?php endif; ?>
  </aside>
</div>

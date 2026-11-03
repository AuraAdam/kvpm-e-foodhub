<?php /** @var array $logs @var array $pagination @var string $entity @var array $entities */ use App\Services\AuditLog; ?>
<div class="page-head">
  <div><h1>Log audit</h1><p>Rekod tindakan penting. Kata laluan dan token tidak pernah disimpan di sini.</p></div>
  <form method="get" action="<?= e(url('/admin/audit')) ?>" class="cluster">
    <label class="sr-only" for="entity">Jenis rekod</label>
    <select class="select" id="entity" name="entity" style="width:auto" data-autosubmit>
      <option value="">Semua jenis</option>
      <?php foreach ($entities as $en): ?><option value="<?= e($en) ?>" <?= $entity === $en ? 'selected' : '' ?>><?= e($en) ?></option><?php endforeach; ?>
    </select>
    <noscript><button class="btn btn--sm" type="submit">Tapis</button></noscript>
  </form>
</div>
<?php if (!$logs): ?>
  <?= view_partial('empty', ['icon' => 'history', 'title' => 'Tiada rekod', 'text' => 'Tindakan seperti kelulusan, penggantungan dan perubahan status akan direkodkan di sini.', 'compact' => true]) ?>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <thead><tr><th>Masa</th><th>Pelaku</th><th>Tindakan</th><th>Rekod</th><th>Butiran</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): $details = $l['details'] ? json_decode($l['details'], true) : []; ?>
          <tr>
            <td class="cell-main nowrap"><?= e(format_date($l['created_at'])) ?></td>
            <td data-label="Pelaku"><?= e($l['actor_name'] ?? 'Sistem') ?></td>
            <td data-label="Tindakan"><?= e(AuditLog::describe($l['action'])) ?></td>
            <td data-label="Rekod"><span class="tag"><?= e($l['entity_type']) ?><?= $l['entity_id'] ? ' #' . (int) $l['entity_id'] : '' ?></span></td>
            <td data-label="Butiran" class="small" style="max-width:340px">
              <?php foreach ((array) $details as $k => $v): ?><div><span class="muted"><?= e($k) ?>:</span> <?= e(is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v) ?></div><?php endforeach; ?>
            </td>
            <td data-label="IP" class="small muted"><?= e($l['ip_address']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

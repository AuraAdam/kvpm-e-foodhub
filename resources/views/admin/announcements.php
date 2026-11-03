<?php /** @var array $announcements @var ?array $editing */
$dt = fn (?string $v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
?>
<div class="page-head"><div><h1>Pengumuman</h1><p>Pengumuman aktif dipaparkan di Laman Utama (maksimum 3).</p></div></div>
<div class="split split--wide-side">
  <section>
    <?php if (!$announcements): ?>
      <?= view_partial('empty', ['icon' => 'megaphone', 'title' => 'Tiada pengumuman', 'compact' => true]) ?>
    <?php else: ?>
      <div class="stack">
        <?php foreach ($announcements as $a): ?>
          <article class="panel panel--tight">
            <div class="panel__head mb-2"><h2 style="font-size:1rem;margin:0"><?= e($a['title']) ?></h2><?= (int) $a['is_active'] ? badge('active') : badge('hidden', 'Tidak aktif') ?></div>
            <p class="small"><?= e($a['body']) ?></p>
            <p class="small muted"><?= $a['starts_at'] ? 'Mula ' . e(format_date($a['starts_at'])) : 'Mula serta-merta' ?> · <?= $a['ends_at'] ? 'Tamat ' . e(format_date($a['ends_at'])) : 'Tiada tarikh tamat' ?></p>
            <div class="btn-row">
              <a class="btn btn--sm" href="<?= e(url('/admin/announcements', ['edit' => $a['id']])) ?>"><?= icon('edit', 15) ?> Edit</a>
              <form method="post" action="<?= e(url('/admin/announcements/' . $a['id'] . '/delete')) ?>" data-confirm="Padam pengumuman ini?" data-confirm-ok="Padam" data-confirm-tone="danger"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('trash', 15) ?> Padam</button></form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <aside class="panel sticky">
    <h2><?= $editing ? 'Edit pengumuman' : 'Pengumuman baharu' ?></h2>
    <?= view_partial('validation_summary') ?>
    <form method="post" action="<?= e(url('/admin/announcements')) ?>" class="form mt-4" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?: '' ?>">
      <div class="field"><label for="atitle">Tajuk</label><input class="input" id="atitle" name="title" value="<?= e(old('title', $editing['title'] ?? '')) ?>" required maxlength="150"><?= view_partial('field_error', ['name' => 'title']) ?></div>
      <div class="field"><label for="abody">Kandungan</label><textarea class="textarea" id="abody" name="body" required maxlength="500"><?= e(old('body', $editing['body'] ?? '')) ?></textarea><?= view_partial('field_error', ['name' => 'body']) ?></div>
      <div class="form-grid form-grid--2">
        <div class="field"><label for="astart">Mula (pilihan)</label><input class="input" id="astart" type="datetime-local" name="starts_at" value="<?= e(old('starts_at', $dt($editing['starts_at'] ?? null))) ?>"><?= view_partial('field_error', ['name' => 'starts_at']) ?></div>
        <div class="field"><label for="aend">Tamat (pilihan)</label><input class="input" id="aend" type="datetime-local" name="ends_at" value="<?= e(old('ends_at', $dt($editing['ends_at'] ?? null))) ?>"><?= view_partial('field_error', ['name' => 'ends_at']) ?></div>
      </div>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= ($editing ? (int) $editing['is_active'] : 1) ? 'checked' : '' ?>> Aktif</label>
      <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan</button><?php if ($editing): ?><a class="btn btn--ghost" href="<?= e(url('/admin/announcements')) ?>">Batal</a><?php endif; ?></div>
    </form>
  </aside>
</div>

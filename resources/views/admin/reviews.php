<?php /** @var array $reviews @var array $pagination @var string $status */ ?>
<div class="page-head"><div><h1>Ulasan</h1><p>Sembunyikan ulasan yang kasar, palsu atau tidak berkaitan.</p></div></div>
<nav class="tabs" aria-label="Status ulasan">
  <?php foreach (['' => 'Semua', 'published' => 'Diterbitkan', 'hidden' => 'Disembunyikan'] as $k => $label): ?><a href="<?= e(url('/admin/reviews', ['status' => $k])) ?>" class="<?= $status === $k ? 'is-active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
</nav>
<?php if (!$reviews): ?>
  <?= view_partial('empty', ['icon' => 'star', 'title' => 'Tiada ulasan', 'text' => 'Ulasan muncul selepas pembeli mengulas pesanan yang telah selesai.', 'compact' => true]) ?>
<?php else: ?>
  <div class="panel">
    <?php foreach ($reviews as $r): ?>
      <article class="review">
        <div class="review__head">
          <span><strong><?= e($r['reviewer']) ?></strong> tentang <a href="<?= e(url('/product/' . $r['product_slug'])) ?>"><?= e($r['product_name']) ?></a></span>
          <span class="cluster small muted"><?= stars((float) $r['rating'], 13) ?> <?= e(format_date($r['created_at'], false)) ?> <?= badge($r['status']) ?></span>
        </div>
        <?php if ($r['comment']): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
        <?php if ($r['moderation_note']): ?><p class="small muted mt-2">Nota moderasi: <?= e($r['moderation_note']) ?></p><?php endif; ?>
        <form method="post" action="<?= e(url('/admin/reviews/' . $r['id'])) ?>" class="cluster mt-3">
          <?= csrf_field() ?>
          <?php if ($r['status'] === 'published'): ?>
            <label class="sr-only" for="rn-<?= (int) $r['id'] ?>">Sebab</label>
            <input class="input" id="rn-<?= (int) $r['id'] ?>" name="note" required maxlength="255" placeholder="Sebab disembunyikan" style="max-width:320px;min-height:36px">
            <button class="btn btn--danger btn--sm" type="submit"><?= icon('eye-off', 15) ?> Sembunyikan</button>
          <?php else: ?>
            <button class="btn btn--sm" type="submit"><?= icon('eye', 15) ?> Terbitkan semula</button>
          <?php endif; ?>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
  <?= view_partial('pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>

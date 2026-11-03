<?php /** @var array $reviews */ ?>
<div class="container page" style="max-width:820px">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/account')) ?>">Akaun saya</a><?= icon('chevron-right', 14) ?><span aria-current="page">Ulasan saya</span></nav>
  <div class="page-head"><div><h1>Ulasan saya</h1><p>Ulasan boleh ditulis dari halaman pesanan selepas pesanan selesai.</p></div></div>
  <?php if (!$reviews): ?>
    <?= view_partial('empty', ['icon' => 'star', 'title' => 'Belum ada ulasan', 'text' => 'Selepas pesanan anda selesai, buka pesanan tersebut dan tekan "Tulis ulasan".', 'actionUrl' => '/orders', 'actionLabel' => 'Lihat pesanan saya']) ?>
  <?php else: ?>
    <div class="panel">
      <?php foreach ($reviews as $r): ?>
        <article class="review">
          <div class="review__head">
            <a class="strong" href="<?= e(url('/product/' . $r['product_slug'])) ?>"><?= e($r['product_name']) ?></a>
            <span class="cluster small muted"><?= stars((float) $r['rating'], 13) ?> <?= e(format_date($r['created_at'], false)) ?> <?= $r['status'] === 'hidden' ? badge('hidden') : '' ?></span>
          </div>
          <?php if ($r['comment']): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
          <?php if ($r['status'] === 'hidden' && $r['moderation_note']): ?><p class="small text-danger mt-2">Disembunyikan oleh pentadbir: <?= e($r['moderation_note']) ?></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

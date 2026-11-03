<?php
/** @var int $status @var string $message */
$headings = [
    400 => 'Permintaan tidak sah',
    403 => 'Akses tidak dibenarkan',
    404 => 'Halaman tidak dijumpai',
    405 => 'Tindakan tidak dibenarkan',
    419 => 'Sesi borang tamat',
    429 => 'Terlalu banyak cubaan',
    500 => 'Berlaku ralat pada pelayan',
];
$icons = [403 => 'lock', 404 => 'search', 419 => 'refresh', 500 => 'alert'];
?>
<section class="container page">
  <div class="empty">
    <div class="empty__icon"><?= icon($icons[$status] ?? 'info', 30) ?></div>
    <p class="muted mb-2">Ralat <?= (int) $status ?></p>
    <h1><?= e($headings[$status] ?? 'Ralat') ?></h1>
    <p style="white-space: pre-line"><?= e($message ?: 'Sila cuba lagi.') ?></p>
    <div class="btn-row" style="justify-content:center">
      <a class="btn btn--primary" href="<?= e(url('/')) ?>">Ke Laman Utama</a>
      <?php if ($status === 403 && !\App\Core\Auth::check()): ?>
        <a class="btn" href="<?= e(url('/login')) ?>">Log masuk</a>
      <?php endif; ?>
    </div>
  </div>
</section>

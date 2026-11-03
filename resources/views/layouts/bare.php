<?php /** Minimal layout used when the database may be unavailable (500 errors). */ ?>
<!doctype html>
<html lang="ms">
<head>
<?php require BASE_PATH . '/resources/views/layouts/head.php'; ?>
</head>
<body>
<main id="main">
  <div class="container" style="padding-top: 32px;"><?= view_partial('brand') ?></div>
  <?= $content ?>
</main>
</body>
</html>

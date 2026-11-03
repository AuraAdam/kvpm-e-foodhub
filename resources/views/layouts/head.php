<?php /** Shared <head>. @var string $title */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — ' : '') ?>E-FoodHub</title>
<meta name="description" content="Pasaran dalam talian untuk makanan, minuman dan barangan daripada penjual kolej vokasional di Malaysia.">
<meta name="theme-color" content="#0A4A33">
<link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script src="<?= e(asset('js/app.js')) ?>" defer></script>

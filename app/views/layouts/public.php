<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'NatConnect Foundation — Nature, Everything') ?></title>
  <meta name="description" content="<?= e($metaDescription ?? 'NatConnect Foundation — Environmental advocacy and conservation platform.') ?>">
  <meta name="theme-color" content="#2F3A8F">

  <!-- Preload Critical Self-Hosted Fonts -->
  <link rel="preload" href="/assets/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/fraunces.woff2" as="font" type="font/woff2" crossorigin>

  <!-- Favicon Slot -->
  <link rel="icon" type="image/png" href="<?= asset('assets/img/favicon.png') ?>">

  <!-- Compiled Tailwind Stylesheet -->
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

  <!-- Self-Hosted Alpine.js -->
  <script defer src="<?= asset('assets/js/alpine.min.js') ?>"></script>
</head>
<body class="bg-surface text-ink min-h-screen flex flex-col antialiased selection:bg-accent/20 selection:text-ink">
  <!-- Accessibility Skip Link -->
  <a 
    href="#main-content" 
    class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 z-50 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-lg shadow-soft transition-colors"
  >
    Skip to main content
  </a>

  <!-- Global Header -->
  <?php \App\Core\View::partial('partials/header'); ?>

  <!-- Main Page Content -->
  <main id="main-content" class="flex-1">
    <?= $content ?? '' ?>
  </main>

  <!-- Global Footer -->
  <?php \App\Core\View::partial('partials/footer'); ?>
</body>
</html>

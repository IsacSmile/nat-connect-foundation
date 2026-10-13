<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <title><?= e($pageTitle ?? 'NatConnect Foundation — Nature, Everything') ?></title>
  <meta name="description" content="<?= e($metaDescription ?? 'NatConnect Foundation — Environmental advocacy, wetland protection, and conservation platform.') ?>">
  <meta name="theme-color" content="#2F3A8F">

  <?php
    $siteName = setting('site_name', 'NatConnect Foundation');
    $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = \App\Core\Auth::isHttps();
    $protocol = $isHttps ? 'https://' : 'http://';
    $rawPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $canonicalUrl = $canonicalUrl ?? ($protocol . $currentHost . $rawPath);
    $ogImage = $ogImage ?? url('assets/img/og-default.png');
    if (!str_starts_with($ogImage, 'http://') && !str_starts_with($ogImage, 'https://')) {
        $ogImage = url($ogImage);
    }
  ?>

  <!-- Canonical Link -->
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">

  <!-- Open Graph Metadata -->
  <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:title" content="<?= e($ogTitle ?? ($pageTitle ?? $siteName)) ?>">
  <meta property="og:description" content="<?= e($ogDescription ?? ($metaDescription ?? '')) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">

  <?php if (!empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>

  <!-- Twitter Card Metadata -->
  <meta name="twitter:card" content="<?= e($twitterCard ?? 'summary_large_image') ?>">
  <meta name="twitter:title" content="<?= e($ogTitle ?? ($pageTitle ?? $siteName)) ?>">
  <meta name="twitter:description" content="<?= e($ogDescription ?? ($metaDescription ?? '')) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">

  <?php if (($ogType ?? 'website') === 'article'): ?>
    <?php if (!empty($articlePublishedTime)): ?>
      <meta property="article:published_time" content="<?= e($articlePublishedTime) ?>">
    <?php endif; ?>
    <?php if (!empty($articleModifiedTime)): ?>
      <meta property="article:modified_time" content="<?= e($articleModifiedTime) ?>">
    <?php endif; ?>
    <?php if (!empty($articleAuthor)): ?>
      <meta property="article:author" content="<?= e($articleAuthor) ?>">
    <?php endif; ?>
  <?php endif; ?>

  <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json">
      <?= json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
  <?php endif; ?>

  <!-- Preload Critical Self-Hosted Fonts -->
  <link rel="preload" href="/assets/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/fraunces.woff2" as="font" type="font/woff2" crossorigin>

  <!-- RSS Feed Alternate Link -->
  <link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> Feed" href="<?= e(url('feed')) ?>">

  <!-- Favicons & Web Manifest -->
  <link rel="icon" href="/favicon.ico" sizes="32x32">
  <link rel="icon" type="image/png" href="<?= asset('assets/img/favicon.png') ?>" sizes="32x32">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <link rel="manifest" href="/site.webmanifest">

  <!-- Compiled Tailwind Stylesheet (v3.4.17) -->
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

  <!-- Self-Hosted Alpine.js -->
  <script defer src="<?= asset('assets/js/alpine.min.js') ?>"></script>

</head>
<body class="bg-surface text-ink min-h-screen flex flex-col antialiased selection:bg-accent/20 selection:text-ink">
  <!-- Accessibility Skip Link -->
  <a 
    href="#main-content" 
    class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 z-30 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors"
  >
    Skip to main content
  </a>

  <?php if (!empty($isPreview)): ?>
    <!-- Fixed Admin Preview Banner -->
    <div class="sticky top-0 inset-x-0 z-30 bg-amber-500 text-amber-950 px-4 py-2 text-xs sm:text-sm font-semibold shadow-md flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-950 animate-pulse"></span>
        <span>Preview: not published (Status: <?= e(ucfirst($post['status'] ?? $page['status'] ?? 'Draft')) ?>)</span>
      </div>
      <?php if (!empty($page['id'])): ?>
        <a href="/admin/pages/<?= (int) $page['id'] ?>/edit" class="px-3 py-1 bg-amber-950 text-white hover:bg-black rounded-lg text-xs font-bold transition-colors">
          Back to Editor
        </a>
      <?php elseif (!empty($post['id'])): ?>
        <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="px-3 py-1 bg-amber-950 text-white hover:bg-black rounded-lg text-xs font-bold transition-colors">
          Back to Editor
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Global Header -->
  <?php \App\Core\View::partial('partials/header'); ?>

  <!-- Main Content Container -->
  <main id="main-content" class="flex-1">
    <?= $content ?? '' ?>
  </main>

  <!-- Global Footer -->
  <?php \App\Core\View::partial('partials/footer'); ?>
</body>
</html>

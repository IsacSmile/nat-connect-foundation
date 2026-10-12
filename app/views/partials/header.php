<!-- NatConnect Foundation — Global Public Header -->
<?php
  $navItems = [
      ['label' => 'Home', 'url' => '/'],
  ];

  // Pages with show_in_nav (ordered, nav_label if set)
  $navPages = \App\Models\PageModel::getNavPages();
  foreach ($navPages as $np) {
      $navItems[] = [
          'label' => !empty($np['nav_label']) ? $np['nav_label'] : $np['title'],
          'url'   => '/' . $np['slug'],
      ];
  }

  // Category links
  $navCategories = \App\Models\CategoryModel::getAll();
  foreach ($navCategories as $nc) {
      $navItems[] = [
          'label' => $nc['name'],
          'url'   => '/category/' . $nc['slug'],
      ];
  }

  // Gallery
  $navItems[] = [
      'label' => 'Gallery',
      'url'   => '/gallery',
  ];

  $isDonateActive = \App\Models\SettingModel::isDonateActive();
  if ($isDonateActive) {
      $navItems[] = [
          'label' => 'Donate',
          'url'   => '/donate',
      ];
  }

  $currentPath = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
?>

<header x-data="{ mobileOpen: false, searchOpen: false }" class="sticky top-0 z-40 bg-surface/95 backdrop-blur border-b border-border">
  <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-20">
      
      <!-- Logo & Brand Identity -->
      <a href="/" class="flex items-center gap-2.5 sm:gap-3 group focus-visible:ring-2 focus-visible:ring-primary rounded-lg p-1 min-w-0">
        <img 
          src="<?= asset('assets/img/logo-mark.png') ?>" 
          alt="NatConnect Foundation Mark" 
          class="h-10 sm:h-11 w-auto object-contain transition-transform duration-200 group-hover:scale-105 shrink-0"
        />
        <div class="flex flex-col min-w-0">
          <span class="font-serif font-bold text-base sm:text-xl md:text-2xl text-primary tracking-tight leading-none group-hover:text-primary-hover transition-colors truncate">
            <?= e(setting('site_name', 'NatConnect Foundation')) ?>
          </span>
          <?php $tagline = setting('site_tagline'); ?>
          <?php if (!empty($tagline)): ?>
            <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-accent-strong mt-0.5 truncate">
              <?= e($tagline) ?>
            </span>
          <?php endif; ?>
        </div>
      </a>

      <!-- Desktop Navigation Menu (from PHP array) -->
      <nav class="hidden nav:flex items-center gap-6" aria-label="Main Navigation">
        <?php foreach ($navItems as $item): ?>
          <?php
            $itemPath = '/' . trim($item['url'], '/');
            $isActive = ($itemPath === '/' && $currentPath === '/') || ($itemPath !== '/' && str_starts_with($currentPath, $itemPath));
          ?>
          <a 
            href="<?= e($item['url']) ?>" 
            class="text-sm transition-colors py-1 whitespace-nowrap <?= $isActive ? 'text-primary font-bold border-b-2 border-primary' : 'text-ink hover:text-primary font-medium' ?>"
          >
            <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <!-- Desktop Actions: Search & Donate CTA -->
      <div class="hidden nav:flex items-center gap-4 shrink-0">
        <!-- Search Button Toggle -->
        <button 
          type="button" 
          @click="searchOpen = !searchOpen" 
          aria-label="Toggle Search Bar"
          class="p-2.5 text-ink-muted hover:text-primary hover:bg-white rounded-xl border border-transparent hover:border-border transition-all focus-visible:ring-2 focus-visible:ring-primary"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
          </svg>
        </button>

        <!-- Donate Button (Shown ONLY if donation fields are populated) -->
        <?php if ($isDonateActive): ?>
          <a 
            href="/donate" 
            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-accent-strong hover:bg-accent-hover rounded-xl shadow-soft transition-all duration-150 focus-visible:ring-2 focus-visible:ring-accent"
          >
            Donate
          </a>
        <?php endif; ?>
      </div>

      <!-- Mobile Buttons (< 1100px) -->
      <div class="flex items-center gap-2 nav:hidden">
        <!-- Search Toggle -->
        <button 
          type="button" 
          @click="searchOpen = !searchOpen" 
          aria-label="Toggle Search"
          class="p-2 text-ink-muted hover:text-primary rounded-xl focus-visible:ring-2 focus-visible:ring-primary"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
          </svg>
        </button>

        <!-- Mobile Drawer Hamburger Toggle -->
        <button 
          type="button" 
          @click="mobileOpen = true" 
          aria-label="Open Navigation Menu" 
          :aria-expanded="mobileOpen"
          class="p-2 text-ink hover:text-primary rounded-xl focus-visible:ring-2 focus-visible:ring-primary"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
            <line x1="4" x2="20" y1="12" y2="12"/>
            <line x1="4" x2="20" y1="6" y2="6"/>
            <line x1="4" x2="20" y1="18" y2="18"/>
          </svg>
        </button>
      </div>

    </div>

    <!-- Collapsible Search Bar (Desktop & Mobile) -->
    <div 
      x-show="searchOpen" 
      x-cloak
      x-transition:enter="transition ease-out duration-150"
      x-transition:enter-start="opacity-0 -translate-y-2"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-100"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      class="py-3 border-t border-border"
    >
      <form action="/search" method="GET" class="relative max-w-xl mx-auto">
        <input 
          type="search" 
          name="q" 
          placeholder="Search posts..." 
          class="w-full pl-11 pr-4 py-2.5 bg-white border border-border rounded-xl text-sm text-ink placeholder-ink-subtle focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all shadow-soft-sm"
        />
        <svg class="w-5 h-5 absolute left-3.5 top-3 text-ink-subtle pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
        </svg>
      </form>
    </div>

  </div>

  <!-- Mobile Navigation Drawer via Alpine.js -->
  <div 
    x-show="mobileOpen" 
    x-cloak
    class="fixed inset-0 z-50 nav:hidden" 
    role="dialog" 
    aria-modal="true" 
    aria-label="Mobile Navigation"
  >
    <!-- Backdrop Overlay -->
    <div 
      x-show="mobileOpen"
      x-transition:enter="transition-opacity ease-linear duration-200"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition-opacity ease-linear duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      @click="mobileOpen = false"
      class="fixed inset-0 bg-ink/50 backdrop-blur-sm"
    ></div>

    <!-- Sliding Drawer Panel -->
    <div 
      x-show="mobileOpen"
      x-transition:enter="transition ease-out duration-250 transform"
      x-transition:enter-start="translate-x-full"
      x-transition:enter-end="translate-x-0"
      x-transition:leave="transition ease-in duration-200 transform"
      x-transition:leave-start="translate-x-0"
      x-transition:leave-end="translate-x-full"
      @keydown.escape.window="mobileOpen = false"
      class="relative ml-auto w-full max-w-xs h-full bg-surface shadow-soft-lg p-6 flex flex-col justify-between overflow-y-auto"
    >
      <div>
        <!-- Drawer Header with Logo-mark & Close Button -->
        <div class="flex items-center justify-between pb-6 border-b border-border">
          <a href="/" class="flex items-center gap-2.5">
            <img src="<?= asset('assets/img/logo-mark.png') ?>" alt="NatConnect" class="h-9 w-auto" />
            <span class="font-serif font-bold text-lg text-primary leading-none">NatConnect</span>
          </a>
          <button 
            type="button" 
            @click="mobileOpen = false" 
            aria-label="Close menu"
            class="p-2 text-ink-muted hover:text-ink rounded-lg focus-visible:ring-2 focus-visible:ring-primary"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Drawer Navigation Links (from PHP array) -->
        <nav class="mt-6 flex flex-col space-y-1.5" aria-label="Mobile Menu Links">
          <?php foreach ($navItems as $item): ?>
            <?php
              $itemPath = '/' . trim($item['url'], '/');
              $isActive = ($itemPath === '/' && $currentPath === '/') || ($itemPath !== '/' && str_starts_with($currentPath, $itemPath));
            ?>
            <a 
              href="<?= e($item['url']) ?>" 
              @click="mobileOpen = false" 
              class="px-3.5 py-2.5 text-base rounded-xl transition-colors <?= $isActive ? 'bg-primary/10 text-primary font-bold' : 'text-ink hover:bg-white hover:text-primary font-medium' ?>"
            >
              <?= e($item['label']) ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>

      <!-- Drawer Bottom Actions -->
      <div class="pt-6 border-t border-border mt-8 space-y-4">
        <?php if ($isDonateActive): ?>
          <a 
            href="/donate" 
            @click="mobileOpen = false"
            class="w-full flex items-center justify-center px-4 py-3 text-sm font-semibold text-white bg-accent-strong hover:bg-accent-hover rounded-xl shadow-soft transition-colors"
          >
            Donate
          </a>
        <?php endif; ?>

        <p class="text-xs text-center text-ink-muted">
          &copy; <?= date('Y') ?> <?= e(setting('site_name', 'NatConnect Foundation')) ?>
        </p>
      </div>
    </div>
  </div>
</header>

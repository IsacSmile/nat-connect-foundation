<!-- NatConnect Foundation — Global Public Header -->
<header x-data="{ mobileOpen: false, searchOpen: false }" class="sticky top-0 z-40 bg-surface/95 backdrop-blur border-b border-border-subtle">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-20">
      
      <!-- Logo & Brand Identity (Icon-only mark next to text) -->
      <a href="/" class="flex items-center gap-3 group focus-visible:ring-2 focus-visible:ring-accent rounded-lg p-1 shrink-0">
        <img 
          src="<?= asset('assets/img/logo-mark.png') ?>" 
          alt="NatConnect Foundation Mark" 
          class="h-10 sm:h-11 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
        />
        <span class="font-serif font-bold text-base sm:text-xl md:text-2xl text-primary tracking-tight leading-none group-hover:text-primary-600 transition-colors whitespace-nowrap">
          NatConnect Foundation
        </span>
      </a>

      <!-- Desktop Navigation Menu (Visible at >= 1100px) -->
      <nav class="hidden nav:flex items-center gap-6" aria-label="Main Navigation">
        <a href="/" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">Home</a>
        <a href="/about" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">About</a>
        <a href="/category/causes" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">Causes</a>
        <a href="/category/news" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">News</a>
        <a href="/category/campaigns" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">Campaigns</a>
        <a href="/category/rti-applications" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">RTI</a>
        <a href="/gallery" class="text-sm font-medium text-ink hover:text-primary transition-colors py-1 whitespace-nowrap">Gallery</a>
      </nav>

      <!-- Desktop Actions: Search & Donate CTA (Visible at >= 1100px) -->
      <div class="hidden nav:flex items-center gap-4 shrink-0">
        <!-- Search Button (Lucide Search, stroke-width 1.75) -->
        <button 
          type="button" 
          @click="searchOpen = !searchOpen" 
          aria-label="Toggle Search Bar"
          class="p-2.5 text-ink-muted hover:text-primary hover:bg-white rounded-lg border border-transparent hover:border-border-subtle transition-all focus-visible:ring-2 focus-visible:ring-accent"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
          </svg>
        </button>

        <!-- Donate Button -->
        <a 
          href="/donate" 
          class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-accent hover:bg-accent-600 rounded-xl shadow-soft-sm hover:shadow-soft transition-all duration-150 focus-visible:ring-2 focus-visible:ring-accent"
        >
          Donate
        </a>
      </div>

      <!-- Mobile / Tablet Buttons (Visible < 1100px) -->
      <div class="flex items-center gap-2 nav:hidden">
        <!-- Search Button -->
        <button 
          type="button" 
          @click="searchOpen = !searchOpen" 
          aria-label="Toggle Search"
          class="p-2 text-ink-muted hover:text-primary rounded-lg focus-visible:ring-2 focus-visible:ring-accent"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
          </svg>
        </button>

        <!-- Mobile Drawer Hamburger Toggle (Lucide Menu, stroke-width 1.75) -->
        <button 
          type="button" 
          @click="mobileOpen = true" 
          aria-label="Open Navigation Menu" 
          :aria-expanded="mobileOpen"
          class="p-2 text-ink hover:text-primary rounded-lg focus-visible:ring-2 focus-visible:ring-accent"
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
      x-transition:enter="transition ease-out duration-150"
      x-transition:enter-start="opacity-0 -translate-y-2"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-100"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      class="py-3 border-t border-border-subtle"
      style="display: none;"
    >
      <form action="/search" method="GET" class="relative max-w-xl mx-auto">
        <input 
          type="search" 
          name="q" 
          placeholder="Search articles, causes, RTI applications..." 
          class="w-full pl-11 pr-4 py-2.5 bg-white border border-border rounded-xl text-sm text-ink placeholder-ink-subtle focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all shadow-soft-sm"
        />
        <svg class="w-5 h-5 absolute left-3.5 top-3 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
        </svg>
      </form>
    </div>

  </div>

  <!-- Mobile Navigation Drawer via Alpine.js -->
  <div 
    x-show="mobileOpen" 
    class="fixed inset-0 z-50 nav:hidden" 
    style="display: none;" 
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
        <div class="flex items-center justify-between pb-6 border-b border-border-subtle">
          <a href="/" class="flex items-center gap-2.5">
            <img src="<?= asset('assets/img/logo-mark.png') ?>" alt="NatConnect" class="h-9 w-auto" />
            <span class="font-serif font-bold text-lg text-primary leading-none">NatConnect Foundation</span>
          </a>
          <button 
            type="button" 
            @click="mobileOpen = false" 
            aria-label="Close menu"
            class="p-2 text-ink-muted hover:text-ink rounded-lg focus-visible:ring-2 focus-visible:ring-accent"
          >
            <!-- Lucide X, stroke-width 1.75 -->
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Drawer Navigation Links -->
        <nav class="mt-6 flex flex-col space-y-2" aria-label="Mobile Menu Links">
          <a href="/" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">Home</a>
          <a href="/about" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">About</a>
          <a href="/category/causes" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">Causes</a>
          <a href="/category/news" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">News</a>
          <a href="/category/campaigns" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">Campaigns</a>
          <a href="/category/rti-applications" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">RTI</a>
          <a href="/gallery" @click="mobileOpen = false" class="px-3 py-2.5 text-base font-medium text-ink hover:bg-white rounded-lg hover:text-primary transition-colors">Gallery</a>
        </nav>
      </div>

      <!-- Drawer Bottom Actions -->
      <div class="pt-6 border-t border-border-subtle mt-8">
        <a 
          href="/donate" 
          @click="mobileOpen = false"
          class="w-full flex items-center justify-center px-4 py-3 text-sm font-semibold text-white bg-accent hover:bg-accent-600 rounded-xl shadow-soft transition-colors"
        >
          Donate
        </a>
        <p class="text-xs text-center text-ink-muted mt-4">
          NatConnect Foundation &copy; <?= date('Y') ?>
        </p>
      </div>
    </div>
  </div>
</header>

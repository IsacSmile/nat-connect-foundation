<!DOCTYPE html>
<html lang="en" class="h-full bg-surface">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin Console — NatConnect Foundation') ?></title>
  <meta name="robots" content="noindex,nofollow">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">

  <!-- Stylesheet -->
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

  <!-- Self-Hosted Alpine.js -->
  <script defer src="<?= asset('assets/js/alpine.min.js') ?>"></script>
</head>
<body class="h-full antialiased font-sans text-ink bg-surface">
  <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen flex flex-col lg:flex-row">
    
    <!-- Mobile Admin Sidebar Backdrop -->
    <div 
      x-show="sidebarOpen" 
      x-transition:enter="transition-opacity ease-linear duration-200"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition-opacity ease-linear duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      @click="sidebarOpen = false" 
      class="fixed inset-0 z-40 bg-ink/60 backdrop-blur-sm lg:hidden"
      style="display: none;"
      aria-hidden="true"
    ></div>

    <!-- Admin Sidebar (Slide-over drawer on mobile, fixed left on lg+) -->
    <aside 
      :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0 shadow-none'"
      class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-border text-ink transition-transform duration-200 ease-in-out flex flex-col justify-between shrink-0 shadow-none lg:shadow-none"
      :aria-hidden="(!sidebarOpen && window.innerWidth < 1024).toString()"
      aria-label="Admin Navigation Sidebar"
    >
      <div class="flex flex-col h-full overflow-y-auto">
        <!-- Sidebar Brand Header -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-border shrink-0">
          <a href="/admin/dashboard" @click="sidebarOpen = false" class="flex items-center gap-3 group focus-visible:ring-2 focus-visible:ring-primary rounded-lg p-1">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="NatConnect Foundation" class="h-8 w-auto object-contain">
            <div class="flex flex-col">
              <span class="font-serif font-bold text-primary text-base leading-none">NatConnect</span>
              <span class="text-[10px] text-accent-strong tracking-wider uppercase font-semibold mt-1">Admin</span>
            </div>
          </a>
          <button 
            type="button" 
            @click="sidebarOpen = false" 
            class="lg:hidden p-2 text-ink-muted hover:text-ink hover:bg-surface rounded-lg focus-visible:ring-2 focus-visible:ring-primary min-h-[44px] min-w-[44px] flex items-center justify-center transition-colors"
            aria-label="Close sidebar"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <?php
          $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
          $nav = $activeNav ?? match(true) {
              str_starts_with($currentUri, '/admin/posts/new') => 'new-post',
              str_starts_with($currentUri, '/admin/posts')     => 'posts',
              str_starts_with($currentUri, '/admin/pages')     => 'pages',
              str_starts_with($currentUri, '/admin/categories')=> 'categories',
              str_starts_with($currentUri, '/admin/tags')      => 'tags',
              str_starts_with($currentUri, '/admin/gallery')   => 'gallery',
              str_starts_with($currentUri, '/admin/comments')  => 'comments',
              str_starts_with($currentUri, '/admin/subscribers')=> 'subscribers',
              str_starts_with($currentUri, '/admin/settings')  => 'settings',
              str_starts_with($currentUri, '/admin/account')   => 'account',
              default => 'dashboard',
          };

          // Base classes for nav items: 3px left bar on active, transparent 3px border on inactive to prevent layout shift
          $activeClasses   = 'bg-primary/10 text-primary font-semibold border-l-[3px] border-primary rounded-r-xl';
          $inactiveClasses = 'text-ink-muted hover:text-ink hover:bg-surface font-medium border-l-[3px] border-transparent rounded-r-xl';
        ?>

        <!-- Primary Navigation Links (Min 44px touch targets) -->
        <nav class="py-4 pr-3 pl-0 space-y-1 flex-1 text-sm" aria-label="Admin Sections">
          <!-- 1. Dashboard -->
          <a 
            href="/admin/dashboard" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'dashboard' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'dashboard' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Dashboard</span>
          </a>

          <!-- 2. Posts -->
          <a 
            href="/admin/posts" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'posts' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'posts' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 14h-8M15 18h-5M10 6h8v4h-8Z"/>
            </svg>
            <span>Posts</span>
          </a>

          <!-- 3. New post -->
          <a 
            href="/admin/posts/new" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'new-post' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'new-post' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            <span>New post</span>
          </a>

          <!-- 4. Pages -->
          <a 
            href="/admin/pages" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'pages' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'pages' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
            </svg>
            <span>Pages</span>
          </a>

          <!-- 5. Categories -->
          <a 
            href="/admin/categories" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'categories' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'categories' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m7 7 5-5 5 5M7 17l5 5 5-5M2 12l5-5 5 5-5 5-5-5ZM12 12l5-5 5 5-5 5-5-5Z"/>
            </svg>
            <span>Categories</span>
          </a>

          <!-- 6. Tags -->
          <a 
            href="/admin/tags" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'tags' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'tags' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
            </svg>
            <span>Tags</span>
          </a>

          <!-- 7. Gallery -->
          <a 
            href="/admin/gallery" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'gallery' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'gallery' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
              <circle cx="9" cy="9" r="2"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
            </svg>
            <span>Gallery</span>
          </a>

          <!-- 8. Comments -->
          <a 
            href="/admin/comments" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'comments' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'comments' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
            </svg>
            <span>Comments</span>
          </a>

          <!-- 9. Subscribers -->
          <a 
            href="/admin/subscribers" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'subscribers' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'subscribers' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
            </svg>
            <span>Subscribers</span>
          </a>

          <!-- 6. Settings -->
          <a 
            href="/admin/settings" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'settings' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'settings' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>Settings</span>
          </a>

          <!-- 7. Account -->
          <a 
            href="/admin/account" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-4 py-2.5 min-h-[44px] transition-colors group <?= $nav === 'account' ? $activeClasses : $inactiveClasses ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'account' ? 'text-primary' : 'text-ink-subtle group-hover:text-ink' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Account</span>
          </a>
        </nav>

        <!-- Sidebar Bottom Actions: View Site & POST Logout Form -->
        <div class="p-4 border-t border-border space-y-1 shrink-0">
          <!-- View Site (Opens in new tab) -->
          <a 
            href="/" 
            target="_blank" 
            rel="noopener noreferrer" 
            class="flex items-center justify-between px-3 py-2 min-h-[44px] text-xs font-medium text-ink-muted hover:text-ink hover:bg-surface rounded-xl transition-colors group"
          >
            <span class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-ink-subtle group-hover:text-ink shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              <span>View site</span>
            </span>
            <span class="text-[11px] text-ink-subtle group-hover:text-ink font-mono">&nearr;</span>
          </a>

          <!-- Logout Form (POST only with CSRF, Neutral ink color, not red) -->
          <form action="/admin/logout" method="POST" class="w-full">
            <?= \App\Core\Csrf::field() ?>
            <button 
              type="submit" 
              class="w-full flex items-center gap-2.5 px-3 py-2 min-h-[44px] text-xs font-medium text-ink-muted hover:text-ink hover:bg-surface rounded-xl transition-colors focus-visible:ring-2 focus-visible:ring-primary group"
            >
              <svg class="w-4 h-4 text-ink-subtle group-hover:text-ink shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Sign out</span>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Main Workspace Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Admin Topbar (No page title heading per UI polish) -->
      <header class="h-16 bg-white border-b border-border flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0">
        <div class="flex items-center gap-3">
          <!-- Mobile Menu Drawer Toggle (Min 44x44px target) -->
          <button 
            type="button" 
            @click="sidebarOpen = true" 
            class="lg:hidden p-2.5 text-ink-muted hover:text-ink hover:bg-surface rounded-xl focus-visible:ring-2 focus-visible:ring-primary min-h-[44px] min-w-[44px] flex items-center justify-center transition-colors"
            aria-label="Open sidebar menu"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <?php $authUser = \App\Core\Auth::user(); ?>
          <?php if ($authUser): ?>
            <a href="/admin/account" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-surface text-xs text-ink-soft transition-colors min-h-[44px]">
              <span class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                <?= strtoupper(substr((string) ($authUser['name'] ?? 'A'), 0, 1)) ?>
              </span>
              <span class="hidden sm:inline font-medium"><?= e($authUser['name'] ?? 'Admin') ?></span>
            </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- Main Body Container & Flash Messages -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
        <!-- Dismissible Session Flash Messages -->
        <?php $flashes = getFlashes(); ?>
        <?php if (!empty($flashes)): ?>
          <div class="mb-6 space-y-3" role="alert">
            <?php foreach ($flashes as $type => $message): ?>
              <?php
                $isError = $type === 'error';
                $isInfo  = $type === 'info';
                $badgeBg = $isError 
                    ? 'bg-rose-50 border-rose-200 text-rose-900' 
                    : ($isInfo ? 'bg-primary/10 border-primary/20 text-ink' : 'bg-accent/10 border-accent/20 text-ink');
                $iconColor = $isError 
                    ? 'text-rose-600' 
                    : ($isInfo ? 'text-primary' : 'text-accent-strong');
              ?>
              <div 
                x-data="{ visible: true }" 
                x-show="visible" 
                class="flex items-start justify-between gap-3 p-4 rounded-xl border <?= $badgeBg ?> shadow-soft-sm text-sm"
              >
                <div class="flex items-center gap-3">
                  <?php if ($isError): ?>
                    <svg class="w-5 h-5 <?= $iconColor ?> shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <?php elseif ($isInfo): ?>
                    <svg class="w-5 h-5 <?= $iconColor ?> shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                  <?php else: ?>
                    <svg class="w-5 h-5 <?= $iconColor ?> shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                  <?php endif; ?>
                  <span class="font-medium"><?= e($message) ?></span>
                </div>
                <button 
                  type="button" 
                  @click="visible = false" 
                  class="p-1 -mr-1 -mt-1 text-ink-muted hover:text-ink rounded-lg focus-visible:ring-2 focus-visible:ring-primary min-h-[36px] min-w-[36px] flex items-center justify-center transition-colors"
                  aria-label="Dismiss message"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
      </main>
    </div>
  </div>
</body>
</html>

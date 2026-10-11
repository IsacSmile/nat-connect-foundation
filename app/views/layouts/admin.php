<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
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
<body class="h-full antialiased font-sans text-ink bg-slate-50">
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
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
      class="fixed lg:static inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-200 transition-transform duration-200 ease-in-out flex flex-col justify-between shrink-0 shadow-2xl lg:shadow-none"
      :aria-hidden="(!sidebarOpen && window.innerWidth < 1024).toString()"
      aria-label="Admin Navigation Sidebar"
    >
      <div class="flex flex-col h-full overflow-y-auto">
        <!-- Sidebar Brand Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800 shrink-0">
          <a href="/admin/dashboard" @click="sidebarOpen = false" class="flex items-center gap-3 group focus-visible:ring-2 focus-visible:ring-accent rounded-lg p-1">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="NatConnect Foundation" class="h-8 w-auto object-contain">
            <div class="flex flex-col">
              <span class="font-serif font-bold text-white text-base leading-none">NatConnect</span>
              <span class="text-[10px] text-accent-strong tracking-wider uppercase font-semibold mt-1">Admin CMS</span>
            </div>
          </a>
          <button 
            type="button" 
            @click="sidebarOpen = false" 
            class="lg:hidden p-2 text-slate-400 hover:text-white rounded-lg focus-visible:ring-2 focus-visible:ring-accent min-h-[44px] min-w-[44px] flex items-center justify-center"
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
              str_starts_with($currentUri, '/admin/categories')=> 'categories',
              str_starts_with($currentUri, '/admin/comments')  => 'comments',
              str_starts_with($currentUri, '/admin/settings')  => 'settings',
              str_starts_with($currentUri, '/admin/account')   => 'account',
              default => 'dashboard',
          };
        ?>

        <!-- Primary Navigation Links (Min 44px touch targets) -->
        <nav class="p-4 space-y-1.5 flex-1 text-sm font-medium" aria-label="Admin Sections">
          <!-- 1. Dashboard -->
          <a 
            href="/admin/dashboard" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'dashboard' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'dashboard' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Dashboard</span>
          </a>

          <!-- 2. Articles & Posts -->
          <a 
            href="/admin/posts" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'posts' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'posts' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 14h-8M15 18h-5M10 6h8v4h-8Z"/>
            </svg>
            <span>Articles & Posts</span>
          </a>

          <!-- 3. New Post -->
          <a 
            href="/admin/posts/new" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'new-post' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'new-post' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            <span>New Article</span>
          </a>

          <!-- 4. Categories -->
          <a 
            href="/admin/categories" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'categories' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'categories' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m7 7 5-5 5 5M7 17l5 5 5-5M2 12l5-5 5 5-5 5-5-5ZM12 12l5-5 5 5-5 5-5-5Z"/>
            </svg>
            <span>Categories</span>
          </a>

          <!-- 5. Comments -->
          <a 
            href="/admin/comments" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'comments' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'comments' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
            </svg>
            <span>Comments</span>
          </a>

          <!-- 6. Settings -->
          <a 
            href="/admin/settings" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'settings' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'settings' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>Site Settings</span>
          </a>

          <!-- 7. Account -->
          <a 
            href="/admin/account" 
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl transition-colors <?= $nav === 'account' ? 'bg-slate-800 text-white font-semibold shadow-inner' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>"
          >
            <svg class="w-5 h-5 <?= $nav === 'account' ? 'text-accent-strong' : 'text-slate-400' ?> shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Account Profile</span>
          </a>
        </nav>

        <!-- Sidebar Bottom Actions: View Site & POST Logout Form -->
        <div class="p-4 border-t border-slate-800 space-y-2 shrink-0">
          <!-- View Site (Opens in new tab) -->
          <a 
            href="/" 
            target="_blank" 
            rel="noopener noreferrer" 
            class="flex items-center justify-between px-3.5 py-2.5 min-h-[44px] text-xs font-semibold text-slate-300 hover:bg-slate-800/80 hover:text-white rounded-xl transition-colors"
          >
            <span class="flex items-center gap-2">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              <span>View Public Site</span>
            </span>
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-mono">&nearr;</span>
          </a>

          <!-- Logout Form (POST only with CSRF) -->
          <form action="/admin/logout" method="POST" class="w-full">
            <?= \App\Core\Csrf::field() ?>
            <button 
              type="submit" 
              class="w-full flex items-center gap-2.5 px-3.5 py-2.5 min-h-[44px] text-xs font-semibold text-rose-300 hover:bg-rose-500/10 hover:text-rose-200 rounded-xl transition-colors focus-visible:ring-2 focus-visible:ring-rose-400"
            >
              <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Sign Out</span>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Main Workspace Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Admin Topbar -->
      <header class="h-16 bg-white border-b border-border flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0">
        <div class="flex items-center gap-3">
          <!-- Mobile Menu Drawer Toggle (Min 44x44px target) -->
          <button 
            type="button" 
            @click="sidebarOpen = true" 
            class="lg:hidden p-2.5 text-ink-muted hover:text-ink hover:bg-slate-100 rounded-xl focus-visible:ring-2 focus-visible:ring-accent min-h-[44px] min-w-[44px] flex items-center justify-center transition-colors"
            aria-label="Open sidebar menu"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div class="font-serif font-bold text-lg text-primary truncate">
            <?= e($pageTitle ?? 'Admin Console') ?>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <?php $authUser = \App\Core\Auth::user(); ?>
          <?php if ($authUser): ?>
            <a href="/admin/account" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-slate-100 text-xs text-ink-soft transition-colors min-h-[44px]">
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
                $badgeBg = $isError ? 'bg-rose-50 border-rose-200 text-rose-900' : ($isInfo ? 'bg-blue-50 border-blue-200 text-blue-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900');
                $iconColor = $isError ? 'text-rose-600' : ($isInfo ? 'text-blue-600' : 'text-emerald-600');
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
                  class="p-1 -mr-1 -mt-1 text-slate-400 hover:text-slate-700 rounded-lg focus-visible:ring-2 focus-visible:ring-accent min-h-[36px] min-w-[36px] flex items-center justify-center transition-colors"
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

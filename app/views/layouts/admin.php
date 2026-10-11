<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin Console — NatConnect Foundation') ?></title>
  <meta name="robots" content="noindex, nofollow">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">

  <!-- Stylesheet -->
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

  <!-- Self-Hosted Alpine.js -->
  <script defer src="<?= asset('assets/js/alpine.min.js') ?>"></script>
</head>
<body class="h-full antialiased font-sans text-ink">
  <div x-data="{ sidebarOpen: false }" class="min-h-screen flex flex-col lg:flex-row">
    
    <!-- Mobile Admin Sidebar Backdrop -->
    <div 
      x-show="sidebarOpen" 
      @click="sidebarOpen = false" 
      class="fixed inset-0 z-40 bg-ink/50 backdrop-blur-sm lg:hidden"
      style="display: none;"
    ></div>

    <!-- Admin Sidebar -->
    <aside 
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
      class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-200 transition-transform duration-200 ease-in-out flex flex-col justify-between shrink-0"
    >
      <div>
        <!-- Sidebar Brand -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800">
          <a href="/admin/dashboard" class="flex items-center gap-2.5">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="Logo" class="h-8 w-auto">
            <div class="flex flex-col">
              <span class="font-serif font-bold text-white leading-none">NatConnect</span>
              <span class="text-[10px] text-accent tracking-wider uppercase font-semibold">Admin CMS</span>
            </div>
          </a>
          <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Navigation Links (Skeleton for Phase 2 & 3) -->
        <nav class="p-4 space-y-1 text-sm font-medium">
          <a href="/admin/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-800 text-white">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
          </a>
          <a href="/admin/posts" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
            Articles & Posts
          </a>
          <a href="/admin/categories" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Categories
          </a>
          <a href="/admin/comments" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Comments
          </a>
          <a href="/admin/settings" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Site Settings
          </a>
        </nav>
      </div>

      <!-- User footer & Logout -->
      <div class="p-4 border-t border-slate-800">
        <a href="/" target="_blank" class="block text-xs text-slate-400 hover:text-white mb-3">
          &larr; View Public Site
        </a>
        <form action="/admin/logout" method="POST">
          <?= \App\Core\Csrf::field() ?>
          <button type="submit" class="w-full text-left text-xs font-semibold text-rose-400 hover:text-rose-300">
            Sign Out
          </button>
        </form>
      </div>
    </aside>

    <!-- Main Admin Body -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Admin Topbar -->
      <header class="h-16 bg-white border-b border-border flex items-center justify-between px-6 lg:px-8 shrink-0">
        <button @click="sidebarOpen = true" class="lg:hidden text-ink-muted hover:text-ink">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="text-sm text-ink-muted">
          NatConnect Foundation Management System
        </div>
        <div class="flex items-center gap-3">
          <span class="text-xs px-2.5 py-1 bg-green-50 text-accent font-semibold rounded-full border border-green-200">
            Phase 1 Ready
          </span>
        </div>
      </header>

      <!-- Content Area -->
      <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <?= $content ?? '' ?>
      </main>
    </div>
  </div>
</body>
</html>

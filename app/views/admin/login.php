<!DOCTYPE html>
<html lang="en" class="h-full bg-surface">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin Sign In — NatConnect Foundation') ?></title>
  <meta name="robots" content="noindex,nofollow">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">

  <!-- Stylesheet -->
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

  <!-- Self-Hosted Alpine.js -->
  <script defer src="<?= asset('assets/js/alpine.min.js') ?>"></script>
</head>
<body class="min-h-screen bg-surface flex flex-col justify-center items-center p-4 sm:p-6 antialiased font-sans text-ink">
  <div class="w-full max-w-md mx-auto">
    
    <!-- Brand Mark & Header -->
    <div class="text-center mb-8">
      <a href="/" class="inline-flex items-center justify-center p-2 rounded-2xl group focus-visible:ring-2 focus-visible:ring-accent">
        <img 
          src="<?= asset('assets/img/logo-mark.png') ?>" 
          alt="NatConnect Foundation Mark" 
          class="h-14 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
        />
      </a>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-primary tracking-tight mt-3">
        NatConnect Admin
      </h1>
    </div>

    <!-- Sign In Card -->
    <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-lg">
      
      <!-- Session Flash Messages -->
      <?php $flashes = getFlashes(); ?>
      <?php if (!empty($flashes)): ?>
        <div class="mb-5 space-y-2" role="alert">
          <?php foreach ($flashes as $type => $msg): ?>
            <?php
              $isError = $type === 'error';
              $badgeBg = $isError ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800';
            ?>
            <div class="p-3.5 rounded-xl border <?= $badgeBg ?> text-xs font-medium flex items-center gap-2.5">
              <span>•</span>
              <span><?= e($msg) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Form Error Messages (Generic message or Rate limit lockout) -->
      <?php if (!empty($errors)): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium space-y-1" role="alert">
          <?php foreach ($errors as $error): ?>
            <p class="flex items-center gap-2">
              <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span><?= e($error) ?></span>
            </p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="/admin/login" method="POST" class="space-y-4" novalidate>
        <?= \App\Core\Csrf::field() ?>

        <?php if (!empty($next)): ?>
          <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php endif; ?>

        <!-- Email Field (Autofocused with correct autocomplete) -->
        <div>
          <label for="email" class="block text-xs font-semibold text-ink-soft mb-1.5">
            Email Address
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= e($email ?? '') ?>" 
            required 
            autofocus 
            autocomplete="username"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink placeholder:text-ink-subtle focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
            placeholder=""
          />
        </div>

        <!-- Password Field (With Alpine Show/Hide Toggle) -->
        <div x-data="{ showPassword: false }">
          <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="block text-xs font-semibold text-ink-soft">
              Password
            </label>
          </div>
          <div class="relative">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              name="password" 
              required 
              autocomplete="current-password"
              class="w-full pl-3.5 pr-11 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink placeholder:text-ink-subtle focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
              placeholder=""
            />
            <button 
              type="button" 
              @click="showPassword = !showPassword" 
              class="absolute right-0 inset-y-0 px-3 flex items-center text-ink-muted hover:text-ink transition-colors focus-visible:ring-2 focus-visible:ring-accent rounded-r-xl"
              :aria-label="showPassword ? 'Hide password' : 'Show password'"
            >
              <!-- Lucide Eye Off / Eye SVGs -->
              <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
              </svg>
              <svg x-show="showPassword" style="display: none;" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Submit Button (Solid Navy: bg-primary hover:bg-primary-hover text-white) -->
        <button 
          type="submit" 
          class="w-full mt-2 py-3 px-4 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors focus-visible:ring-2 focus-visible:ring-primary min-h-[44px] flex items-center justify-center gap-2"
        >
          <span>Sign In to Admin</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>
    </div>

    <!-- Return Link -->
    <div class="mt-6 text-center">
      <a href="/" class="inline-flex items-center gap-1.5 text-xs text-ink-muted hover:text-primary transition-colors py-2 px-3 rounded-lg">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Return to Public Website</span>
      </a>
    </div>

  </div>
</body>
</html>

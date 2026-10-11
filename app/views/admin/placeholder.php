<!-- NatConnect Foundation — Phase Module Placeholder -->
<div class="max-w-2xl mx-auto py-12 text-center space-y-6">
  
  <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-accent/10 border border-accent/20 text-accent-strong font-semibold text-xs tracking-wider uppercase">
    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3L12 3Z"/></svg>
    <span><?= e($featurePhase ?? 'Upcoming Module') ?></span>
  </div>

  <h1 class="font-serif text-3xl sm:text-4xl font-bold text-primary">
    <?= e($featureTitle ?? 'Feature in Development') ?>
  </h1>

  <p class="text-sm sm:text-base text-ink-muted leading-relaxed max-w-lg mx-auto">
    <?= e($description ?? 'This module will be introduced in the upcoming development phase.') ?>
  </p>

  <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
    <a 
      href="/admin/dashboard" 
      class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      <span>Return to Dashboard</span>
    </a>
  </div>

</div>

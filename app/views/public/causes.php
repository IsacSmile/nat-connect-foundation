<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — All Causes Page (/causes)
 *
 * @var string $causesTitle Page heading from settings
 * @var string $causesIntro Optional intro text from settings
 * @var array $causes All tags with show_as_cause = 1
 */
?>
<div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">

  <!-- Causes Page Header -->
  <header class="border-b border-border pb-8 max-w-3xl space-y-3">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/10 text-accent-strong text-xs font-semibold uppercase tracking-wider">
      Our Focus
    </div>
    
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink">
      <?= e($causesTitle) ?>
    </h1>

    <?php if (!empty($causesIntro)): ?>
      <p class="text-base sm:text-lg text-ink-muted leading-relaxed">
        <?= e($causesIntro) ?>
      </p>
    <?php endif; ?>
  </header>

  <!-- Causes Grid: 3 columns desktop, 2 tablet, 1 mobile -->
  <?php if (!empty($causes)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
      <?php foreach ($causes as $cause): ?>
        <?php \App\Core\View::partial('partials/cause-card', ['cause' => $cause]); ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-16 bg-white rounded-3xl border border-border p-8 max-w-lg mx-auto space-y-3">
      <p class="font-serif text-xl font-bold text-ink">No causes listed</p>
      <p class="text-sm text-ink-muted">There are currently no active causes configured.</p>
      <div class="pt-2">
        <a href="/" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-primary hover:underline">
          &larr; Return to Homepage
        </a>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php
declare(strict_types=1);

/**
 * Shared Section Header Partial
 *
 * Requirements:
 * - Eyebrow (optional): 12px uppercase tracking-wider font-semibold text-accent-strong
 * - h2: Fraunces, weight 500, 28px mobile / 36px desktop
 * - Subtitle (optional): text-ink-muted, min 14px
 * - "View all →" link (optional): aligned right on desktop, below on mobile
 *
 * @var string|null $eyebrow
 * @var string $title
 * @var string|null $subtitle
 * @var string|null $viewAllUrl
 * @var string|null $viewAllLabel
 */

$viewAllLabel = !empty($viewAllLabel) ? $viewAllLabel : 'View all';
?>
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 border-b border-ink/10 pb-4 mb-8">
  <div class="space-y-1 max-w-2xl">
    <?php if (!empty($eyebrow)): ?>
      <div class="mb-1.5">
        <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong">
          <?= e($eyebrow) ?>
        </span>
      </div>
    <?php endif; ?>
    <h2 class="font-serif text-[28px] sm:text-[36px] font-medium text-ink leading-tight">
      <?= e($title) ?>
    </h2>
    <?php if (!empty($subtitle)): ?>
      <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
        <?= e($subtitle) ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($viewAllUrl)): ?>
    <div class="shrink-0 pt-1 sm:pt-0">
      <a 
        href="<?= e($viewAllUrl) ?>" 
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary-hover transition-colors"
      >
        <span><?= e($viewAllLabel) ?></span>
        <span aria-hidden="true">&rarr;</span>
      </a>
    </div>
  <?php endif; ?>
</div>

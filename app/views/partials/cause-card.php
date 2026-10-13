<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Cause Card Partial
 *
 * @var array $cause Tag record with show_as_cause = 1
 * @var string|null $extraClasses Optional extra classes for container
 */

$causeUrl = '/tag/' . e($cause['slug']);
$causeImg = !empty($cause['image_path']) ? \App\Core\ImageUploader::getVariantUrl($cause['image_path'], 800) : '';
$causeAlt = !empty($cause['image_alt']) ? $cause['image_alt'] : $cause['name'];
$extraClasses = $extraClasses ?? '';
?>
<article class="flex flex-col bg-white rounded-lg border border-ink/10 overflow-hidden hover:border-primary/30 transition-colors group relative <?= e($extraClasses) ?>">
  <div class="relative aspect-[16/10] w-full overflow-hidden bg-surface">
    <?php if ($causeImg): ?>
      <img 
        src="<?= e($causeImg) ?>" 
        alt="<?= e($causeAlt) ?>" 
        width="800" 
        height="500" 
        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        loading="lazy"
      >
    <?php else: ?>
      <div class="w-full h-full flex items-center justify-center bg-surface text-primary/60 font-serif text-sm">
        Cause
      </div>
    <?php endif; ?>
  </div>

  <div class="p-5 sm:p-6 flex flex-col flex-1">
    <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong mb-2 block">
      Cause
    </span>
    <h3 class="font-serif text-xl sm:text-2xl font-medium text-ink group-hover:text-primary transition-colors leading-snug mb-2">
      <a href="<?= $causeUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
        <?= e($cause['name']) ?>
      </a>
    </h3>
    <?php if (!empty($cause['description'])): ?>
      <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed mb-4">
        <?= e($cause['description']) ?>
      </p>
    <?php endif; ?>
    <div class="mt-auto pt-3 border-t border-ink/10 flex items-center gap-1 text-sm font-semibold text-accent-strong group-hover:text-accent-hover transition-colors">
      <span>Explore</span>
      <span aria-hidden="true">&rarr;</span>
    </div>
  </div>
</article>

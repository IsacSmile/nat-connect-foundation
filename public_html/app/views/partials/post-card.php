<?php
declare(strict_types=1);

/**
 * Shared Post Card Partial (Design Phase D2)
 *
 * Requirements:
 * - rounded-lg, 1px hairline border (border-ink/10), subtle/no heavy shadows
 * - Card image ratio 16:10, 800w variant, lazy loading (eager only if explicitly requested)
 * - Eyebrow: "CATEGORY · DATE" (12px, uppercase, tracking-wider, font-semibold, text-accent-strong)
 * - Title: Fraunces weight 500, serif, 18-20px
 * - Excerpt: 2-line clamp, min 14px text
 * - Action: "Read story →"
 *
 * @var array $post
 * @var bool|null $eager
 * @var string|null $sizes
 */

$eager = $eager ?? false;
$sizes = $sizes ?? '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';

$postUrl = '/post/' . e($post['slug'] ?? '');
$title = $post['title'] ?? 'Untitled Post';
$excerpt = !empty($post['excerpt']) ? $post['excerpt'] : snippet($post['content'] ?? '', 140);

$rawDate = $post['published_at'] ?? ($post['created_at'] ?? '');
$dateFormatted = !empty($rawDate) ? strtoupper(date('d M Y', strtotime($rawDate))) : '';

$categoryName = !empty($post['category_name']) ? strtoupper((string) $post['category_name']) : 'STORY';
$eyebrowLine = $categoryName . ($dateFormatted !== '' ? ' · ' . $dateFormatted : '');

$coverType = $post['cover_type'] ?? 'image';
$coverImage = $post['cover_image'] ?? '';
$coverAlt = !empty($post['cover_alt']) ? $post['cover_alt'] : $title;
$videoUrl = $post['video_url'] ?? '';

$hasVideoCover = ($coverType === 'video' && !empty($videoUrl));
$hasImageCover = ($coverType === 'image' && !empty($coverImage));
?>

<article class="group relative flex flex-col bg-white rounded-lg border border-ink/10 overflow-hidden hover:border-primary/30 transition-colors">
  <!-- Card Media Container (Aspect 16:10) -->
  <div class="relative aspect-[16/10] w-full overflow-hidden bg-surface">
    <?php if ($hasVideoCover): ?>
      <?php $ytThumb = "https://img.youtube.com/vi/" . rawurlencode($videoUrl) . "/hqdefault.jpg"; ?>
      <img 
        src="<?= $ytThumb ?>" 
        alt="<?= e($coverAlt) ?>" 
        width="800" 
        height="500" 
        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        <?= $eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
      >
      <!-- Video Play Indicator Badge -->
      <div class="absolute bottom-3 right-3 p-1.5 rounded-full bg-ink/70 text-white backdrop-blur pointer-events-none shadow-sm">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M8 5v14l11-7z"/>
        </svg>
      </div>

    <?php elseif ($hasImageCover): ?>
      <?php $attrs = \App\Core\ImageUploader::getResponsiveAttributes($coverImage, $sizes); ?>
      <?php if ($attrs): ?>
        <img 
          src="<?= e($attrs['src']) ?>" 
          srcset="<?= e($attrs['srcset']) ?>" 
          sizes="<?= e($attrs['sizes']) ?>" 
          width="<?= (int) $attrs['width'] ?>" 
          height="<?= (int) $attrs['height'] ?>" 
          alt="<?= e($coverAlt) ?>" 
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          <?= $eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
        >
      <?php else: ?>
        <img 
          src="<?= e(\App\Core\ImageUploader::getVariantUrl($coverImage, 800)) ?>" 
          alt="<?= e($coverAlt) ?>" 
          width="800" 
          height="500" 
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          <?= $eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
        >
      <?php endif; ?>

    <?php else: ?>
      <!-- Neutral Branded CSS Fallback -->
      <div class="w-full h-full flex flex-col items-center justify-center p-6 bg-gradient-to-br from-primary/5 via-surface to-accent/10 border-b border-ink/10 select-none">
        <div class="w-12 h-12 rounded-lg bg-white/80 border border-ink/10 flex items-center justify-center text-primary/70 mb-2 shadow-soft-sm group-hover:scale-105 transition-transform">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918"/>
          </svg>
        </div>
        <span class="font-serif font-medium text-xs tracking-wider uppercase text-primary/60">NatConnect</span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Content Body -->
  <div class="flex flex-col flex-1 p-5 sm:p-6">
    <!-- Eyebrow: "CATEGORY · DATE" -->
    <div class="mb-2">
      <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong">
        <?= e($eyebrowLine) ?>
      </span>
    </div>

    <!-- Post Title (Wraps up to 3 lines, no truncation with ellipsis) -->
    <h3 class="font-serif font-medium text-lg sm:text-xl text-ink group-hover:text-primary transition-colors leading-snug mb-2.5">
      <a href="<?= $postUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
        <?= e($title) ?>
      </a>
    </h3>

    <!-- Excerpt (2 Lines Clamp, min 14px text) -->
    <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed mb-4">
      <?= e($excerpt) ?>
    </p>

    <!-- Action Link (Arrow directly after text, gap-1) -->
    <div class="mt-auto pt-3 border-t border-ink/10 flex items-center gap-1 text-sm font-semibold text-accent-strong group-hover:text-accent-hover transition-colors">
      <span>Read story</span>
      <span aria-hidden="true">&rarr;</span>
    </div>
  </div>
</article>

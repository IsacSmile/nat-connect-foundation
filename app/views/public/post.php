<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Single Article View Template (Phase 4B)
 *
 * Variables provided by PublicController:
 * - $post: array containing full post row with category_name & category_slug
 * - $tags: array of tag rows (id, name, slug)
 * - $relatedPosts: array of up to 3 published related posts
 * - $popularPosts: array of up to 3 popular published posts
 * - $prevPost: ?array chronologically earlier published post
 * - $nextPost: ?array chronologically later published post
 * - $isPreview: bool true if rendering admin preview
 * - $isDonateActive: bool true if any donation fields are populated
 */

use App\Core\ImageUploader;
use App\Models\SettingModel;

$isDonateActive = SettingModel::isDonateActive();
$shareUrl = url('/post/' . $post['slug']);
$shareTitle = $post['title'] ?? 'NatConnect Foundation';

$whatsappUrl = 'https://api.whatsapp.com/send?text=' . rawurlencode($shareTitle . "\n" . $shareUrl);
$xUrl = 'https://x.com/intent/tweet?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($shareTitle);
$fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl);
$linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareUrl);

// Calculate if updated more than 1 day after publishing
$isUpdated = false;
if (!empty($post['updated_at']) && !empty($post['published_at'])) {
    $pubTs = strtotime((string) $post['published_at']);
    $updTs = strtotime((string) $post['updated_at']);
    if ($updTs - $pubTs > 86400) {
        $isUpdated = true;
    }
}

// Media hero determination
$hasVideo = (($post['cover_type'] ?? '') === 'video' && !empty($post['video_url']));
$videoId = $hasVideo ? youtubeId($post['video_url']) : null;
$hasImage = (!empty($post['cover_image']) && !$hasVideo);
$imageAttrs = $hasImage ? ImageUploader::getResponsiveAttributes($post['cover_image'], '(min-width: 1024px) 800px, 100vw') : null;

// Determine if sidebar has any content
$hasSidebar = (!empty($relatedPosts) || !empty($popularPosts));

// Check if excerpt was written manually (not auto-generated from body content)
$isManualExcerpt = false;
if (!empty($post['excerpt'])) {
    $cleanExcerpt = trim((string) $post['excerpt']);
    $cleanContent = trim((string) preg_replace('/\s+/', ' ', strip_tags($post['content'] ?? '')));
    if (!str_starts_with($cleanContent, $cleanExcerpt)) {
        $isManualExcerpt = true;
    }
}
?>

<div class="py-8 md:py-12">
  <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
    <div class="<?= $hasSidebar ? 'lg:grid lg:grid-cols-12 lg:gap-12 items-start' : 'max-w-3xl mx-auto' ?>">

      <!-- ========================================================= -->
      <!-- Main Article Column (lg:col-span-8 or centered max-w-3xl) -->
      <!-- ========================================================= -->
      <article class="<?= $hasSidebar ? 'lg:col-span-8' : 'w-full' ?>">

        <!-- 1. Header Metadata Section -->
        <header class="mb-8">
          <!-- Category Chip -->
          <?php if (!empty($post['category_name'])): ?>
            <div class="mb-3">
              <a 
                href="/category/<?= e($post['category_slug'] ?? '') ?>" 
                class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold tracking-wide uppercase bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
              >
                <?= e($post['category_name']) ?>
              </a>
            </div>
          <?php endif; ?>

          <!-- Article H1 Heading -->
          <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink leading-[1.18] tracking-tight mb-4">
            <?= e($post['title']) ?>
          </h1>

          <!-- Excerpt Subtitle ONLY if written manually -->
          <?php if ($isManualExcerpt): ?>
            <p class="text-lg sm:text-xl text-ink-muted leading-relaxed font-sans mb-6">
              <?= e($post['excerpt']) ?>
            </p>
          <?php endif; ?>

          <!-- Meta Byline Row (At least 14px; on mobile author + date on line 1, read time + views on line 2 if needed) -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-y-2.5 gap-x-4 text-sm text-ink-muted py-3.5 border-y border-border">
            <!-- Group 1: Author + Date -->
            <div class="flex items-center flex-wrap gap-2 text-sm">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0">
                  <?= strtoupper(substr($post['author_name'] ?? 'N', 0, 1)) ?>
                </span>
                <span class="font-medium text-ink text-sm"><?= e($post['author_name'] ?? 'NatConnect Foundation') ?></span>
              </div>

              <span class="text-border" aria-hidden="true">&bull;</span>

              <!-- Published Date -->
              <time datetime="<?= date('Y-m-d', strtotime($post['published_at'] ?? 'now')) ?>" class="text-ink-soft text-sm whitespace-nowrap">
                <?= formatDate($post['published_at'] ?? $post['created_at']) ?>
              </time>

              <!-- Updated Notice (> 1 day) -->
              <?php if ($isUpdated): ?>
                <span class="text-border" aria-hidden="true">&bull;</span>
                <span class="text-amber-800 bg-amber-50 px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap">
                  Updated <?= formatDate($post['updated_at']) ?>
                </span>
              <?php endif; ?>
            </div>

            <!-- Group 2: Read Time + Views -->
            <div class="flex items-center gap-2.5 text-sm text-ink-muted">
              <!-- Read Time -->
              <span class="flex items-center gap-1.5 whitespace-nowrap text-sm">
                <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="12" cy="12" r="9"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
                </svg>
                <?= (int) ($post['read_time_minutes'] ?? 1) ?> min read
              </span>

              <?php $postViews = (int) ($post['views'] ?? 0); ?>
              <?php if ($postViews >= 10): ?>
                <span class="text-border" aria-hidden="true">&bull;</span>

                <!-- Views Count (shown only when views >= 10) -->
                <span class="flex items-center gap-1.5 whitespace-nowrap text-sm">
                  <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                  </svg>
                  <?= number_format($postViews) ?> views
                </span>
              <?php endif; ?>

              <span class="text-border" aria-hidden="true">&bull;</span>

              <!-- Comments Count -->
              <?php $commentCount = $commentCount ?? count($comments ?? []); ?>
              <a href="#comments" class="flex items-center gap-1.5 whitespace-nowrap text-sm hover:text-primary transition-colors">
                <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.009Z" />
                </svg>
                <span><?= $commentCount === 1 ? '1 comment' : $commentCount . ' comments' ?></span>
              </a>
            </div>
          </div>
        </header>

        <!-- 2. Hero Cover Media Section -->
        <?php if ($hasVideo && !empty($videoId)): ?>
          <!-- YouTube Lite Embed (No scripts on load, loads nocookie iframe on click) -->
          <div x-data="{ playing: false }" class="relative aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-soft mb-8">
            <template x-if="!playing">
              <div 
                class="relative w-full h-full cursor-pointer group" 
                @click="playing = true" 
                role="button" 
                tabindex="0"
                @keydown.enter="playing = true"
                @keydown.space.prevent="playing = true"
                aria-label="Play video: <?= e($post['title']) ?>"
              >
                <img 
                  src="https://img.youtube.com/vi/<?= e($videoId) ?>/hqdefault.jpg" 
                  alt="<?= e($post['title']) ?>" 
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                  loading="eager"
                  fetchpriority="high"
                />
                <div class="absolute inset-0 bg-black/30 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                  <div class="w-16 h-16 sm:w-20 sm:h-20 bg-ink/70 hover:bg-ink/80 text-white rounded-full flex items-center justify-center shadow-lg transition-transform group-hover:scale-110 backdrop-blur-sm">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 ml-1 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M8 5v14l11-7z"/>
                    </svg>
                  </div>
                </div>
              </div>
            </template>
            <template x-if="playing">
              <iframe 
                src="https://www.youtube-nocookie.com/embed/<?= e($videoId) ?>?autoplay=1" 
                title="<?= e($post['title']) ?>"
                class="w-full h-full border-0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
              ></iframe>
            </template>
          </div>
        <?php elseif ($hasImage && $imageAttrs): ?>
          <!-- Responsive Image Cover -->
          <figure class="relative aspect-video w-full rounded-2xl overflow-hidden shadow-soft bg-surface-alt mb-8">
            <img 
              src="<?= e($imageAttrs['src']) ?>" 
              srcset="<?= e($imageAttrs['srcset']) ?>" 
              sizes="(min-width: 1024px) 800px, 100vw"
              alt="<?= e($post['cover_alt'] ?? $post['title']) ?>" 
              width="1600" 
              height="900" 
              loading="eager" 
              fetchpriority="high"
              class="w-full h-full object-cover"
            />
            <?php if (!empty($post['cover_alt'])): ?>
              <figcaption class="sr-only"><?= e($post['cover_alt']) ?></figcaption>
            <?php endif; ?>
          </figure>
        <?php endif; ?>

        <!-- 3. Readable Editorial Prose Body (~68ch) -->
        <div class="prose max-w-none text-ink-soft leading-relaxed">
          <?= $post['content'] ?? '' ?>
        </div>

        <!-- 4. Tags Section -->
        <?php if (!empty($tags)): ?>
          <div class="mt-8 pt-6 border-t border-border flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted mr-1">Tags:</span>
            <?php foreach ($tags as $tag): ?>
              <a 
                href="/tag/<?= e($tag['slug']) ?>" 
                class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-surface-alt hover:bg-primary/10 text-ink-soft hover:text-primary border border-border/70 transition-colors"
              >
                #<?= e($tag['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Share Row Below Content (Uniform neutral buttons with border and ink icons, WhatsApp first, or native share) -->
        <div 
          x-data="{ 
            canShare: false,
            copied: false,
            init() {
              this.canShare = (typeof navigator !== 'undefined' && typeof navigator.share === 'function');
            },
            async shareNative() {
              try {
                await navigator.share({
                  title: <?= json_encode($shareTitle) ?>,
                  url: <?= json_encode($shareUrl) ?>
                });
              } catch (e) {
                /* User cancelled or dismissed */
              }
            }
          }"
          class="mt-8 pt-6 border-t border-border"
          aria-label="Share this story"
        >
          <div class="flex items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Share:</span>

            <!-- Native Share Button (Shown on devices with navigator.share) -->
            <template x-if="canShare">
              <button 
                type="button" 
                @click="shareNative()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-white hover:bg-surface text-ink border border-border hover:border-ink/30 shadow-soft-sm transition-all focus:outline-none"
                aria-label="Share this article"
              >
                <svg class="w-4 h-4 text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/>
                </svg>
                <span>Share</span>
              </button>
            </template>

            <!-- Fallback Neutral Button Row (Uniform neutral buttons, WhatsApp first, ink icons, no brand colors) -->
            <div x-show="!canShare" class="flex flex-wrap items-center gap-2">
              <!-- WhatsApp (First) -->
              <a 
                href="<?= e($whatsappUrl) ?>" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-surface text-ink border border-border hover:border-ink/30 transition-colors"
                aria-label="Share on WhatsApp"
              >
                <svg class="w-3.5 h-3.5 fill-current text-ink" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M17.507 14.307l-.009.075c-.338.258-2.036 1.025-2.28 1.115-.243.09-.422.135-.6.135-.178 0-.356-.045-.534-.135-.178-.089-1.246-.579-2.315-1.532-.837-.745-1.402-1.666-1.567-1.951-.164-.285-.018-.438.106-.563.111-.112.247-.291.371-.437.124-.145.165-.247.248-.412.083-.165.041-.309-.021-.434-.062-.124-.557-1.341-.763-1.836-.201-.482-.405-.417-.557-.425l-.475-.008c-.165 0-.433.062-.66.309-.227.247-.866.846-.866 2.064 0 1.218.887 2.395 1.011 2.56.124.165 1.749 2.671 4.237 3.746.592.256 1.054.409 1.414.523.595.189 1.137.162 1.565.098.477-.071 1.467-.6 1.674-1.179.207-.579.207-1.074.145-1.179-.062-.104-.227-.166-.475-.289l.059-.045zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.662 1.433 5.178L2 22l4.981-1.402C8.423 21.493 10.155 22 12 22c5.523 0 10-6.477 10-12S17.523 2 12 2z"/>
                </svg>
                <span>WhatsApp</span>
              </a>

              <!-- X / Twitter -->
              <a 
                href="<?= e($xUrl) ?>" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-surface text-ink border border-border hover:border-ink/30 transition-colors"
                aria-label="Share on X"
              >
                <svg class="w-3.5 h-3.5 fill-current text-ink" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                <span>X</span>
              </a>

              <!-- Facebook -->
              <a 
                href="<?= e($fbUrl) ?>" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-surface text-ink border border-border hover:border-ink/30 transition-colors"
                aria-label="Share on Facebook"
              >
                <svg class="w-3.5 h-3.5 fill-current text-ink" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Facebook</span>
              </a>

              <!-- LinkedIn -->
              <a 
                href="<?= e($linkedinUrl) ?>" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-surface text-ink border border-border hover:border-ink/30 transition-colors"
                aria-label="Share on LinkedIn"
              >
                <svg class="w-3.5 h-3.5 fill-current text-ink" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                </svg>
                <span>LinkedIn</span>
              </a>

              <!-- Copy Link -->
              <button 
                type="button" 
                @click="navigator.clipboard.writeText('<?= e($shareUrl) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-surface text-ink border border-border hover:border-ink/30 transition-colors"
                aria-label="Copy link to story"
              >
                <svg class="w-3.5 h-3.5 text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101m-.758-4.899a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1"/>
                </svg>
                <span x-text="copied ? 'Copied!' : 'Copy link'">Copy link</span>
              </button>
            </div>
          </div>
        </div>

        <!-- 5. Like Button (Heart icon + count, toggle like/unlike, optimistic UI) -->
        <div 
          x-data="{
            liked: <?= !empty($userLiked) ? 'true' : 'false' ?>,
            count: <?= (int) ($post['likes'] ?? 0) ?>,
            loading: false,
            async toggleLike() {
              if (this.loading) return;
              const prevLiked = this.liked;
              const prevCount = this.count;

              // Optimistic UI update
              this.liked = !prevLiked;
              this.count = this.liked ? prevCount + 1 : Math.max(0, prevCount - 1);
              this.loading = true;

              try {
                const res = await fetch('/api/like/<?= (int) $post['id'] ?>', {
                  method: 'POST',
                  headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                  },
                  credentials: 'same-origin'
                });
                if (!res.ok) {
                  throw new Error('Network error or rate limit');
                }
                const data = await res.json();
                if (data.error) {
                  throw new Error(data.error);
                }
                this.liked = !!data.liked;
                this.count = Number(data.count);
              } catch (err) {
                // Revert optimistic update on failure
                this.liked = prevLiked;
                this.count = prevCount;
              } finally {
                this.loading = false;
              }
            }
          }"
          class="my-8 flex items-center justify-start"
        >
          <button 
            type="button"
            @click="toggleLike()"
            :disabled="loading"
            :aria-pressed="liked ? 'true' : 'false'"
            class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-sm font-semibold transition-all duration-200 shadow-soft-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary min-h-[44px]"
            :class="liked 
              ? 'bg-accent/10 border-accent/30 text-accent-strong hover:bg-accent/20' 
              : 'bg-white border-border text-ink-soft hover:border-ink/30 hover:bg-surface-alt'"
            aria-label="Like this article"
          >
            <svg 
              class="w-5 h-5 transition-transform duration-200" 
              :class="liked ? 'text-accent-strong scale-110 fill-accent-strong' : 'text-ink-muted fill-none stroke-current stroke-2'"
              viewBox="0 0 24 24" 
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
            </svg>
            <span x-text="count + (count === 1 ? ' Like' : ' Likes')"></span>
          </button>
        </div>

        <!-- 6. Donate CTA Box (Shown ONLY if donation fields are populated) -->
        <?php if ($isDonateActive): ?>
          <div class="my-10 p-6 sm:p-8 rounded-2xl bg-surface-alt border border-border text-center shadow-soft-sm">
            <h3 class="font-serif text-xl sm:text-2xl font-bold text-primary mb-2">Support Grassroots Environmental Action</h3>
            <p class="text-sm text-ink-muted max-w-xl mx-auto mb-6 leading-relaxed">
              <?= e(setting('donate_intro', 'Your contribution directly powers public interest investigations, RTI filings, wetland protection, and mangrove conservation.')) ?>
            </p>
            <a 
              href="/donate" 
              class="inline-flex items-center px-6 py-3 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
            >
              Support NatConnect
            </a>
          </div>
        <?php endif; ?>

        <!-- 7. Previous / Next Story Navigation (Published Only) -->
        <?php if ($prevPost || $nextPost): ?>
          <nav class="mt-10 pt-8 border-t border-border grid grid-cols-1 sm:grid-cols-2 gap-4" aria-label="Previous and Next Stories">
            <?php if ($prevPost): ?>
              <a 
                href="/post/<?= e($prevPost['slug']) ?>" 
                class="group p-4 rounded-xl border border-border hover:border-primary/40 bg-white hover:bg-surface-alt transition-all flex flex-col justify-between"
              >
                <span class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle mb-1 group-hover:text-primary flex items-center gap-1">
                  &larr; Previous Story
                </span>
                <span class="font-serif text-sm font-bold text-ink group-hover:text-primary leading-snug">
                  <?= e($prevPost['title']) ?>
                </span>
              </a>
            <?php else: ?>
              <div></div>
            <?php endif; ?>

            <?php if ($nextPost): ?>
              <a 
                href="/post/<?= e($nextPost['slug']) ?>" 
                class="group p-4 rounded-xl border border-border hover:border-primary/40 bg-white hover:bg-surface-alt transition-all flex flex-col justify-between text-right"
              >
                <span class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle mb-1 group-hover:text-primary flex items-center justify-end gap-1">
                  Next Story &rarr;
                </span>
                <span class="font-serif text-sm font-bold text-ink group-hover:text-primary leading-snug">
                  <?= e($nextPost['title']) ?>
                </span>
              </a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

        <!-- 8. Comments Section (Approved comments oldest first + Turnstile protected form) -->
        <section id="comments" class="mt-12 pt-8 border-t border-border" aria-label="Reader Comments">
          <div class="flex items-center justify-between mb-6">
            <h2 class="font-serif text-2xl font-bold text-ink">
              Comments (<?= (int) ($commentCount ?? count($comments ?? [])) ?>)
            </h2>
          </div>

          <!-- Success Alert -->
          <?php if (!empty($commentSuccess)): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-3">
              <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
              <span><?= e($commentSuccess) ?></span>
            </div>
          <?php endif; ?>

          <!-- Approved Comments List (Oldest First) -->
          <div class="space-y-4 mb-10">
            <?php if (!empty($comments)): ?>
              <?php foreach ($comments as $c): ?>
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-border shadow-soft-sm">
                  <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-2.5">
                      <span class="w-7 h-7 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0">
                        <?= strtoupper(substr($c['name'] ?? 'N', 0, 1)) ?>
                      </span>
                      <span class="font-semibold text-sm text-ink"><?= e($c['name']) ?></span>
                    </div>
                    <time class="text-xs text-ink-subtle" datetime="<?= date('Y-m-d H:i', strtotime($c['created_at'])) ?>">
                      <?= formatDate($c['created_at'], 'd M Y, h:i A') ?>
                    </time>
                  </div>
                  <div class="text-sm text-ink-soft leading-relaxed break-words pl-9">
                    <?= nl2br(e($c['body'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-sm text-ink-muted italic py-2">
                No comments yet. Be the first to share your perspective.
              </p>
            <?php endif; ?>
          </div>

          <!-- Add Comment Form -->
          <?php if (!empty($commentsAvailable)): ?>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <div class="p-6 sm:p-8 rounded-2xl bg-white border border-border shadow-soft-sm">
              <h3 class="font-serif text-lg font-bold text-ink mb-1">Leave a Comment</h3>
              <p class="text-xs text-ink-muted mb-6">Join the conversation. Submissions are reviewed by our team before publishing.</p>

              <!-- Form Validation Errors -->
              <?php if (!empty($commentErrors)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                  <p class="font-semibold mb-1">Please correct the following:</p>
                  <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
                    <?php foreach ($commentErrors as $err): ?>
                      <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <form action="/post/<?= e($post['slug']) ?>/comment" method="POST" class="space-y-4">
                <!-- Honeypot (hidden with CSS, not type=hidden) -->
                <div class="opacity-0 absolute -left-[9999px] -top-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                  <label for="nc_hp_7" class="sr-only">Security verification</label>
                  <input type="text" name="nc_hp_7" id="nc_hp_7" autocomplete="off" tabindex="-1" aria-hidden="true" value="" />
                </div>

                <!-- Signed timestamp for min 3s spam check -->
                <input type="hidden" name="comment_ts" value="<?= (int) ($commentTs ?? time()) ?>">
                <input type="hidden" name="comment_sig" value="<?= e($commentSig ?? '') ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Name -->
                  <div>
                    <label for="comment_name" class="block text-xs font-semibold uppercase tracking-wider text-ink-soft mb-1.5">
                      Name <span class="text-rose-500">*</span>
                    </label>
                    <input 
                      type="text" 
                      id="comment_name" 
                      name="name" 
                      required 
                      maxlength="80"
                      value="<?= e($commentForm['name'] ?? '') ?>"
                      placeholder="Your name" 
                      class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors"
                    >
                  </div>

                  <!-- Email (Never published) -->
                  <div>
                    <label for="comment_email" class="block text-xs font-semibold uppercase tracking-wider text-ink-soft mb-1.5">
                      Email <span class="text-rose-500">*</span>
                    </label>
                    <input 
                      type="email" 
                      id="comment_email" 
                      name="email" 
                      required 
                      value="<?= e($commentForm['email'] ?? '') ?>"
                      placeholder="you@example.com" 
                      class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors"
                    >
                  </div>
                </div>

                <!-- Comment Body -->
                <div>
                  <label for="comment_body" class="block text-xs font-semibold uppercase tracking-wider text-ink-soft mb-1.5">
                    Comment <span class="text-rose-500">*</span>
                  </label>
                  <textarea 
                    id="comment_body" 
                    name="comment" 
                    required 
                    minlength="3" 
                    maxlength="2000" 
                    rows="4" 
                    placeholder="Share your feedback or insights (plain text only)..." 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors resize-y"
                  ><?= e($commentForm['comment'] ?? '') ?></textarea>
                </div>

                <!-- Cloudflare Turnstile Widget -->
                <div class="pt-1">
                  <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey ?? '1x00000000000000000000AA') ?>"></div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                  <button 
                    type="submit" 
                    class="inline-flex items-center justify-center px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
                  >
                    Post comment
                  </button>
                </div>

                <!-- Privacy Notice -->
                <p class="text-xs text-ink-subtle pt-1 leading-relaxed">
                  Your email is not published. We use Cloudflare Turnstile to block spam. Learn more in our 
                  <a href="/privacy" class="text-primary hover:underline font-medium">Privacy Policy</a>.
                </p>
              </form>
            </div>
          <?php else: ?>
            <div class="p-6 rounded-2xl bg-surface-alt border border-border text-center text-sm text-ink-muted">
              Comments are temporarily unavailable.
            </div>
          <?php endif; ?>
        </section>

        <!-- 9. Mobile Only: Related & Popular Blocks (< 1024px) -->
        <?php if ($hasSidebar): ?>
          <div class="mt-12 space-y-10 lg:hidden">
            <?php if (!empty($relatedPosts)): ?>
              <section aria-label="Related Stories">
                <h2 class="font-serif text-xl font-bold text-ink mb-4 pb-2 border-b border-border">Related Stories</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <?php foreach ($relatedPosts as $rel): ?>
                    <?php \App\Core\View::partial('partials/post-card', ['post' => $rel]); ?>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>

            <?php if (!empty($popularPosts)): ?>
              <section aria-label="Popular Stories">
                <h2 class="font-serif text-xl font-bold text-ink mb-4 pb-2 border-b border-border">Popular Stories</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <?php foreach ($popularPosts as $pop): ?>
                    <?php \App\Core\View::partial('partials/post-card', ['post' => $pop]); ?>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </article>

      <!-- ========================================================= -->
      <!-- Desktop Sidebar (lg:col-span-4)                           -->
      <!-- ========================================================= -->
      <?php if ($hasSidebar): ?>
        <aside class="hidden lg:block lg:col-span-4">
          <div class="lg:sticky lg:top-6 space-y-8">

            <!-- Related Stories Section -->
            <?php if (!empty($relatedPosts)): ?>
              <section class="p-5 bg-white rounded-2xl border border-border shadow-soft-sm" aria-label="Related Stories">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-border">
                  <h2 class="font-serif text-lg font-bold text-ink">Related Stories</h2>
                  <?php if (!empty($post['category_slug'])): ?>
                    <a href="/category/<?= e($post['category_slug']) ?>" class="text-xs text-primary font-semibold hover:underline">
                      More &rarr;
                    </a>
                  <?php endif; ?>
                </div>

                <div class="space-y-4">
                  <?php foreach ($relatedPosts as $rel): ?>
                    <?php
                      $relThumb = !empty($rel['cover_image'])
                        ? ImageUploader::variantUrl($rel['cover_image'], 400)
                        : (!empty($rel['video_url']) && youtubeId($rel['video_url'])
                            ? 'https://img.youtube.com/vi/' . youtubeId($rel['video_url']) . '/hqdefault.jpg'
                            : null);
                    ?>
                    <a href="/post/<?= e($rel['slug']) ?>" class="group flex gap-3.5 items-start">
                      <div class="w-20 h-14 rounded-lg overflow-hidden bg-surface-alt shrink-0 border border-border/50">
                        <?php if ($relThumb): ?>
                          <img src="<?= e($relThumb) ?>" alt="<?= e($rel['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy" />
                        <?php else: ?>
                          <div class="w-full h-full flex items-center justify-center text-primary/40 font-serif font-bold text-xs bg-primary/5">NC</div>
                        <?php endif; ?>
                      </div>
                      <div class="flex-1 min-w-0">
                        <?php if (!empty($rel['category_name'])): ?>
                          <span class="text-[10px] font-bold text-primary uppercase tracking-wider block mb-0.5"><?= e($rel['category_name']) ?></span>
                        <?php endif; ?>
                        <h3 class="font-serif text-xs font-bold text-ink group-hover:text-primary transition-colors leading-snug">
                          <?= e($rel['title']) ?>
                        </h3>
                        <div class="text-[11px] text-ink-subtle mt-1 flex items-center gap-1.5">
                          <span><?= formatDate($rel['published_at'] ?? $rel['created_at'], 'd M') ?></span>
                          <span>&bull;</span>
                          <span><?= (int) ($rel['read_time_minutes'] ?? 1) ?> min</span>
                        </div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>

            <!-- Popular Stories Section -->
            <?php if (!empty($popularPosts)): ?>
              <section class="p-5 bg-white rounded-2xl border border-border shadow-soft-sm" aria-label="Popular Stories">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-border">
                  <h2 class="font-serif text-lg font-bold text-ink">Popular Stories</h2>
                </div>

                <div class="space-y-4">
                  <?php foreach ($popularPosts as $idx => $pop): ?>
                    <?php
                      $popThumb = !empty($pop['cover_image'])
                        ? ImageUploader::variantUrl($pop['cover_image'], 400)
                        : (!empty($pop['video_url']) && youtubeId($pop['video_url'])
                            ? 'https://img.youtube.com/vi/' . youtubeId($pop['video_url']) . '/hqdefault.jpg'
                            : null);
                    ?>
                    <a href="/post/<?= e($pop['slug']) ?>" class="group flex gap-3.5 items-start">
                      <span class="font-serif font-black text-2xl text-border-strong group-hover:text-primary transition-colors w-5 shrink-0 text-center leading-none mt-1">
                        <?= $idx + 1 ?>
                      </span>
                      <div class="w-20 h-14 rounded-lg overflow-hidden bg-surface-alt shrink-0 border border-border/50">
                        <?php if ($popThumb): ?>
                          <img src="<?= e($popThumb) ?>" alt="<?= e($pop['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy" />
                        <?php else: ?>
                          <div class="w-full h-full flex items-center justify-center text-primary/40 font-serif font-bold text-xs bg-primary/5">NC</div>
                        <?php endif; ?>
                      </div>
                      <div class="flex-1 min-w-0">
                        <?php if (!empty($pop['category_name'])): ?>
                          <span class="text-[10px] font-bold text-primary uppercase tracking-wider block mb-0.5"><?= e($pop['category_name']) ?></span>
                        <?php endif; ?>
                        <h3 class="font-serif text-xs font-bold text-ink group-hover:text-primary transition-colors leading-snug">
                          <?= e($pop['title']) ?>
                        </h3>
                        <div class="text-[11px] text-ink-subtle mt-1 flex items-center gap-1.5">
                          <span><?= formatDate($pop['published_at'] ?? $pop['created_at'], 'd M') ?></span>
                          <?php $popViews = (int) ($pop['views'] ?? 0); ?>
                          <?php if ($popViews >= 10): ?>
                            <span>&bull;</span>
                            <span><?= number_format($popViews) ?> views</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>

          </div>
        </aside>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- JS View Beacon: fires POST /api/view/{id} after 3 continuous seconds of document visibility -->
<script>
  (function() {
    let timer = null;
    let sent = false;
    const postId = <?= (int) $post['id'] ?>;

    function sendBeacon() {
      if (sent) return;
      sent = true;
      if (timer) clearTimeout(timer);
      try {
        fetch('/api/view/' + postId, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          keepalive: true
        });
      } catch (e) {}
    }

    function checkVisibility() {
      if (sent) return;
      if (document.visibilityState === 'visible') {
        if (!timer) {
          timer = setTimeout(sendBeacon, 3000);
        }
      } else {
        if (timer) {
          clearTimeout(timer);
          timer = null;
        }
      }
    }

    checkVisibility();
    document.addEventListener('visibilitychange', checkVisibility);
  })();
</script>

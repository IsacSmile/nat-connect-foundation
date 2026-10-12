<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Public Homepage
 * Design Phase D2 & Round 2 (Hero Slider, Impact Numbers, Get Involved, About Band)
 */
?>

<div class="space-y-16 sm:space-y-24">

  <!-- =========================================================================
       1. ANNOUNCEMENT BAR (Dismissible via localStorage)
       ========================================================================= -->
  <?php if (!empty($showAnnouncement)): ?>
    <div 
      x-data="{ dismissed: localStorage.getItem('nc_announcement_dismissed') === '1' }" 
      x-show="!dismissed" 
      x-cloak
      class="bg-primary text-white text-sm py-2.5 px-5 shadow-sm"
      role="region"
      aria-label="Announcement"
    >
      <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
        <div class="flex-1 text-center min-w-0">
          <?php if (!empty($announcementLink)): ?>
            <a href="<?= e($announcementLink) ?>" class="inline-flex items-center gap-1.5 hover:underline font-medium truncate">
              <span><?= e($announcementText) ?></span>
              <span aria-hidden="true">&rarr;</span>
            </a>
          <?php else: ?>
            <span class="font-medium truncate inline-block"><?= e($announcementText) ?></span>
          <?php endif; ?>
        </div>
        <button 
          type="button" 
          @click="localStorage.setItem('nc_announcement_dismissed', '1'); dismissed = true" 
          class="shrink-0 p-1 text-white/80 hover:text-white rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
          aria-label="Dismiss announcement"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>
  <?php endif; ?>

  <!-- =========================================================================
       2. HERO SECTION (Slider + Most Read + Calls To Action)
       ========================================================================= -->
  <?php if (!empty($sliderPosts)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 pt-4 sm:pt-6" aria-labelledby="hero-heading">
      <!-- Top Row: Single h1 Headline (~60%) + Intro Paragraph & CTAs (~40%) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-12 items-end pb-8 sm:pb-12 border-b border-ink/10">
        <div class="lg:col-span-7 space-y-3">
          <h1 id="hero-heading" class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal text-ink leading-tight tracking-tight">
            <?= e($heroHeadline) ?>
          </h1>
        </div>
        <div class="lg:col-span-5 space-y-4">
          <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
            <?= e($heroIntro) ?>
          </p>
          <div class="flex flex-wrap items-center gap-3 pt-1">
            <a 
              href="#get-involved" 
              class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg shadow-sm transition-colors min-h-[44px]"
            >
              Get involved
            </a>
            <?php if (!empty($isDonateActive)): ?>
              <a 
                href="/donate" 
                class="inline-flex items-center justify-center px-5 py-2.5 bg-white border border-border hover:bg-surface text-ink text-sm font-semibold rounded-lg shadow-sm transition-colors min-h-[44px]"
              >
                Donate
              </a>
            <?php endif; ?>
            <a 
              href="/stories" 
              class="inline-flex items-center gap-1 text-sm font-semibold text-accent-strong hover:text-accent-hover transition-colors py-2 px-1"
            >
              <span>Explore all stories</span>
              <span aria-hidden="true">&rarr;</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Bottom Layout: Hero Slider + Most Read -->
      <div class="mt-8 sm:mt-10">
        <?php if (!empty($mostReadPosts)): ?>
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            
            <!-- Left: Hero Slider (8 columns) -->
            <div class="lg:col-span-8 flex">
              <?php
                // Render Hero Slider
                $sliderCount = count($sliderPosts);
              ?>
              <div 
                x-data="{
                  current: 0,
                  total: <?= $sliderCount ?>,
                  autoRotate: <?= $sliderAutoRotate ? 'true' : 'false' ?>,
                  playing: <?= $sliderAutoRotate ? 'true' : 'false' ?>,
                  pausedByInteraction: false,
                  timer: null,
                  init() {
                    if (this.autoRotate) {
                      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.playing = false;
                      } else {
                        this.startAuto();
                      }
                      document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                          this.stopAuto();
                        } else if (this.playing && !this.pausedByInteraction) {
                          this.startAuto();
                        }
                      });
                    }
                  },
                  startAuto() {
                    this.stopAuto();
                    if (!this.playing || this.total <= 1) return;
                    this.timer = setInterval(() => {
                      this.next();
                    }, 7000);
                  },
                  stopAuto() {
                    if (this.timer) {
                      clearInterval(this.timer);
                      this.timer = null;
                    }
                  },
                  pause() {
                    this.pausedByInteraction = true;
                    this.stopAuto();
                  },
                  resume() {
                    this.pausedByInteraction = false;
                    if (this.playing) {
                      this.startAuto();
                    }
                  },
                  togglePlay() {
                    this.playing = !this.playing;
                    if (this.playing) {
                      this.pausedByInteraction = false;
                      this.startAuto();
                    } else {
                      this.stopAuto();
                    }
                  },
                  goTo(index) {
                    if (index < 0) index = this.total - 1;
                    if (index >= this.total) index = 0;
                    this.current = index;
                    const track = this.$refs.track;
                    if (track) {
                      const slide = track.children[index];
                      if (slide) {
                        track.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
                      }
                    }
                  },
                  prev() {
                    this.goTo((this.current - 1 + this.total) % this.total);
                  },
                  next() {
                    this.goTo((this.current + 1) % this.total);
                  },
                  onScroll() {
                    const track = this.$refs.track;
                    if (!track || track.offsetWidth <= 0) return;
                    const index = Math.round(track.scrollLeft / track.offsetWidth);
                    if (index >= 0 && index < this.total && index !== this.current) {
                      this.current = index;
                    }
                  }
                }"
                @mouseenter="pause()"
                @mouseleave="resume()"
                @focusin="pause()"
                @focusout="resume()"
                @touchstart.passive="pause()"
                @touchend.passive="resume()"
                class="relative w-full flex flex-col"
              >
                <div 
                  role="region" 
                  aria-roledescription="carousel" 
                  aria-label="Featured stories"
                  tabindex="0"
                  @keydown.left.prevent="prev()"
                  @keydown.right.prevent="next()"
                  class="relative w-full h-full rounded-lg overflow-hidden border border-ink/10 shadow-soft-sm outline-none focus-visible:ring-2 focus-visible:ring-primary flex"
                >
                  <!-- Screen reader slide counter -->
                  <div class="sr-only" aria-live="polite" x-text="`${current + 1} / ${total}`">
                    1 / <?= $sliderCount ?>
                  </div>

                  <!-- Scroll-snap track -->
                  <div 
                    x-ref="track" 
                    @scroll.debounce.50ms="onScroll()" 
                    class="flex overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth w-full h-full"
                  >
                    <?php foreach ($sliderPosts as $idx => $slide): ?>
                      <?php
                        $slideUrl = '/post/' . e($slide['slug']);
                        $slideCover = $slide['cover_image'] ?? '';
                        $slideAlt = !empty($slide['cover_alt']) ? $slide['cover_alt'] : $slide['title'];
                        $slideCategory = !empty($slide['category_name']) ? strtoupper((string) $slide['category_name']) : 'FEATURED';
                        $slideAttrs = \App\Core\ImageUploader::getResponsiveAttributes($slideCover, '(max-width: 1024px) 100vw, 66vw');
                      ?>
                      <article 
                        role="group" 
                        aria-roledescription="slide" 
                        aria-label="<?= ($idx + 1) . ' of ' . $sliderCount ?>"
                        class="min-w-full w-full shrink-0 snap-start relative aspect-[4/3] sm:aspect-[16/9] min-h-[360px] sm:min-h-[420px] bg-ink flex flex-col justify-end p-6 sm:p-10 group"
                      >
                        <?php if ($slideAttrs): ?>
                          <img 
                            src="<?= e($slideAttrs['src']) ?>" 
                            srcset="<?= e($slideAttrs['srcset']) ?>" 
                            sizes="<?= e($slideAttrs['sizes']) ?>" 
                            alt="<?= e($slideAlt) ?>" 
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                            <?= $idx === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
                          >
                        <?php elseif (!empty($slideCover)): ?>
                          <img 
                            src="<?= e(\App\Core\ImageUploader::getVariantUrl($slideCover, 1600)) ?>" 
                            alt="<?= e($slideAlt) ?>" 
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                            <?= $idx === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
                          >
                        <?php endif; ?>

                        <!-- Bottom gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/65 to-transparent pointer-events-none"></div>

                        <!-- Content overlay -->
                        <div class="relative z-10 space-y-2.5 max-w-2xl">
                          <div>
                            <span class="text-[12px] uppercase tracking-wider font-semibold text-white/90 bg-white/10 backdrop-blur-sm px-2.5 py-1 rounded inline-block">
                              <?= e($slideCategory) ?>
                            </span>
                          </div>
                          <h3 class="font-serif text-xl sm:text-2xl lg:text-3xl font-medium text-white group-hover:text-white/90 transition-colors leading-snug">
                            <a href="<?= $slideUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
                              <?= e($slide['title']) ?>
                            </a>
                          </h3>
                          <?php if (!empty($slide['excerpt'])): ?>
                            <p class="hidden sm:block text-sm sm:text-base text-white/80 line-clamp-2 leading-relaxed">
                              <?= e($slide['excerpt']) ?>
                            </p>
                          <?php endif; ?>
                          <div class="pt-1">
                            <span class="inline-flex items-center gap-1 text-sm font-semibold text-white group-hover:underline">
                              <span>Read the story</span>
                              <span aria-hidden="true">&rarr;</span>
                            </span>
                          </div>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>

                  <!-- Controls: Desktop side arrows (44px) -->
                  <?php if ($sliderCount > 1): ?>
                    <button 
                      type="button" 
                      @click="prev()" 
                      aria-label="Previous slide" 
                      class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 min-w-[44px] min-h-[44px] items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button 
                      type="button" 
                      @click="next()" 
                      aria-label="Next slide" 
                      class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 min-w-[44px] min-h-[44px] items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                  <?php endif; ?>

                  <!-- Controls: Overlaid bottom bar (Dots, Mobile arrows 44px, Pause/Play) -->
                  <?php if ($sliderCount > 1): ?>
                    <div class="absolute bottom-3 right-3 sm:right-6 z-20 flex items-center gap-2">
                      <!-- Mobile Prev (44px) -->
                      <button 
                        type="button" 
                        @click="prev()" 
                        aria-label="Previous slide" 
                        class="sm:hidden w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                      </button>

                      <!-- Dots -->
                      <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-black/40 backdrop-blur-sm">
                        <?php for ($d = 0; $d < $sliderCount; $d++): ?>
                          <button 
                            type="button" 
                            @click="goTo(<?= $d ?>)" 
                            aria-label="Go to slide <?= $d + 1 ?>" 
                            class="h-2 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            :class="current === <?= $d ?> ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
                          ></button>
                        <?php endfor; ?>
                      </div>

                      <!-- Mobile Next (44px) -->
                      <button 
                        type="button" 
                        @click="next()" 
                        aria-label="Next slide" 
                        class="sm:hidden w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                      </button>

                      <!-- Auto-rotate Pause/Play button (visible when auto-rotate is enabled) -->
                      <?php if (!empty($sliderAutoRotate)): ?>
                        <button 
                          type="button" 
                          @click="togglePlay()" 
                          :aria-label="playing ? 'Pause auto-rotation' : 'Play auto-rotation'" 
                          class="w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                        >
                          <svg x-show="playing" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
                          <svg x-show="!playing" x-cloak class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Right: Most Read (4 columns) -->
            <div class="lg:col-span-4 flex flex-col">
              <div class="rounded-lg border border-ink/10 bg-white p-6 flex flex-col justify-between h-full">
                <div>
                  <div class="border-b border-ink/10 pb-3 mb-4">
                    <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong">
                      Most read
                    </span>
                  </div>

                  <div class="divide-y divide-ink/10">
                    <?php foreach ($mostReadPosts as $mr): ?>
                      <?php
                        $mrUrl = '/post/' . e($mr['slug']);
                        $mrThumb = !empty($mr['cover_image']) ? \App\Core\ImageUploader::getVariantUrl($mr['cover_image'], 400) : '';
                        $mrAlt = !empty($mr['cover_alt']) ? $mr['cover_alt'] : $mr['title'];
                        $mrCategory = !empty($mr['category_name']) ? strtoupper((string) $mr['category_name']) : 'STORY';
                      ?>
                      <article class="group relative py-4 first:pt-0 last:pb-0 flex items-start gap-4">
                        <?php if ($mrThumb): ?>
                          <img 
                            src="<?= e($mrThumb) ?>" 
                            alt="<?= e($mrAlt) ?>" 
                            width="96" 
                            height="96" 
                            class="w-24 h-24 shrink-0 rounded-lg object-cover border border-ink/10"
                            loading="lazy"
                          >
                        <?php else: ?>
                          <div class="w-24 h-24 shrink-0 rounded-lg bg-surface border border-ink/10 flex items-center justify-center text-primary/60">
                            <span class="font-serif text-xs font-medium uppercase tracking-wider"><?= e($mrCategory) ?></span>
                          </div>
                        <?php endif; ?>

                        <div class="flex-1 min-w-0 space-y-1">
                          <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block">
                            <?= e($mrCategory) ?>
                          </span>
                          <h3 class="font-serif text-sm sm:text-base font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                            <a href="<?= $mrUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
                              <?= e($mr['title']) ?>
                            </a>
                          </h3>
                          <p class="text-xs text-ink-muted">
                            <?= formatDate($mr['published_at'] ?? $mr['created_at']) ?>
                          </p>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="pt-4 mt-4 border-t border-ink/10 text-right">
                  <a href="/stories" class="text-xs font-semibold text-primary hover:text-primary-hover inline-flex items-center gap-1">
                    <span>View all stories</span>
                    <span aria-hidden="true">&rarr;</span>
                  </a>
                </div>
              </div>
            </div>

          </div>
        <?php else: ?>
          <!-- Full Width Hero Slider if No Most Read Posts -->
          <?php $sliderCount = count($sliderPosts); ?>
          <div 
            x-data="{
              current: 0,
              total: <?= $sliderCount ?>,
              autoRotate: <?= $sliderAutoRotate ? 'true' : 'false' ?>,
              playing: <?= $sliderAutoRotate ? 'true' : 'false' ?>,
              pausedByInteraction: false,
              timer: null,
              init() {
                if (this.autoRotate) {
                  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    this.playing = false;
                  } else {
                    this.startAuto();
                  }
                  document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                      this.stopAuto();
                    } else if (this.playing && !this.pausedByInteraction) {
                      this.startAuto();
                    }
                  });
                }
              },
              startAuto() {
                this.stopAuto();
                if (!this.playing || this.total <= 1) return;
                this.timer = setInterval(() => {
                  this.next();
                }, 7000);
              },
              stopAuto() {
                if (this.timer) {
                  clearInterval(this.timer);
                  this.timer = null;
                }
              },
              pause() {
                this.pausedByInteraction = true;
                this.stopAuto();
              },
              resume() {
                this.pausedByInteraction = false;
                if (this.playing) {
                  this.startAuto();
                }
              },
              togglePlay() {
                this.playing = !this.playing;
                if (this.playing) {
                  this.pausedByInteraction = false;
                  this.startAuto();
                } else {
                  this.stopAuto();
                }
              },
              goTo(index) {
                if (index < 0) index = this.total - 1;
                if (index >= this.total) index = 0;
                this.current = index;
                const track = this.$refs.track;
                if (track) {
                  const slide = track.children[index];
                  if (slide) {
                    track.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
                  }
                }
              },
              prev() {
                this.goTo((this.current - 1 + this.total) % this.total);
              },
              next() {
                this.goTo((this.current + 1) % this.total);
              },
              onScroll() {
                const track = this.$refs.track;
                if (!track || track.offsetWidth <= 0) return;
                const index = Math.round(track.scrollLeft / track.offsetWidth);
                if (index >= 0 && index < this.total && index !== this.current) {
                  this.current = index;
                }
              }
            }"
            @mouseenter="pause()"
            @mouseleave="resume()"
            @focusin="pause()"
            @focusout="resume()"
            @touchstart.passive="pause()"
            @touchend.passive="resume()"
            class="relative w-full flex flex-col"
          >
            <div 
              role="region" 
              aria-roledescription="carousel" 
              aria-label="Featured stories"
              tabindex="0"
              @keydown.left.prevent="prev()"
              @keydown.right.prevent="next()"
              class="relative w-full rounded-lg overflow-hidden border border-ink/10 shadow-soft-sm outline-none focus-visible:ring-2 focus-visible:ring-primary flex"
            >
              <div class="sr-only" aria-live="polite" x-text="`${current + 1} / ${total}`">
                1 / <?= $sliderCount ?>
              </div>

              <div 
                x-ref="track" 
                @scroll.debounce.50ms="onScroll()" 
                class="flex overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-smooth w-full h-full"
              >
                <?php foreach ($sliderPosts as $idx => $slide): ?>
                  <?php
                    $slideUrl = '/post/' . e($slide['slug']);
                    $slideCover = $slide['cover_image'] ?? '';
                    $slideAlt = !empty($slide['cover_alt']) ? $slide['cover_alt'] : $slide['title'];
                    $slideCategory = !empty($slide['category_name']) ? strtoupper((string) $slide['category_name']) : 'FEATURED';
                    $slideAttrs = \App\Core\ImageUploader::getResponsiveAttributes($slideCover, '100vw');
                  ?>
                  <article 
                    role="group" 
                    aria-roledescription="slide" 
                    aria-label="<?= ($idx + 1) . ' of ' . $sliderCount ?>"
                    class="min-w-full w-full shrink-0 snap-start relative aspect-[4/3] sm:aspect-[16/9] min-h-[380px] sm:min-h-[480px] bg-ink flex flex-col justify-end p-6 sm:p-12 group"
                  >
                    <?php if ($slideAttrs): ?>
                      <img 
                        src="<?= e($slideAttrs['src']) ?>" 
                        srcset="<?= e($slideAttrs['srcset']) ?>" 
                        sizes="<?= e($slideAttrs['sizes']) ?>" 
                        alt="<?= e($slideAlt) ?>" 
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        <?= $idx === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
                      >
                    <?php elseif (!empty($slideCover)): ?>
                      <img 
                        src="<?= e(\App\Core\ImageUploader::getVariantUrl($slideCover, 1600)) ?>" 
                        alt="<?= e($slideAlt) ?>" 
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        <?= $idx === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>
                      >
                    <?php endif; ?>

                    <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/65 to-transparent pointer-events-none"></div>

                    <div class="relative z-10 space-y-3 max-w-3xl">
                      <div>
                        <span class="text-[12px] uppercase tracking-wider font-semibold text-white/90 bg-white/10 backdrop-blur-sm px-2.5 py-1 rounded inline-block">
                          <?= e($slideCategory) ?>
                        </span>
                      </div>
                      <h3 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-medium text-white group-hover:text-white/90 transition-colors leading-snug">
                        <a href="<?= $slideUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
                          <?= e($slide['title']) ?>
                        </a>
                      </h3>
                      <?php if (!empty($slide['excerpt'])): ?>
                        <p class="hidden sm:block text-sm sm:text-base text-white/80 line-clamp-2 leading-relaxed">
                          <?= e($slide['excerpt']) ?>
                        </p>
                      <?php endif; ?>
                      <div class="pt-1">
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-white group-hover:underline">
                          <span>Read the story</span>
                          <span aria-hidden="true">&rarr;</span>
                        </span>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

              <?php if ($sliderCount > 1): ?>
                <button 
                  type="button" 
                  @click="prev()" 
                  aria-label="Previous slide" 
                  class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 min-w-[44px] min-h-[44px] items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button 
                  type="button" 
                  @click="next()" 
                  aria-label="Next slide" 
                  class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 min-w-[44px] min-h-[44px] items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                <div class="absolute bottom-3 right-3 sm:right-6 z-20 flex items-center gap-2">
                  <button 
                    type="button" 
                    @click="prev()" 
                    aria-label="Previous slide" 
                    class="sm:hidden w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                  </button>

                  <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-black/40 backdrop-blur-sm">
                    <?php for ($d = 0; $d < $sliderCount; $d++): ?>
                      <button 
                        type="button" 
                        @click="goTo(<?= $d ?>)" 
                        aria-label="Go to slide <?= $d + 1 ?>" 
                        class="h-2 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                        :class="current === <?= $d ?> ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
                      ></button>
                    <?php endfor; ?>
                  </div>

                  <button 
                    type="button" 
                    @click="next()" 
                    aria-label="Next slide" 
                    class="sm:hidden w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                  </button>

                  <?php if (!empty($sliderAutoRotate)): ?>
                    <button 
                      type="button" 
                      @click="togglePlay()" 
                      :aria-label="playing ? 'Pause auto-rotation' : 'Play auto-rotation'" 
                      class="w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white shadow-md"
                    >
                      <svg x-show="playing" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
                      <svg x-show="!playing" x-cloak class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       3. IMPACT NUMBERS (Directly under Hero, Hidden entirely if none filled)
       ========================================================================= -->
  <?php if (!empty($impactNumbers)): ?>
    <section id="impact-numbers" class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-label="Impact figures">
      <div class="py-8 sm:py-10 border-y border-ink/10 grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 text-center">
        <?php foreach ($impactNumbers as $item): ?>
          <div class="space-y-1">
            <p class="font-serif text-3xl sm:text-4xl lg:text-5xl font-medium text-primary tracking-tight">
              <?= e($item['number']) ?>
            </p>
            <p class="text-xs sm:text-sm text-ink-muted leading-snug">
              <?= e($item['label']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       4. EXPLORE BY TOPIC
       ========================================================================= -->
  <?php if (!empty($showTopics)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-labelledby="topics-heading">
      <?php \App\Core\View::partial('partials/section-header', [
        'title'        => $titleTopics,
        'subtitle'     => $subtitleTopics,
        'viewAllUrl'   => '/stories',
        'viewAllLabel' => 'View all',
      ]); ?>

      <!-- Horizontal scroll row: starts aligned with 20px gutter, bleeds off right edge only -->
      <div 
        class="flex items-center gap-2.5 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory scroll-pl-5 pb-2 -mr-5 pr-5 sm:mr-0 sm:pr-0 sm:flex-wrap"
        tabindex="0"
        role="region"
        aria-label="Topics chips scroll row"
      >
        <!-- Active-style chip for All stories -->
        <a 
          href="/stories" 
          class="shrink-0 snap-start inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-primary text-white shadow-soft-sm transition-colors"
        >
          All stories
        </a>
        <?php foreach ($topics as $topic): ?>
          <a 
            href="/tag/<?= e($topic['slug']) ?>" 
            class="shrink-0 snap-start inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-surface border border-ink/10 text-ink hover:border-primary/40 transition-colors whitespace-nowrap"
          >
            <span><?= e($topic['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       5. LATEST STORIES
       ========================================================================= -->
  <?php if (!empty($showLatest)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-labelledby="latest-heading">
      <?php \App\Core\View::partial('partials/section-header', [
        'title'        => $titleLatest,
        'subtitle'     => $subtitleLatest,
        'viewAllUrl'   => '/stories',
        'viewAllLabel' => 'View all',
      ]); ?>

      <!-- Desktop (>= 640px): Standard 3-column card grid unchanged -->
      <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-3 sm:gap-8">
        <?php foreach ($latestPosts as $p): ?>
          <?php \App\Core\View::partial('partials/post-card', ['post' => $p]); ?>
        <?php endforeach; ?>
      </div>

      <!-- Mobile (< 640px): First post full card, rest as compact rows with 96px thumbnail -->
      <div class="sm:hidden flex flex-col">
        <?php if (!empty($latestPosts[0])): ?>
          <?php \App\Core\View::partial('partials/post-card', ['post' => $latestPosts[0]]); ?>
        <?php endif; ?>

        <?php $latestRest = array_slice($latestPosts, 1); ?>
        <?php if (!empty($latestRest)): ?>
          <div class="divide-y divide-ink/10 border-t border-ink/10 mt-6">
            <?php foreach ($latestRest as $sub): ?>
              <?php
                $subUrl = '/post/' . e($sub['slug']);
                $subThumb = !empty($sub['cover_image']) ? \App\Core\ImageUploader::getVariantUrl($sub['cover_image'], 400) : '';
                $subAlt = !empty($sub['cover_alt']) ? $sub['cover_alt'] : $sub['title'];
                $subCategory = !empty($sub['category_name']) ? strtoupper((string) $sub['category_name']) : 'STORY';
                $subRawDate = $sub['published_at'] ?? ($sub['created_at'] ?? '');
                $subDate = !empty($subRawDate) ? strtoupper(date('d M Y', strtotime((string) $subRawDate))) : '';
                $subEyebrow = $subCategory . ($subDate !== '' ? ' · ' . $subDate : '');
              ?>
              <article class="group relative py-4 flex items-start gap-4">
                <?php if ($subThumb): ?>
                  <img 
                    src="<?= e($subThumb) ?>" 
                    alt="<?= e($subAlt) ?>" 
                    width="96" 
                    height="96" 
                    class="w-24 h-24 shrink-0 rounded-lg object-cover border border-ink/10"
                    loading="lazy"
                  >
                <?php else: ?>
                  <div class="w-24 h-24 shrink-0 rounded-lg bg-surface border border-ink/10 flex items-center justify-center text-primary/60">
                    <span class="font-serif text-xs font-medium uppercase tracking-wider"><?= e($subCategory) ?></span>
                  </div>
                <?php endif; ?>

                <div class="flex-1 min-w-0 space-y-1">
                  <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block">
                    <?= e($subEyebrow) ?>
                  </span>
                  <h3 class="font-serif text-base font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                    <a href="<?= $subUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
                      <?= e($sub['title']) ?>
                    </a>
                  </h3>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       6. GET INVOLVED (id="get-involved")
       ========================================================================= -->
  <?php if (!empty($showGetInvolved)): ?>
    <section id="get-involved" class="bg-accent/5 border-y border-ink/10 py-16 sm:py-24" aria-labelledby="get-involved-heading">
      <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12">
          <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block mb-2">Take Action</span>
          <h2 id="get-involved-heading" class="font-serif text-[28px] sm:text-[36px] font-medium text-ink leading-tight">
            <?= e($titleGetInvolved) ?>
          </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
          <?php foreach ($getInvolvedCards as $idx => $card): ?>
            <article class="bg-white rounded-lg border border-ink/10 p-6 sm:p-8 flex flex-col justify-between shadow-soft-sm hover:border-primary/30 transition-colors">
              <div class="space-y-4">
                <!-- Fixed line icon in accent green: 0=hand-heart, 1=megaphone, 2=pen -->
                <div class="w-12 h-12 rounded-lg bg-surface border border-ink/10 flex items-center justify-center text-accent shrink-0">
                  <?php if ($idx === 0): ?>
                    <!-- hand-heart -->
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 14h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.8-2.8L15 13" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 5.5a2.5 2.5 0 0 0-3.5 0l-.5.5-.5-.5a2.5 2.5 0 1 0-3.5 3.5l4 4 4-4a2.5 2.5 0 0 0 0-3.5Z" />
                    </svg>
                  <?php elseif ($idx === 1): ?>
                    <!-- megaphone -->
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m3 11 18-5v12L3 14v-3z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
                    </svg>
                  <?php else: ?>
                    <!-- pen -->
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" d="m15 5 4 4"/>
                    </svg>
                  <?php endif; ?>
                </div>

                <h3 class="font-serif text-xl sm:text-2xl font-medium text-ink leading-snug">
                  <?= e($card['title']) ?>
                </h3>

                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
                  <?= e($card['text']) ?>
                </p>
              </div>

              <div class="pt-6 mt-6 border-t border-ink/10">
                <a 
                  href="<?= e($card['url']) ?>" 
                  class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg shadow-sm transition-colors min-h-[44px] w-full"
                >
                  <?= e($card['btn_label']) ?>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       7. ABOUT BAND (Warm green tint bg-accent/5)
       ========================================================================= -->
  <?php if (!empty($showAboutBand)): ?>
    <section class="bg-accent/5 border-y border-ink/10 py-16 sm:py-24" aria-labelledby="about-band-heading">
      <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
        <?php if (!empty($aboutBandPhoto)): ?>
          <!-- Layout With Photo: Photo left (4:3, rounded-lg) + Statement and Text right on desktop; photo on top on mobile -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <!-- Photo left (4:3, rounded-lg) / on top on mobile -->
            <div class="lg:col-span-5 aspect-[4/3] w-full overflow-hidden rounded-lg border border-ink/10 bg-surface shadow-soft-sm">
              <?php
                $aboutPhotoAttrs = \App\Core\ImageUploader::getResponsiveAttributes($aboutBandPhoto, '(max-width: 1024px) 100vw, 42vw');
                $aboutPhotoAlt = !empty($aboutBandPhotoAlt) ? $aboutBandPhotoAlt : 'About ' . $siteName;
              ?>
              <?php if ($aboutPhotoAttrs): ?>
                <img 
                  src="<?= e($aboutPhotoAttrs['src']) ?>" 
                  srcset="<?= e($aboutPhotoAttrs['srcset']) ?>" 
                  sizes="<?= e($aboutPhotoAttrs['sizes']) ?>" 
                  alt="<?= e($aboutPhotoAlt) ?>" 
                  class="w-full h-full object-cover"
                  loading="lazy"
                >
              <?php else: ?>
                <img 
                  src="<?= e(\App\Core\ImageUploader::getVariantUrl($aboutBandPhoto, 800)) ?>" 
                  alt="<?= e($aboutPhotoAlt) ?>" 
                  class="w-full h-full object-cover"
                  loading="lazy"
                >
              <?php endif; ?>
            </div>

            <!-- Statement + Text + Links right on desktop -->
            <div class="lg:col-span-7 space-y-6">
              <p id="about-band-heading" class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-ink leading-snug [text-wrap:balance]">
                <?= e($missionStatement) ?>
              </p>

              <?php if (!empty($footerAbout)): ?>
                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
                  <?= e($footerAbout) ?>
                </p>
              <?php else: ?>
                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
                  Dedicated to grassroots environmental activism, wetland conservation, coastal preservation, and RTI accountability across India.
                </p>
              <?php endif; ?>

              <div class="flex items-center gap-4 pt-2">
                <a 
                  href="/about" 
                  class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-white border border-border hover:bg-surface text-ink text-sm font-semibold rounded-lg shadow-sm transition-colors min-h-[44px]"
                >
                  <span>About us</span>
                  <span aria-hidden="true">&rarr;</span>
                </a>
                <a 
                  href="/contact" 
                  class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover transition-colors px-2 py-2"
                >
                  <span>Contact</span>
                  <span aria-hidden="true">&rarr;</span>
                </a>
              </div>
            </div>

          </div>
        <?php else: ?>
          <!-- Layout Without Photo: Current Two-Column Layout -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <!-- Left: Mission Statement as large serif statement -->
            <div class="lg:col-span-7 space-y-4">
              <p id="about-band-heading" class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-ink leading-snug [text-wrap:balance]">
                <?= e($missionStatement) ?>
              </p>
            </div>

            <!-- Right: Footer/About text + Links -->
            <div class="lg:col-span-5 space-y-5">
              <?php if (!empty($footerAbout)): ?>
                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
                  <?= e($footerAbout) ?>
                </p>
              <?php else: ?>
                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
                  Dedicated to grassroots environmental activism, wetland conservation, coastal preservation, and RTI accountability across India.
                </p>
              <?php endif; ?>
              <div class="flex items-center gap-4 pt-2">
                <a 
                  href="/about" 
                  class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-white border border-border hover:bg-surface text-ink text-sm font-semibold rounded-lg shadow-sm transition-colors min-h-[44px]"
                >
                  <span>About us</span>
                  <span aria-hidden="true">&rarr;</span>
                </a>
                <a 
                  href="/contact" 
                  class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover transition-colors px-2 py-2"
                >
                  <span>Contact</span>
                  <span aria-hidden="true">&rarr;</span>
                </a>
              </div>
            </div>

          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       8. CAUSES WE CARE ABOUT
       ========================================================================= -->
  <?php if (!empty($showCauses)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-labelledby="causes-heading">
      <?php \App\Core\View::partial('partials/section-header', [
        'title'        => $titleCauses,
        'subtitle'     => $subtitleCauses,
      ]); ?>

      <?php
        $causeCount = count($causes);
        $causeGridClass = 'sm:grid-cols-3';
        if ($causeCount === 1) {
            $causeGridClass = 'sm:grid-cols-1';
        } elseif ($causeCount === 2) {
            $causeGridClass = 'sm:grid-cols-2';
        }
      ?>

      <!-- Desktop: Grid adapts (1 = wide, 2 = two cols, 3 = three cols)
           Mobile: Scroll row starts aligned with gutter, bleeds off right edge only -->
      <div 
        class="flex overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-pl-5 gap-5 pb-4 -mr-5 pr-5 sm:mr-0 sm:pr-0 sm:grid <?= $causeGridClass ?> sm:gap-8"
        tabindex="0"
        role="region"
        aria-label="Causes cards scroll row"
      >
        <?php foreach ($causes as $cause): ?>
          <?php
            $causeUrl = '/tag/' . e($cause['slug']);
            $causeImg = !empty($cause['image_path']) ? \App\Core\ImageUploader::getVariantUrl($cause['image_path'], 800) : '';
            $causeAlt = !empty($cause['image_alt']) ? $cause['image_alt'] : $cause['name'];
          ?>
          <article class="<?= $causeCount === 1 ? 'w-full' : 'min-w-[85%] sm:min-w-0' ?> snap-start flex flex-col bg-white rounded-lg border border-ink/10 overflow-hidden hover:border-primary/30 transition-colors group relative">
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
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       9. CAMPAIGNS
       ========================================================================= -->
  <?php if (!empty($showCampaigns)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-labelledby="campaigns-heading">
      <?php \App\Core\View::partial('partials/section-header', [
        'title'        => $titleCampaigns,
        'subtitle'     => $subtitleCampaigns,
        'viewAllUrl'   => $campaignCategory ? '/category/' . e($campaignCategory['slug']) : '/stories',
        'viewAllLabel' => 'View all',
      ]); ?>

      <?php
        $leadCampaign = $campaignPosts[0];
        $subCampaigns = array_slice($campaignPosts, 1, 2);
        $leadUrl = '/post/' . e($leadCampaign['slug']);
        $leadCover = $leadCampaign['cover_image'] ?? '';
        $leadAlt = !empty($leadCampaign['cover_alt']) ? $leadCampaign['cover_alt'] : $leadCampaign['title'];
        $leadImg = $leadCover ? \App\Core\ImageUploader::getVariantUrl($leadCover, 800) : '';
      ?>

      <!-- Desktop Layout (>= 640px) -->
      <div class="hidden sm:block">
        <?php if (empty($subCampaigns)): ?>
          <!-- 1 item: image 7/12 + text 5/12, vertically centered, link directly under excerpt -->
          <article class="bg-white rounded-lg border border-ink/10 overflow-hidden flex flex-col md:flex-row group relative hover:border-primary/30 transition-colors">
            <div class="w-full md:w-7/12 aspect-[16/10] md:aspect-auto overflow-hidden bg-surface shrink-0">
              <?php if ($leadImg): ?>
                <img 
                  src="<?= e($leadImg) ?>" 
                  alt="<?= e($leadAlt) ?>" 
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                  loading="lazy"
                >
              <?php else: ?>
                <div class="w-full h-full min-h-[260px] flex items-center justify-center bg-surface text-primary/60 font-serif text-sm">
                  Campaign
                </div>
              <?php endif; ?>
            </div>
            <div class="w-full md:w-5/12 p-8 sm:p-10 flex flex-col justify-center space-y-4">
              <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block">
                Campaign
              </span>
              <h3 class="font-serif text-2xl lg:text-3xl font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                <a href="<?= $leadUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-[''] text-ink group-hover:text-primary transition-colors">
                  <?= e($leadCampaign['title']) ?>
                </a>
              </h3>
              <p class="text-sm sm:text-base text-ink-muted line-clamp-3 leading-relaxed">
                <?= e(!empty($leadCampaign['excerpt']) ? $leadCampaign['excerpt'] : snippet($leadCampaign['content'] ?? '', 180)) ?>
              </p>
              <div class="pt-2">
                <span class="inline-flex items-center gap-1 text-sm font-semibold text-accent-strong group-hover:text-accent-hover transition-colors">
                  <span>Read campaign</span>
                  <span aria-hidden="true">&rarr;</span>
                </span>
              </div>
            </div>
          </article>
        <?php else: ?>
          <!-- 2-3 items: Current layout (Lead card lg:col-span-8 + compact cards on right lg:col-span-4) -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-stretch">
            <article class="lg:col-span-8 bg-white rounded-lg border border-ink/10 overflow-hidden flex flex-col md:flex-row group relative hover:border-primary/30 transition-colors">
              <div class="w-full md:w-1/2 aspect-[16/10] md:aspect-auto overflow-hidden bg-surface shrink-0">
                <?php if ($leadImg): ?>
                  <img 
                    src="<?= e($leadImg) ?>" 
                    alt="<?= e($leadAlt) ?>" 
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                  >
                <?php else: ?>
                  <div class="w-full h-full min-h-[220px] flex items-center justify-center bg-surface text-primary/60 font-serif text-sm">
                    Campaign
                  </div>
                <?php endif; ?>
              </div>
              <div class="p-6 sm:p-8 flex flex-col justify-between flex-1">
                <div class="space-y-2.5">
                  <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block">
                    Campaign
                  </span>
                  <h3 class="font-serif text-xl sm:text-2xl font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                    <a href="<?= $leadUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-[''] text-ink group-hover:text-primary transition-colors">
                      <?= e($leadCampaign['title']) ?>
                    </a>
                  </h3>
                  <p class="text-sm text-ink-muted line-clamp-3 leading-relaxed">
                    <?= e(!empty($leadCampaign['excerpt']) ? $leadCampaign['excerpt'] : snippet($leadCampaign['content'] ?? '', 160)) ?>
                  </p>
                </div>
                <div class="pt-4 mt-4 border-t border-ink/10 flex items-center gap-1 text-sm font-semibold text-accent-strong group-hover:text-accent-hover transition-colors">
                  <span>Read campaign</span>
                  <span aria-hidden="true">&rarr;</span>
                </div>
              </div>
            </article>

            <!-- Up to 2 compact cards on right -->
            <div class="lg:col-span-4 flex flex-col gap-6">
              <?php foreach ($subCampaigns as $sub): ?>
                <?php $subUrl = '/post/' . e($sub['slug']); ?>
                <article class="bg-accent/5 rounded-lg border border-ink/10 p-6 flex flex-col justify-between flex-1 group relative hover:border-accent-strong/40 transition-colors">
                  <div class="space-y-2">
                    <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block">
                      Campaign
                    </span>
                    <h3 class="font-serif text-lg font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                      <a href="<?= $subUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-[''] text-ink group-hover:text-primary transition-colors">
                        <?= e($sub['title']) ?>
                      </a>
                    </h3>
                    <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed">
                      <?= e(!empty($sub['excerpt']) ? $sub['excerpt'] : snippet($sub['content'] ?? '', 120)) ?>
                    </p>
                  </div>
                  <div class="pt-3 mt-3 border-t border-ink/10 flex items-center gap-1 text-sm font-semibold text-accent-strong group-hover:text-accent-hover transition-colors">
                    <span>Read</span>
                    <span aria-hidden="true">&rarr;</span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Mobile Layout (< 640px): First post full card, rest as compact rows -->
      <div class="sm:hidden flex flex-col">
        <article class="bg-white rounded-lg border border-ink/10 overflow-hidden flex flex-col group relative hover:border-primary/30 transition-colors">
          <div class="w-full aspect-[16/10] overflow-hidden bg-surface shrink-0">
            <?php if ($leadImg): ?>
              <img 
                src="<?= e($leadImg) ?>" 
                alt="<?= e($leadAlt) ?>" 
                class="w-full h-full object-cover"
                loading="lazy"
              >
            <?php else: ?>
              <div class="w-full h-full min-h-[200px] flex items-center justify-center bg-surface text-primary/60 font-serif text-sm">
                Campaign
              </div>
            <?php endif; ?>
          </div>
          <div class="p-5 flex flex-col flex-1">
            <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong mb-2 block">
              Campaign
            </span>
            <h3 class="font-serif text-xl font-medium text-ink group-hover:text-primary transition-colors leading-snug mb-2">
              <a href="<?= $leadUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-[''] text-ink group-hover:text-primary transition-colors">
                <?= e($leadCampaign['title']) ?>
              </a>
            </h3>
            <p class="text-sm text-ink-muted line-clamp-3 leading-relaxed mb-4">
              <?= e(!empty($leadCampaign['excerpt']) ? $leadCampaign['excerpt'] : snippet($leadCampaign['content'] ?? '', 160)) ?>
            </p>
            <div class="mt-auto pt-3 border-t border-ink/10 flex items-center gap-1 text-sm font-semibold text-accent-strong">
              <span>Read campaign</span>
              <span aria-hidden="true">&rarr;</span>
            </div>
          </div>
        </article>

        <?php if (!empty($subCampaigns)): ?>
          <div class="divide-y divide-ink/10 border-t border-ink/10 mt-6">
            <?php foreach ($subCampaigns as $sub): ?>
              <?php
                $subUrl = '/post/' . e($sub['slug']);
                $subThumb = !empty($sub['cover_image']) ? \App\Core\ImageUploader::getVariantUrl($sub['cover_image'], 400) : '';
                $subAlt = !empty($sub['cover_alt']) ? $sub['cover_alt'] : $sub['title'];
                $subRawDate = $sub['published_at'] ?? ($sub['created_at'] ?? '');
                $subDate = !empty($subRawDate) ? strtoupper(date('d M Y', strtotime((string) $subRawDate))) : '';
                $subEyebrow = 'CAMPAIGN' . ($subDate !== '' ? ' · ' . $subDate : '');
              ?>
              <article class="group relative py-4 flex items-start gap-4">
                <?php if ($subThumb): ?>
                  <img 
                    src="<?= e($subThumb) ?>" 
                    alt="<?= e($subAlt) ?>" 
                    width="96" 
                    height="96" 
                    class="w-24 h-24 shrink-0 rounded-lg object-cover border border-ink/10"
                    loading="lazy"
                  >
                <?php else: ?>
                  <div class="w-24 h-24 shrink-0 rounded-lg bg-surface border border-ink/10 flex items-center justify-center text-primary/60">
                    <span class="font-serif text-xs font-medium uppercase tracking-wider">Campaign</span>
                  </div>
                <?php endif; ?>

                <div class="flex-1 min-w-0 space-y-1">
                  <span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong block">
                    <?= e($subEyebrow) ?>
                  </span>
                  <h3 class="font-serif text-base font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                    <a href="<?= $subUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-[''] text-ink group-hover:text-primary transition-colors">
                      <?= e($sub['title']) ?>
                    </a>
                  </h3>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       10. RIGHT TO INFORMATION
       ========================================================================= -->
  <?php if (!empty($showRti)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-labelledby="rti-heading">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        
        <!-- Left Column: Title + Subtitle/Intro + Link -->
        <div class="lg:col-span-5 space-y-4">
          <h2 id="rti-heading" class="font-serif text-[28px] sm:text-[36px] font-medium text-ink leading-tight">
            <?= e($titleRti) ?>
          </h2>
          <?php $effectiveRtiIntro = !empty($subtitleRti) ? $subtitleRti : $introRti; ?>
          <?php if (!empty($effectiveRtiIntro)): ?>
            <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
              <?= e($effectiveRtiIntro) ?>
            </p>
          <?php endif; ?>
          <div class="pt-2">
            <a 
              href="<?= $rtiCategory ? '/category/' . e($rtiCategory['slug']) : '/stories' ?>" 
              class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-hover transition-colors"
            >
              <span>View all</span>
              <span aria-hidden="true">&rarr;</span>
            </a>
          </div>
        </div>

        <!-- Right Column: Up to 2 RTI category posts as text cards with document icon (no images) -->
        <div class="lg:col-span-7 flex flex-col sm:flex-row gap-6">
          <?php foreach ($rtiPosts as $rtiPost): ?>
            <?php $rtiUrl = '/post/' . e($rtiPost['slug']); ?>
            <article class="bg-white rounded-lg border border-ink/10 p-6 flex flex-col justify-between flex-1 group relative hover:border-primary/30 transition-colors">
              <div class="space-y-3">
                <!-- Document Icon -->
                <div class="w-10 h-10 rounded-md bg-surface border border-ink/10 flex items-center justify-center text-primary shrink-0">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                  </svg>
                </div>
                <h3 class="font-serif text-lg sm:text-xl font-medium text-ink group-hover:text-primary transition-colors leading-snug">
                  <a href="<?= $rtiUrl ?>" class="focus:outline-none after:absolute after:inset-0 after:content-['']">
                    <?= e($rtiPost['title']) ?>
                  </a>
                </h3>
                <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed">
                  <?= e(!empty($rtiPost['excerpt']) ? $rtiPost['excerpt'] : snippet($rtiPost['content'] ?? '', 120)) ?>
                </p>
              </div>
              <div class="pt-3 mt-4 border-t border-ink/10 flex items-center gap-1 text-sm font-semibold text-accent-strong group-hover:text-accent-hover transition-colors">
                <span>Read</span>
                <span aria-hidden="true">&rarr;</span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       11. FROM THE GALLERY
       ========================================================================= -->
  <?php if (!empty($showGallery)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8" aria-labelledby="gallery-heading">
      <?php \App\Core\View::partial('partials/section-header', [
        'title'        => $titleGallery,
        'subtitle'     => $subtitleGallery,
        'viewAllUrl'   => '/gallery',
        'viewAllLabel' => 'View all',
      ]); ?>

      <!-- Desktop 3 columns; Mobile: horizontal scroll row starting aligned with gutter, bleeding off right edge only -->
      <div 
        class="flex overflow-x-auto no-scrollbar snap-x snap-mandatory scroll-pl-5 gap-5 pb-4 -mr-5 pr-5 sm:mr-0 sm:pr-0 sm:grid sm:grid-cols-2 lg:grid-cols-3 sm:gap-8"
        tabindex="0"
        role="region"
        aria-label="Gallery photos scroll row"
      >
        <?php foreach ($galleryPhotos as $photo): ?>
          <?php
            $photoImg = \App\Core\ImageUploader::getVariantUrl($photo['image_path'], 800);
            $photoAlt = !empty($photo['alt_text']) ? $photo['alt_text'] : 'Conservation photograph';
          ?>
          <div class="min-w-[85%] sm:min-w-0 snap-start group">
            <div class="aspect-[4/3] w-full overflow-hidden rounded-lg border border-ink/10 bg-surface">
              <img 
                src="<?= e($photoImg) ?>" 
                alt="<?= e($photoAlt) ?>" 
                width="800" 
                height="600" 
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy"
              >
            </div>
            <?php if (!empty($photo['caption'])): ?>
              <p class="text-sm text-ink leading-snug mt-2.5 line-clamp-2">
                <?= e($photo['caption']) ?>
              </p>
            <?php else: ?>
              <p class="text-sm text-ink-muted leading-snug mt-2.5 line-clamp-2">
                <?= e($photoAlt) ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       12. STAY INFORMED (Newsletter Subscription)
       ========================================================================= -->
  <?php if (!empty($showNewsletter)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 border-t border-ink/10 pt-12 sm:pt-16" aria-labelledby="newsletter-heading">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        
        <!-- Left: Section Title + Newsletter Intro -->
        <div class="lg:col-span-5 space-y-3">
          <h2 id="newsletter-heading" class="font-serif text-[28px] sm:text-[36px] font-medium text-ink leading-tight">
            <?= e($titleNewsletter) ?>
          </h2>
          <?php if (!empty($subtitleNewsletter)): ?>
            <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
              <?= e($subtitleNewsletter) ?>
            </p>
          <?php endif; ?>
          <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
            <?= e($introNewsletter) ?>
          </p>
        </div>

        <!-- Right: Subscription Form -->
        <div 
          class="lg:col-span-7"
          x-data="{
            email: '',
            consent: false,
            loading: false,
            message: '',
            error: '',
            submitted: false,
            async submitForm(e) {
              this.loading = true;
              this.message = '';
              this.error = '';
              const form = e.target;
              const formData = new FormData(form);

              try {
                const res = await fetch('/subscribe', {
                  method: 'POST',
                  headers: { 'Accept': 'application/json' },
                  body: formData
                });
                const data = await res.json();
                if (res.ok && data.success) {
                  this.message = data.message || 'Thanks! You\'re on the list.';
                  this.submitted = true;
                  this.email = '';
                  this.consent = false;
                } else {
                  this.error = data.error || 'Unable to subscribe. Please try again.';
                }
              } catch (err) {
                this.error = 'Network error. Please try again later.';
              } finally {
                this.loading = false;
              }
            }
          }"
        >
          <!-- Native Session Flash Messages -->
          <?php if (!empty($_SESSION['flash_sub_success'])): ?>
            <div class="p-4 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
              <?= e($_SESSION['flash_sub_success']) ?>
            </div>
            <?php unset($_SESSION['flash_sub_success']); ?>
          <?php endif; ?>
          <?php if (!empty($_SESSION['flash_sub_error'])): ?>
            <div class="p-4 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm">
              <?= e($_SESSION['flash_sub_error']) ?>
            </div>
            <?php unset($_SESSION['flash_sub_error']); ?>
          <?php endif; ?>

          <!-- Inline Alpine Feedback -->
          <div x-show="message" x-cloak class="p-4 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
            <span x-text="message"></span>
          </div>
          <div x-show="error" x-cloak class="p-4 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium">
            <span x-text="error"></span>
          </div>

          <form 
            action="/subscribe" 
            method="POST" 
            @submit.prevent="submitForm" 
            class="space-y-4"
            x-show="!submitted"
          >
            <?= \App\Core\Csrf::field() ?>
            <?= subscriberTimestampFields() ?>

            <!-- Accessible sr-only Spam Honeypot -->
            <div class="sr-only" aria-hidden="true">
              <label for="home_sub_hp_7">Leave empty</label>
              <input type="text" id="home_sub_hp_7" name="sub_hp_7" tabindex="-1" autocomplete="off" value="">
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
              <input 
                type="email" 
                name="email" 
                x-model="email" 
                required 
                maxlength="254" 
                placeholder="Enter your email address" 
                class="flex-1 px-4 py-3 rounded-lg border border-ink/10 focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-muted bg-white"
              >
              <button 
                type="submit" 
                :disabled="loading" 
                class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-primary hover:bg-primary-hover text-white text-sm font-semibold transition-colors disabled:opacity-50 shrink-0"
              >
                <span x-show="!loading">Subscribe</span>
                <span x-show="loading" x-cloak>Subscribing...</span>
              </button>
            </div>

            <!-- Required Consent Checkbox -->
            <div class="flex items-start gap-2.5 pt-1">
              <input 
                type="checkbox" 
                id="home_consent" 
                name="consent" 
                value="1" 
                x-model="consent" 
                required 
                class="mt-1 w-4 h-4 rounded border-ink/20 text-primary focus:ring-primary"
              >
              <label for="home_consent" class="text-xs text-ink-muted leading-relaxed">
                I agree to receive updates from <?= e($siteName) ?>. See our <a href="/privacy" class="text-primary hover:underline font-medium">privacy policy</a>.
              </label>
            </div>
          </form>
        </div>

      </div>
    </section>
  <?php endif; ?>

  <!-- =========================================================================
       13. DONATE BAND (Only if active & toggle on)
       ========================================================================= -->
  <?php if (!empty($showDonate)): ?>
    <section class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 pb-8" aria-labelledby="donate-band-heading">
      <div class="bg-primary text-white rounded-lg p-8 sm:p-12 shadow-soft flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="space-y-3 max-w-2xl text-center md:text-left">
          <span class="text-[12px] uppercase tracking-wider font-semibold text-white/80 block">
            <?= e($titleDonate) ?>
          </span>
          <h2 id="donate-band-heading" class="font-serif text-2xl sm:text-3xl font-normal text-white leading-snug">
            <?= !empty($textDonate) ? e($textDonate) : 'Stand with us to protect wetlands, natural habitats, and coastal biodiversity.' ?>
          </h2>
        </div>
        <div class="shrink-0 flex items-center gap-6">
          <a 
            href="/donate" 
            class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary hover:bg-white/90 text-sm font-semibold rounded-lg shadow-sm transition-colors"
          >
            Donate now &rarr;
          </a>
          <a 
            href="/contact" 
            class="text-white/80 hover:text-white underline text-sm transition-colors"
          >
            Contact us
          </a>
        </div>
      </div>
    </section>
  <?php endif; ?>

</div>

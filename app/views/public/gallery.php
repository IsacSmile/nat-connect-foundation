<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Photo Gallery View (Phase 4C)
 *
 * Variables:
 * - $photos: array of gallery items from GalleryModel
 * - $pagination: array{current: int, total: int, count: int, baseUrl: string}
 */

use App\Core\ImageUploader;

$lightboxData = array_map(function ($item) {
    return [
        'id'      => (int) $item['id'],
        'src'     => ImageUploader::variantUrl($item['image_path'], 1600),
        'alt'     => $item['alt_text'],
        'caption' => $item['caption'] ?? '',
    ];
}, $photos);
?>

<div 
  class="py-8 md:py-12"
  x-data="publicGallery({
    photos: <?= json_encode($lightboxData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
  })"
  @keydown.escape.window="closeLightbox()"
  @keydown.left.window="prevPhoto()"
  @keydown.right.window="nextPhoto()"
>
  <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">

    <!-- Header Section (Left-aligned, Fraunces weight 500, setting intro or empty) -->
    <div class="border-b border-ink/10 pb-6 mb-8 sm:mb-12">
      <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-medium text-ink tracking-tight mb-3">
        Photo Gallery
      </h1>
      <?php if (!empty($galleryIntro)): ?>
        <p class="text-base sm:text-lg text-ink-muted leading-relaxed max-w-3xl">
          <?= e($galleryIntro) ?>
        </p>
      <?php endif; ?>
    </div>

    <!-- Empty State -->
    <?php if (empty($photos)): ?>
      <div class="p-16 text-center rounded-lg border border-dashed border-border bg-white shadow-soft-sm max-w-lg mx-auto">
        <svg class="w-12 h-12 text-ink-subtle mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
          <circle cx="9" cy="9" r="2"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
        </svg>
        <h2 class="font-serif text-xl font-bold text-ink mb-1">No photos in the gallery yet</h2>
        <p class="text-sm text-ink-muted">Field photos and conservation documentation will be uploaded shortly.</p>
      </div>
    <?php else: ?>

      <!-- Responsive Grid: 2 cols mobile, 3 cols tablet, 4 cols desktop -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
        <?php foreach ($photos as $index => $photo): ?>
          <?php
            $thumbUrl = ImageUploader::variantUrl($photo['image_path'], 400);
          ?>
          <button 
            type="button" 
            @click="openLightbox(<?= $index ?>)" 
            class="group relative aspect-square rounded-lg overflow-hidden bg-surface-alt border border-border shadow-soft-sm hover:shadow-soft-lg transition-all focus-visible:ring-2 focus-visible:ring-primary text-left cursor-pointer"
            aria-label="View photo: <?= e($photo['alt_text']) ?>"
          >
            <img 
              src="<?= e($thumbUrl) ?>" 
              alt="<?= e($photo['alt_text']) ?>" 
              class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              loading="lazy"
            >

            <!-- Hover Caption Preview Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/20 to-transparent opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-opacity p-3 sm:p-4 flex flex-col justify-end">
              <p class="text-xs sm:text-sm text-white font-medium line-clamp-2 leading-snug">
                <?= e(!empty($photo['caption']) ? $photo['caption'] : $photo['alt_text']) ?>
              </p>
            </div>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Server-side Pagination & Load More Controls (Works without JS) -->
      <?php if ($pagination['total'] > 1): ?>
        <nav class="mt-12 flex items-center justify-center gap-3" aria-label="Gallery Pagination">
          <?php if ($pagination['current'] > 1): ?>
            <a 
              href="/gallery?page=<?= $pagination['current'] - 1 ?>" 
              class="inline-flex items-center px-4 py-2.5 bg-white hover:bg-surface-alt text-ink font-semibold text-sm rounded-lg border border-border shadow-soft-sm transition-colors min-h-[44px]"
            >
              &larr; Previous
            </a>
          <?php endif; ?>

          <?php if ($pagination['current'] < $pagination['total']): ?>
            <a 
              href="/gallery?page=<?= $pagination['current'] + 1 ?>" 
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold text-sm rounded-lg shadow-soft transition-colors min-h-[44px]"
            >
              <span>Load more photos</span>
              <span class="text-xs opacity-80">(Page <?= $pagination['current'] + 1 ?> of <?= $pagination['total'] ?>)</span>
            </a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>

  <!-- Accessible Alpine.js Lightbox Modal (No library) -->
  <div 
    x-show="isOpen" 
    x-cloak 
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6" 
    role="dialog" 
    aria-modal="true" 
    :aria-label="currentPhoto ? currentPhoto.alt : 'Photo lightbox'"
    x-ref="lightboxModal"
    tabindex="-1"
    @keydown.tab="handleTab($event)"
    @touchstart="handleTouchStart($event)"
    @touchend="handleTouchEnd($event)"
  >
    <!-- Backdrop Overlay -->
    <div 
      class="fixed inset-0 bg-ink/90 backdrop-blur-md transition-opacity"
      @click="closeLightbox()"
      aria-hidden="true"
    ></div>

    <!-- Modal Content Card -->
    <div 
      class="relative z-10 max-w-5xl w-full max-h-[92vh] flex flex-col items-center justify-center"
      @click.stop
    >
      <!-- Top Bar: Index counter & Close Button -->
      <div class="w-full flex items-center justify-between text-white/90 pb-3 px-2">
        <span class="text-xs font-mono tracking-wider font-semibold">
          <span x-text="currentIndex + 1"></span> / <span x-text="photos.length"></span>
        </span>

        <button 
          type="button" 
          @click="closeLightbox()" 
          class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors focus-visible:ring-2 focus-visible:ring-white min-h-[44px] min-w-[44px] flex items-center justify-center"
          aria-label="Close lightbox"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Main Image Container with Next / Prev Overlay Controls -->
      <div class="relative w-full flex items-center justify-center overflow-hidden rounded-lg bg-black/60 shadow-2xl">
        <template x-if="currentPhoto">
          <img 
            :src="currentPhoto.src" 
            :alt="currentPhoto.alt" 
            class="max-h-[70vh] sm:max-h-[75vh] w-auto max-w-full object-contain mx-auto select-none"
            loading="eager"
          >
        </template>

        <!-- Previous Button -->
        <button 
          type="button" 
          @click="prevPhoto()" 
          class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 p-2.5 rounded-full bg-black/40 hover:bg-black/70 text-white transition-all focus-visible:ring-2 focus-visible:ring-white min-h-[44px] min-w-[44px] flex items-center justify-center shadow-lg"
          aria-label="Previous photo"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
          </svg>
        </button>

        <!-- Next Button -->
        <button 
          type="button" 
          @click="nextPhoto()" 
          class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 p-2.5 rounded-full bg-black/40 hover:bg-black/70 text-white transition-all focus-visible:ring-2 focus-visible:ring-white min-h-[44px] min-w-[44px] flex items-center justify-center shadow-lg"
          aria-label="Next photo"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
          </svg>
        </button>
      </div>

      <!-- Caption Box below photo -->
      <div class="w-full pt-3 px-2 text-center text-white/90">
        <template x-if="currentPhoto && currentPhoto.caption">
          <p class="text-sm sm:text-base font-medium max-w-2xl mx-auto leading-relaxed" x-text="currentPhoto.caption"></p>
        </template>
        <template x-if="currentPhoto && !currentPhoto.caption">
          <p class="text-xs sm:text-sm text-white/70 max-w-2xl mx-auto italic" x-text="currentPhoto.alt"></p>
        </template>
      </div>

    </div>
  </div>

</div>

<script>
function publicGallery(config) {
  return {
    isOpen: false,
    currentIndex: 0,
    photos: config.photos || [],
    touchStartX: 0,
    touchEndX: 0,
    lastFocusedElement: null,

    get currentPhoto() {
      if (this.photos.length === 0) return null;
      return this.photos[this.currentIndex] || null;
    },

    openLightbox(index) {
      if (index >= 0 && index < this.photos.length) {
        this.lastFocusedElement = document.activeElement;
        this.currentIndex = index;
        this.isOpen = true;
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => {
          const closeBtn = this.$refs.lightboxModal?.querySelector('button[aria-label="Close lightbox"]');
          if (closeBtn) {
            closeBtn.focus();
          } else {
            this.$refs.lightboxModal?.focus();
          }
        });
      }
    },

    closeLightbox() {
      if (!this.isOpen) return;
      this.isOpen = false;
      document.body.classList.remove('overflow-hidden');
      if (this.lastFocusedElement && typeof this.lastFocusedElement.focus === 'function') {
        this.lastFocusedElement.focus();
      }
    },

    prevPhoto() {
      if (!this.isOpen || this.photos.length <= 1) return;
      this.currentIndex = (this.currentIndex > 0) ? this.currentIndex - 1 : this.photos.length - 1;
    },

    nextPhoto() {
      if (!this.isOpen || this.photos.length <= 1) return;
      this.currentIndex = (this.currentIndex < this.photos.length - 1) ? this.currentIndex + 1 : 0;
    },

    handleTab(e) {
      if (!this.isOpen || !this.$refs.lightboxModal) return;
      const focusable = Array.from(this.$refs.lightboxModal.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'));
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === first || document.activeElement === this.$refs.lightboxModal) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    },

    handleTouchStart(e) {
      if (e.changedTouches && e.changedTouches[0]) {
        this.touchStartX = e.changedTouches[0].screenX;
      }
    },

    handleTouchEnd(e) {
      if (e.changedTouches && e.changedTouches[0]) {
        this.touchEndX = e.changedTouches[0].screenX;
        const diff = this.touchStartX - this.touchEndX;
        if (Math.abs(diff) > 45) {
          if (diff > 0) {
            this.nextPhoto(); // swipe left -> next
          } else {
            this.prevPhoto(); // swipe right -> prev
          }
        }
      }
    }
  };
}
</script>

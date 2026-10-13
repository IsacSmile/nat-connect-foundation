<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Admin Gallery View (Phase 4C)
 *
 * Variables:
 * - $items: array of gallery items from GalleryModel
 */

use App\Core\Csrf;
use App\Core\ImageUploader;

$csrfToken = Csrf::token();
$totalItems = count($items);
?>

<div 
  class="max-w-7xl mx-auto space-y-8"
  x-data="adminGallery({
    csrfToken: '<?= e($csrfToken) ?>'
  })"
>

  <!-- Page Header (Rule 18: Plain title, no subtitle) -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
    <div class="flex items-center gap-3">
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink tracking-tight">Gallery</h1>
      <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
        <?= $totalItems ?> <?= $totalItems === 1 ? 'photo' : 'photos' ?>
      </span>
    </div>

    <div>
      <button 
        type="button" 
        @click="triggerUpload()" 
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Upload photos</span>
      </button>
      <input 
        type="file" 
        multiple 
        accept="image/jpeg,image/png,image/webp" 
        @change="handleFiles($event)" 
        x-ref="fileInput" 
        class="hidden"
      >
    </div>
  </div>

  <!-- Flash Messages -->
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="p-4 rounded-xl bg-accent/10 border border-accent/20 text-accent-strong text-sm font-medium flex items-center justify-between">
      <span><?= e($_SESSION['flash_success']) ?></span>
      <button type="button" @click="$el.parentElement.remove()" class="text-accent-strong hover:text-accent-hover p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium flex items-center justify-between">
      <span><?= e($_SESSION['flash_error']) ?></span>
      <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <!-- Description for these photos (Required before uploading) -->
  <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm space-y-2">
    <label for="batch_description" class="block text-xs font-bold uppercase tracking-wider text-ink">
      Description for these photos <span class="text-rose-500">*</span>
    </label>
    <div>
      <input 
        type="text" 
        id="batch_description"
        x-model="batchDescription"
        placeholder="Describe what is in these photos (e.g. Volunteers planting mangroves at Panje wetland)" 
        class="w-full px-4 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
        @keydown.enter.prevent="triggerUpload()"
      >
    </div>
    <p class="text-xs text-ink-subtle">
      Required before selecting or dropping files. Applied as alt text to every photo in the batch. Each photo stays editable afterwards.
    </p>
  </div>

  <!-- Multi-File Upload Drag & Drop Dropzone -->
  <div 
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="handleDrop($event)"
    :class="isDragging ? 'border-primary bg-primary/5' : 'border-border bg-white hover:border-primary/50'"
    class="border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer shadow-soft-sm"
    @click="triggerUpload()"
  >
    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
      <div class="w-14 h-14 rounded-2xl bg-surface-alt flex items-center justify-center text-primary mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.5V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
      </div>
      <p class="text-sm font-semibold text-ink mb-1">Select or drop photos here</p>
      <p class="text-xs text-ink-subtle">Upload multiple JPEG, PNG, or WebP images up to 8 MB each. EXIF metadata is stripped automatically.</p>
    </div>
  </div>

  <!-- Upload Queue & Progress Display -->
  <div x-show="queue.length > 0" x-cloak class="p-5 rounded-2xl bg-white border border-border shadow-soft-sm space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse" x-show="isUploading"></div>
        <span class="text-sm font-semibold text-ink" x-text="queueStatusText"></span>
      </div>
      <button 
        type="button" 
        x-show="!isUploading" 
        @click="clearCompletedQueue()" 
        class="text-xs font-semibold text-primary hover:underline"
      >
        Clear queue
      </button>
    </div>

    <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
      <template x-for="(fileItem, idx) in queue" :key="idx">
        <div class="p-3 rounded-xl border border-border/80 bg-surface-alt text-xs flex items-center justify-between gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-1">
              <span class="font-medium text-ink truncate" x-text="fileItem.name"></span>
              <span class="text-ink-subtle font-mono text-[11px]" x-text="fileItem.statusText"></span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-border rounded-full h-1.5 overflow-hidden">
              <div 
                class="h-full transition-all duration-150"
                :class="fileItem.error ? 'bg-rose-500' : (fileItem.progress === 100 ? 'bg-accent-strong' : 'bg-primary')"
                :style="'width: ' + fileItem.progress + '%'"
              ></div>
            </div>
            <template x-if="fileItem.error">
              <p class="text-[11px] text-rose-600 font-medium mt-1" x-text="fileItem.error"></p>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Photos Grid -->
  <?php if (empty($items)): ?>
    <div class="p-12 text-center rounded-2xl border border-dashed border-border bg-white shadow-soft-sm">
      <svg class="w-12 h-12 text-ink-subtle mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
        <circle cx="9" cy="9" r="2"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
      </svg>
      <h3 class="font-serif text-lg font-bold text-ink mb-1">No photos in the gallery yet</h3>
      <p class="text-xs text-ink-muted mb-4 max-w-sm mx-auto">Upload your first batch of environmental photos to showcase conservation action.</p>
      <button 
        type="button" 
        @click="triggerUpload()" 
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors"
      >
        Select photos
      </button>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php foreach ($items as $index => $item): ?>
        <?php
          $thumbUrl = ImageUploader::variantUrl($item['image_path'], 400);
          $isFirst = ($index === 0);
          $isLast = ($index === $totalItems - 1);
        ?>
        <div class="bg-white rounded-2xl border border-border shadow-soft-sm overflow-hidden flex flex-col justify-between group">
          <!-- Thumbnail & Quick Actions -->
          <div class="relative aspect-square w-full bg-surface-alt overflow-hidden border-b border-border">
            <img 
              src="<?= e($thumbUrl) ?>" 
              alt="<?= e($item['alt_text']) ?>" 
              class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
              loading="lazy"
            >

            <!-- Reorder Controls Overlay -->
            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 bg-white/95 backdrop-blur rounded-xl p-1 shadow-soft border border-border">
              <!-- Move Up -->
              <form action="/admin/gallery/<?= (int) $item['id'] ?>/move" method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="direction" value="up">
                <button 
                  type="submit" 
                  <?= $isFirst ? 'disabled' : '' ?>
                  class="p-1.5 rounded-lg text-ink-muted hover:text-primary hover:bg-surface disabled:opacity-30 disabled:pointer-events-none transition-colors"
                  title="Move up"
                  aria-label="Move up"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/>
                  </svg>
                </button>
              </form>

              <!-- Move Down -->
              <form action="/admin/gallery/<?= (int) $item['id'] ?>/move" method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="direction" value="down">
                <button 
                  type="submit" 
                  <?= $isLast ? 'disabled' : '' ?>
                  class="p-1.5 rounded-lg text-ink-muted hover:text-primary hover:bg-surface disabled:opacity-30 disabled:pointer-events-none transition-colors"
                  title="Move down"
                  aria-label="Move down"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                  </svg>
                </button>
              </form>
            </div>
          </div>

          <!-- Metadata Edit Form -->
          <form 
            action="/admin/gallery/<?= (int) $item['id'] ?>/edit" 
            method="POST" 
            class="p-4 space-y-3 flex-1 flex flex-col justify-between"
            @submit="validateItemForm($event)"
          >
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="space-y-3">
              <!-- Required Alt Text -->
              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-ink-muted mb-1">
                  Photo description (alt text) <span class="text-rose-500">*</span>
                </label>
                <input 
                  type="text" 
                  name="alt_text" 
                  required 
                  value="<?= e($item['alt_text']) ?>" 
                  placeholder="Describe what is in the photo"
                  class="w-full px-3 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink placeholder:text-ink-subtle"
                >
                <p class="text-[10px] text-ink-subtle mt-0.5">Required. Do not use filenames.</p>
              </div>

              <!-- Optional Caption -->
              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-ink-muted mb-1">
                  Caption (optional)
                </label>
                <textarea 
                  name="caption" 
                  rows="2" 
                  placeholder="Caption displayed under lightbox"
                  class="w-full px-3 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink placeholder:text-ink-subtle"
                ><?= e($item['caption'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Card Bottom Actions -->
            <div class="pt-3 border-t border-border flex items-center justify-between gap-2">
              <button 
                type="submit" 
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-xl transition-colors shadow-soft"
              >
                Save
              </button>

              <button 
                type="button" 
                @click="confirmDelete(<?= (int) $item['id'] ?>)" 
                class="text-xs text-rose-600 hover:text-rose-800 hover:underline font-semibold"
              >
                Delete
              </button>
            </div>
          </form>

          <!-- Hidden Delete Form -->
          <form 
            id="delete-form-<?= (int) $item['id'] ?>" 
            action="/admin/gallery/<?= (int) $item['id'] ?>/delete" 
            method="POST" 
            class="hidden"
          >
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          </form>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<script>
function adminGallery(config) {
  return {
    csrfToken: config.csrfToken,
    batchDescription: '',
    isDragging: false,
    queue: [],
    isUploading: false,

    get queueStatusText() {
      if (this.isUploading) {
        const active = this.queue.filter(q => q.progress > 0 && q.progress < 100).length;
        const total = this.queue.length;
        return `Uploading photos (${total} in queue)...`;
      }
      const done = this.queue.filter(q => q.progress === 100).length;
      return `Upload finished: ${done} of ${this.queue.length} completed.`;
    },

    validateBatchDescription() {
      const desc = (this.batchDescription || '').trim();
      if (!desc) {
        alert("Please enter a description for these photos before selecting or dropping files.");
        const input = document.getElementById('batch_description');
        if (input) input.focus();
        return false;
      }
      if (/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i.test(desc) || (!desc.includes(' ') && desc.length < 10)) {
        alert("Please enter a descriptive text for these photos instead of a filename.");
        const input = document.getElementById('batch_description');
        if (input) input.focus();
        return false;
      }
      return true;
    },

    triggerUpload() {
      if (this.validateBatchDescription()) {
        this.$refs.fileInput.click();
      }
    },

    handleFiles(event) {
      if (!this.validateBatchDescription()) {
        event.target.value = '';
        return;
      }
      const files = Array.from(event.target.files || []);
      if (files.length > 0) {
        this.addFilesToQueue(files);
      }
      event.target.value = '';
    },

    handleDrop(event) {
      this.isDragging = false;
      if (!this.validateBatchDescription()) {
        return;
      }
      const files = Array.from(event.dataTransfer.files || []);
      if (files.length > 0) {
        this.addFilesToQueue(files);
      }
    },

    addFilesToQueue(files) {
      const allowed = ['image/jpeg', 'image/png', 'image/webp'];
      for (const file of files) {
        if (!allowed.includes(file.type)) {
          alert(`"${file.name}" is not a supported format. Please choose JPEG, PNG, or WebP.`);
          continue;
        }
        if (file.size > 8 * 1024 * 1024) {
          alert(`"${file.name}" is larger than 8 MB.`);
          continue;
        }
        this.queue.push({
          file: file,
          name: file.name,
          progress: 0,
          statusText: 'Waiting...',
          error: null
        });
      }

      if (!this.isUploading && this.queue.some(q => q.progress === 0)) {
        this.processQueue();
      }
    },

    async processQueue() {
      this.isUploading = true;
      let hasCompletedAny = false;

      for (let i = 0; i < this.queue.length; i++) {
        const item = this.queue[i];
        if (item.progress === 100 || item.error) continue;

        item.statusText = 'Uploading...';
        item.progress = 20;

        try {
          const formData = new FormData();
          formData.append('file', item.file);
          formData.append('csrf_token', this.csrfToken);
          formData.append('alt_text', this.batchDescription.trim());

          const response = await fetch('/admin/gallery/upload', {
            method: 'POST',
            headers: {
              'X-CSRF-Token': this.csrfToken
            },
            body: formData
          });

          item.progress = 80;
          const result = await response.json();

          if (!response.ok || !result.success) {
            throw new Error(result.error || ('Upload failed (HTTP ' + response.status + ')'));
          }

          item.progress = 100;
          item.statusText = 'Complete';
          hasCompletedAny = true;
        } catch (err) {
          item.error = err.message || 'Upload failed.';
          item.statusText = 'Failed';
        }
      }

      this.isUploading = false;

      // Automatically reload page to show uploaded photos in grid
      if (hasCompletedAny) {
        setTimeout(() => {
          window.location.reload();
        }, 800);
      }
    },

    clearCompletedQueue() {
      this.queue = this.queue.filter(q => q.progress < 100 && !q.error);
    },

    validateItemForm(event) {
      const altInput = event.target.querySelector('input[name="alt_text"]');
      if (altInput) {
        const val = altInput.value.trim();
        if (!val) {
          event.preventDefault();
          alert("Please describe what's in the photo.");
          altInput.focus();
          return;
        }
        if (/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i.test(val) || (!val.includes(' ') && val.length < 10)) {
          event.preventDefault();
          alert("Please describe what's in the photo instead of using a filename.");
          altInput.focus();
          return;
        }
      }
    },

    confirmDelete(id) {
      if (confirm('Delete this photo and its image files from the server? This cannot be undone.')) {
        const form = document.getElementById('delete-form-' + id);
        if (form) form.submit();
      }
    }
  };
}
</script>

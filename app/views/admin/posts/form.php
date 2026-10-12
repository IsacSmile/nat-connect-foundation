<!-- NatConnect Foundation — Editorial Post Editor -->
<?php
  $postId = $isEdit ? (int) $post['id'] : 0;
  $titleVal = $old['title'] ?? ($post['title'] ?? '');
  $slugVal = $old['slug'] ?? ($post['slug'] ?? '');
  $contentVal = $old['content'] ?? ($post['content'] ?? '');
  $excerptVal = $old['excerpt'] ?? ($post['excerpt'] ?? '');
  $categoryIdVal = (int) ($old['category_id'] ?? ($post['category_id'] ?? 0));
  $statusVal = $old['status'] ?? ($post['status'] ?? 'draft');
  $authorNameVal = $old['author_name'] ?? ($post['author_name'] ?? $defaultAuthor);
  $metaTitleVal = $old['meta_title'] ?? ($post['meta_title'] ?? '');
  $metaDescVal = $old['meta_description'] ?? ($post['meta_description'] ?? '');
  $tagsVal = $old['tags'] ?? ($post['tag_names'] ?? '');
  $coverTypeVal = $old['cover_type'] ?? ($post['cover_type'] ?? 'image');
  $coverImageVal = $old['cover_image'] ?? ($post['cover_image'] ?? '');
  $coverAltVal = $old['cover_alt'] ?? ($post['cover_alt'] ?? '');
  $videoUrlVal = $old['video_url'] ?? ($post['video_url'] ?? '');
  $isPickVal = !empty($old['is_pick'] ?? ($post['is_pick'] ?? 0));

  // Format published_at for datetime-local input (YYYY-MM-DDTHH:MM)
  $rawPub = $old['published_at'] ?? ($post['published_at'] ?? '');
  $publishedAtVal = '';
  if (!empty($rawPub)) {
      $ts = strtotime($rawPub);
      if ($ts !== false) {
          $publishedAtVal = date('Y-m-d\TH:i', $ts);
      }
  }

  $actionUrl = $isEdit ? "/admin/posts/{$postId}/edit" : "/admin/posts/new";
?>

<div 
  class="max-w-7xl mx-auto space-y-6 pb-28 lg:pb-0"
  x-data="postEditor({
    postId: <?= $postId ?>,
    initialTitle: '<?= e(addslashes($titleVal)) ?>',
    initialSlug: '<?= e(addslashes($slugVal)) ?>',
    initialCoverType: '<?= e(addslashes($coverTypeVal)) ?>',
    initialCoverImage: '<?= e(addslashes($coverImageVal)) ?>',
    initialCoverAlt: '<?= e(addslashes($coverAltVal)) ?>',
    initialVideoUrl: '<?= e(addslashes($videoUrlVal)) ?>',
    isEdit: <?= $isEdit ? 'true' : 'false' ?>
  })"
  x-init="initEditor()"
>

  <!-- Top Action & Navigation Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
      <a 
        href="/admin/posts" 
        class="p-2 text-ink-muted hover:text-ink hover:bg-surface rounded-xl border border-border transition-colors flex items-center justify-center min-h-[40px] min-w-[40px]"
        title="Back to posts list"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
      </a>
      <div>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">
          <?= $isEdit ? 'Edit Post #' . $postId : 'New Post' ?>
        </h1>
        <?php if ($isEdit): ?>
          <p class="text-xs text-ink-muted mt-0.5">
            Last updated: <?= formatDate($post['updated_at'] ?? $post['created_at'], 'd M Y, H:i') ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Top Action Buttons -->
    <div class="flex items-center gap-2 self-end sm:self-auto">
      <button 
        type="button" 
        @click="previewPost()" 
        class="px-4 py-2 text-sm font-semibold rounded-xl border border-border bg-white text-ink hover:bg-surface transition-colors min-h-[42px] inline-flex items-center gap-1.5"
        title="Preview how this post looks on the site"
      >
        <svg class="w-4 h-4 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        Preview
      </button>

      <button 
        type="button" 
        @click="submitAs('draft')" 
        class="px-4 py-2 text-sm font-semibold rounded-xl border border-border bg-white text-ink hover:bg-surface transition-colors min-h-[42px]"
      >
        Save draft
      </button>

      <button 
        type="button" 
        @click="submitPrimary()" 
        class="px-5 py-2 text-sm font-semibold rounded-xl text-white shadow-soft transition-colors min-h-[42px]"
        :class="currentStatus === 'scheduled' ? 'bg-primary hover:bg-primary-hover' : 'bg-accent-strong hover:bg-accent-hover'"
        x-text="currentStatus === 'scheduled' ? 'Schedule post' : 'Publish post'"
      >
        Publish post
      </button>
    </div>
  </div>

  <!-- Unsaved Changes Recovery Banner -->
  <div 
    x-show="hasBackup" 
    x-cloak 
    class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-soft-sm"
  >
    <div class="flex items-center gap-2.5">
      <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
      <div>
        <p class="text-sm font-semibold">Unsaved edits found from a previous session.</p>
        <p class="text-xs text-amber-700" x-text="'Saved on: ' + backupTimeFormatted"></p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button 
        type="button" 
        @click="restoreBackup()" 
        class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition-colors"
      >
        Restore changes
      </button>
      <button 
        type="button" 
        @click="dismissBackup()" 
        class="px-3 py-1.5 text-xs text-amber-800 hover:text-amber-950 font-medium transition-colors"
      >
        Dismiss
      </button>
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

  <?php if (!empty($errors['general'])): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium">
      <?= e($errors['general']) ?>
    </div>
  <?php endif; ?>

  <!-- Main Post Form -->
  <form 
    id="postForm" 
    action="<?= e($actionUrl) ?>" 
    method="POST" 
    @submit="isSubmitting = true; clearBackupStorage()"
    class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start"
  >
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="status" id="formStatusInput" x-model="currentStatus">

    <!-- Primary Editorial Column (8 cols on lg) -->
    <div class="lg:col-span-8 space-y-6">

      <!-- Post Title Field -->
      <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-4">
        <div>
          <label for="postTitle" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Title <span class="text-rose-500">*</span>
          </label>
          <input 
            type="text" 
            id="postTitle" 
            name="title" 
            x-model="title"
            @input="onTitleInput()"
            required 
            placeholder=""
            class="w-full px-4 py-3 rounded-xl border <?= isset($errors['title']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-lg sm:text-xl font-serif font-bold text-ink placeholder:text-ink-subtle placeholder:font-sans placeholder:text-base placeholder:font-normal"
          >
          <?php if (isset($errors['title'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['title']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Slug & Permalink Preview -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="postSlug" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">Permalink Slug</label>
            <button 
              type="button" 
              @click="slugLocked = !slugLocked" 
              class="text-[11px] text-primary hover:underline font-medium"
              x-text="slugLocked ? 'Edit slug' : 'Auto-sync with title'"
            ></button>
          </div>
          <div class="flex rounded-xl shadow-soft-sm overflow-hidden border <?= isset($errors['slug']) ? 'border-rose-400' : 'border-border' ?>">
            <span class="inline-flex items-center px-3 text-xs font-mono text-ink-muted bg-surface border-r border-border select-none">
              /post/
            </span>
            <input 
              type="text" 
              id="postSlug" 
              name="slug" 
              x-model="slug"
              :readonly="slugLocked && !isEdit"
              @input="slugLocked = true"
              class="w-full px-3.5 py-2 font-mono text-xs text-ink focus:outline-none focus:ring-2 focus:ring-primary bg-white"
            >
          </div>
          <p class="text-[11px] text-ink-subtle mt-1">Lowercase letters, numbers, and hyphens.</p>
        </div>
      </div>

      <!-- Rich-Text Content (TinyMCE) -->
      <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-2">
        <label for="post_content" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">
          Content <span class="text-rose-500">*</span>
        </label>
        
        <textarea 
          id="post_content" 
          name="content" 
          rows="16" 
          class="w-full px-4 py-3 rounded-xl border border-border text-sm text-ink focus:outline-none"
        ><?= e($contentVal) ?></textarea>
      </div>

      <!-- Excerpt Field -->
      <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-2">
        <label for="postExcerpt" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">Excerpt</label>
        <textarea 
          id="postExcerpt" 
          name="excerpt" 
          rows="3" 
          x-model="excerpt"
          placeholder="Brief summary for archive cards and search previews..."
          class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
        ><?= e($excerptVal) ?></textarea>
        <p class="text-[11px] text-ink-subtle">Leave blank to use the beginning of the post.</p>
      </div>

      <!-- Cover Section -->
      <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-border pb-4">
          <div>
            <h3 class="font-serif text-base font-bold text-ink">Cover</h3>
          </div>

          <!-- Cover Type Toggle (Segmented control) -->
          <div class="inline-flex p-1 bg-surface rounded-xl border border-border shrink-0">
            <button 
              type="button" 
              @click="setCoverType('image')"
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 min-h-[32px]"
              :class="coverType === 'image' ? 'bg-white text-primary shadow-soft-sm font-bold' : 'text-ink-muted hover:text-ink'"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
              <span>Image</span>
            </button>
            <button 
              type="button" 
              @click="setCoverType('video')"
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 min-h-[32px]"
              :class="coverType === 'video' ? 'bg-white text-primary shadow-soft-sm font-bold' : 'text-ink-muted hover:text-ink'"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
              <span>YouTube Video</span>
            </button>
          </div>
        </div>

        <input type="hidden" name="cover_type" :value="coverType">
        <input type="hidden" name="cover_image" :value="coverImage">

        <!-- Cover Type: Image Panel -->
        <div x-show="coverType === 'image'" class="space-y-4">
          <!-- Hidden file input for cover upload -->
          <input 
            type="file" 
            id="coverFileInput" 
            x-ref="coverFileInput"
            accept="image/jpeg,image/png,image/webp" 
            class="hidden" 
            @change="handleCoverFileSelect($event)"
          >

          <!-- Upload Dropzone (shown when no cover image is uploaded) -->
          <div 
            x-show="!coverImage && !isUploadingCover"
            @dragover.prevent="isDraggingCover = true"
            @dragleave.prevent="isDraggingCover = false"
            @drop.prevent="isDraggingCover = false; handleCoverDrop($event)"
            @click="$refs.coverFileInput.click()"
            class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors"
            :class="isDraggingCover ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-surface/50'"
          >
            <div class="max-w-sm mx-auto space-y-2">
              <div class="w-12 h-12 rounded-full bg-surface mx-auto flex items-center justify-center text-ink-muted">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
              </div>
              <p class="text-sm font-semibold text-ink">Click to upload cover image or drag and drop</p>
              <p class="text-xs text-ink-muted">JPEG, PNG, or WebP up to 8 MB (max 25 MP). Strips EXIF metadata automatically.</p>
            </div>
          </div>

          <!-- Uploading Spinner -->
          <div x-show="isUploadingCover" class="p-8 rounded-xl bg-surface border border-border text-center space-y-3">
            <svg class="animate-spin w-8 h-8 text-primary mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-xs font-semibold text-ink">Processing and re-encoding image variants...</p>
          </div>

          <!-- Cover Image Preview & Actions (shown when image is loaded) -->
          <div x-show="coverImage && !isUploadingCover" class="space-y-4">
            <div class="relative rounded-xl overflow-hidden border border-border bg-slate-900 group aspect-[16/9] max-h-72 flex items-center justify-center">
              <img 
                :src="coverImage" 
                :alt="coverAlt || 'Cover image preview'" 
                class="w-full h-full object-cover"
              >
              <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button 
                  type="button" 
                  @click="$refs.coverFileInput.click()" 
                  class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-white/90 hover:bg-white text-ink transition-colors shadow-soft"
                >
                  Replace Image
                </button>
                <button 
                  type="button" 
                  @click="removeCoverImage()" 
                  class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition-colors shadow-soft"
                >
                  Remove
                </button>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 text-xs">
              <button 
                type="button" 
                @click="$refs.coverFileInput.click()" 
                class="text-primary hover:underline font-semibold"
              >
                Replace
              </button>
              <span class="text-border">|</span>
              <button 
                type="button" 
                @click="removeCoverImage()" 
                class="text-rose-600 hover:underline font-semibold"
              >
                Remove
              </button>
            </div>

            <!-- REQUIRED Alt Text Field -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="cover_alt" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">
                  Photo description (alt text) <span class="text-rose-500">*</span>
                </label>
                <span class="text-[11px] text-ink-subtle">Required</span>
              </div>
              <input 
                type="text" 
                id="cover_alt" 
                name="cover_alt" 
                x-model="coverAlt" 
                placeholder="Describe what's in the photo. Example: Volunteers planting mangroves at Panje wetland."
                class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['cover_alt']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
                :required="coverType === 'image' && !!coverImage"
              >
              <?php if (isset($errors['cover_alt'])): ?>
                <p class="text-xs text-rose-600 mt-1"><?= e($errors['cover_alt']) ?></p>
              <?php endif; ?>
              <p class="text-[11px] text-ink-subtle mt-1">Describe what's in the photo. Example: Volunteers planting mangroves at Panje wetland.</p>
            </div>
          </div>

          <!-- Error message if cover upload failed -->
          <div x-show="coverUploadError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium flex items-center justify-between">
            <span x-text="coverUploadError"></span>
            <button type="button" @click="coverUploadError = ''" class="text-rose-500 hover:text-rose-700">&times;</button>
          </div>
        </div>

        <!-- Cover Type: YouTube Video Panel -->
        <div x-show="coverType === 'video'" class="space-y-4">
          <div>
            <label for="video_url" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              YouTube Video URL
            </label>
            <div class="flex rounded-xl shadow-soft-sm overflow-hidden border <?= isset($errors['video_url']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?>">
              <span class="inline-flex items-center px-3 text-xs font-mono text-ink-muted bg-surface border-r border-border select-none">
                YouTube
              </span>
              <input 
                type="text" 
                id="video_url" 
                name="video_url" 
                x-model="videoUrl" 
                @input="updateVideoPreview()" 
                placeholder="https://www.youtube.com/watch?v=..."
                class="w-full px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-primary bg-white font-mono"
              >
            </div>
            <?php if (isset($errors['video_url'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['video_url']) ?></p>
            <?php endif; ?>
            <p class="text-[11px] text-ink-subtle mt-1">Accepts youtube.com/watch, youtu.be, shorts URLs, or video IDs.</p>
          </div>

          <!-- Video Thumbnail Preview -->
          <div x-show="videoPreviewId" class="space-y-2">
            <p class="text-xs font-semibold text-ink-muted">Video Preview</p>
            <div class="relative rounded-xl overflow-hidden border border-border bg-slate-950 aspect-[16/9] max-h-72">
              <img 
                :src="'https://img.youtube.com/vi/' + videoPreviewId + '/hqdefault.jpg'" 
                alt="YouTube thumbnail preview" 
                class="w-full h-full object-cover"
              >
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex items-end p-4">
                <div class="flex items-center justify-between w-full">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-600 text-white">
                      <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <span class="text-xs font-mono text-white/90" x-text="'ID: ' + videoPreviewId"></span>
                  </div>
                  <a 
                    :href="'https://www.youtube.com/watch?v=' + videoPreviewId" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="text-xs text-white/80 hover:text-white underline"
                  >
                    Watch on YouTube &rarr;
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div x-show="!videoPreviewId && videoUrl.trim().length > 0" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
            Please enter a valid YouTube URL (youtube.com/watch, youtu.be, or shorts) or 11-character video ID to load the preview.
          </div>
        </div>
      </div>

      <!-- Search Preview Meta Panel -->
      <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-4">
        <h3 class="font-serif text-base font-bold text-ink border-b border-border pb-3">Search preview (optional)</h3>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="meta_title" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">Search title</label>
            <span class="text-[11px] font-mono text-ink-subtle" :class="metaTitle.length > 60 ? 'text-amber-600 font-semibold' : ''" x-text="metaTitle.length + ' / 60'"></span>
          </div>
          <input 
            type="text" 
            id="meta_title" 
            name="meta_title" 
            x-model="metaTitle"
            placeholder=""
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
          >
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="meta_description" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">Search description</label>
            <span class="text-[11px] font-mono text-ink-subtle" :class="metaDescription.length > 160 ? 'text-amber-600 font-semibold' : ''" x-text="metaDescription.length + ' / 160'"></span>
          </div>
          <textarea 
            id="meta_description" 
            name="meta_description" 
            rows="3" 
            x-model="metaDescription"
            placeholder=""
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
          ></textarea>
        </div>
      </div>

    </div>

    <!-- Editorial Sidebar Controls (4 cols on lg) -->
    <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-6">

      <!-- Publishing Card -->
      <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-5">
        <h3 class="font-serif text-base font-bold text-ink border-b border-border pb-3">Publishing</h3>

        <!-- Status Selector -->
        <div>
          <label for="postStatus" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">Status</label>
          <select 
            id="postStatus" 
            x-model="currentStatus"
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white"
          >
            <option value="draft">Draft (Work in progress)</option>
            <option value="published">Published (Live immediately)</option>
            <option value="scheduled">Scheduled (Future release)</option>
          </select>
        </div>

        <!-- Schedule Date Time Picker (Shown when scheduled or editing scheduled/published) -->
        <div x-show="currentStatus === 'scheduled'" x-cloak class="space-y-1">
          <label for="published_at" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">
            Publication Date & Time <span class="text-rose-500">*</span>
          </label>
          <input 
            type="datetime-local" 
            id="published_at" 
            name="published_at" 
            x-model="publishedAt"
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['published_at']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
          <p class="text-[11px] text-ink-subtle">IST (+05:30). Must be set in the future.</p>
          <?php if (isset($errors['published_at'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['published_at']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Category Picker -->
        <div>
          <label for="category_id" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Category <span class="text-rose-500">*</span>
          </label>
          <select 
            id="category_id" 
            name="category_id" 
            required 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['category_id']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white"
          >
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>" <?= $categoryIdVal === (int) $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category_id'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['category_id']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Author Name Field -->
        <div>
          <label for="author_name" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">Author</label>
          <input 
            type="text" 
            id="author_name" 
            name="author_name" 
            value="<?= e($authorNameVal) ?>"
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
        </div>

        <!-- Tags Input with Datalist Suggestions -->
        <div>
          <label for="postTags" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">Tags (Comma-separated)</label>
          <input 
            type="text" 
            id="postTags" 
            name="tags" 
            x-model="tags"
            list="tagSuggestionsList"
            placeholder=""
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
          >
          <datalist id="tagSuggestionsList">
            <?php foreach ($tagSuggestions as $tagSuggestion): ?>
              <option value="<?= e($tagSuggestion) ?>">
            <?php endforeach; ?>
          </datalist>
          <p class="text-[11px] text-ink-subtle mt-1">Separate multiple tags with commas. New tags will be created automatically.</p>
        </div>

        <!-- Editor's Pick Checkbox -->
        <div class="pt-2">
          <label class="relative flex items-start gap-2.5 cursor-pointer select-none">
            <input 
              type="checkbox" 
              name="is_pick" 
              value="1" 
              <?= !empty($isPickVal) ? 'checked' : '' ?>
              class="mt-0.5 rounded border-border text-primary focus:ring-primary h-4 w-4"
            >
            <div>
              <span class="text-xs font-semibold uppercase tracking-wider text-ink block">Editor's pick</span>
              <span class="text-[11px] text-ink-subtle block">Shown in the picks list on the home page (latest 3 picks).</span>
            </div>
          </label>
        </div>

        <!-- Action Buttons -->
        <div class="pt-3 border-t border-border space-y-2.5">
          <button 
            type="button" 
            @click="submitPrimary()" 
            class="w-full inline-flex items-center justify-center px-4 py-2.5 text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
            :class="currentStatus === 'scheduled' ? 'bg-primary hover:bg-primary-hover' : 'bg-accent-strong hover:bg-accent-hover'"
            x-text="currentStatus === 'scheduled' ? 'Schedule Post' : 'Publish Post'"
          >
            Publish
          </button>

          <button 
            type="button" 
            @click="submitAs('draft')" 
            class="w-full inline-flex items-center justify-center px-4 py-2.5 border border-border text-ink hover:bg-surface text-sm font-semibold rounded-xl transition-colors min-h-[44px]"
          >
            Save draft
          </button>

          <button 
            type="button" 
            @click="previewPost()" 
            class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 border border-border text-ink hover:bg-surface text-sm font-semibold rounded-xl transition-colors min-h-[44px]"
          >
            <svg class="w-4 h-4 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Preview
          </button>
        </div>

      </div>

    </div>

  </form>

  <!-- Mobile Sticky Bottom Action Bar -->
  <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-border p-3.5 pb-safe z-40 lg:hidden flex items-center justify-between gap-2 shadow-lg">
    <button 
      type="button" 
      @click="previewPost()" 
      class="px-3.5 py-2.5 text-sm font-semibold rounded-xl border border-border bg-white text-ink hover:bg-surface transition-colors min-h-[44px] flex items-center justify-center shrink-0"
      aria-label="Preview post"
      title="Preview"
    >
      <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    </button>
    <button 
      type="button" 
      @click="submitAs('draft')" 
      class="flex-1 px-3 py-2.5 text-sm font-semibold rounded-xl border border-border bg-white text-ink hover:bg-surface transition-colors min-h-[44px]"
    >
      Save draft
    </button>
    <button 
      type="button" 
      @click="submitPrimary()" 
      class="flex-1 px-3 py-2.5 text-sm font-semibold rounded-xl text-white shadow-soft transition-colors min-h-[44px]"
      :class="currentStatus === 'scheduled' ? 'bg-primary hover:bg-primary-hover' : 'bg-accent-strong hover:bg-accent-hover'"
      x-text="currentStatus === 'scheduled' ? 'Schedule' : 'Publish'"
    >
      Publish
    </button>
  </div>

</div>

<!-- Self-Hosted TinyMCE Initialization Script -->
<script src="/assets/vendor/tinymce/tinymce.min.js"></script>
<script>
function youtubeId(url) {
  if (!url) return null;
  const pattern = /(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i;
  const match = url.match(pattern);
  if (match && match[1]) {
    return match[1];
  }
  const trimmed = url.trim();
  if (/^[a-zA-Z0-9_-]{11}$/.test(trimmed)) {
    return trimmed;
  }
  return null;
}
window.youtubeId = youtubeId;

function postEditor(config) {
  return {
    postId: config.postId,
    title: config.initialTitle || '',
    slug: config.initialSlug || '',
    excerpt: '<?= e(addslashes($excerptVal)) ?>',
    tags: '<?= e(addslashes($tagsVal)) ?>',
    publishedAt: '<?= e(addslashes($publishedAtVal)) ?>',
    metaTitle: '<?= e(addslashes($metaTitleVal)) ?>',
    metaDescription: '<?= e(addslashes($metaDescVal)) ?>',
    currentStatus: '<?= e(addslashes($statusVal)) ?>',
    coverType: config.initialCoverType || 'image',
    coverImage: config.initialCoverImage || '',
    coverAlt: config.initialCoverAlt || '',
    videoUrl: config.initialVideoUrl || '',
    videoPreviewId: '',
    isUploadingCover: false,
    isDraggingCover: false,
    coverUploadError: '',
    slugLocked: config.isEdit || config.initialSlug.length > 0,
    isEdit: config.isEdit,
    hasBackup: false,
    backupTimeFormatted: '',
    backupData: null,
    isSubmitting: false,

    youtubeId(url) {
      return youtubeId(url);
    },

    setCoverType(type) {
      this.coverType = type;
      if (type === 'video') {
        this.updateVideoPreview();
      }
    },

    updateVideoPreview() {
      this.videoPreviewId = this.youtubeId(this.videoUrl) || '';
    },

    handleCoverFileSelect(event) {
      const file = event.target.files && event.target.files[0];
      if (file) {
        this.uploadCover(file);
      }
      event.target.value = '';
    },

    handleCoverDrop(event) {
      const file = event.dataTransfer.files && event.dataTransfer.files[0];
      if (file) {
        this.uploadCover(file);
      }
    },

    uploadCover(file) {
      const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        this.coverUploadError = 'Only JPEG, PNG, or WebP images are allowed.';
        return;
      }
      if (file.size > 8 * 1024 * 1024) {
        this.coverUploadError = 'Image exceeds 8 MB size limit.';
        return;
      }

      this.isUploadingCover = true;
      this.coverUploadError = '';

      const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
      const formData = new FormData();
      formData.append('file', file);
      formData.append('csrf_token', csrfToken);

      fetch('/admin/media/upload', {
        method: 'POST',
        headers: {
          'X-CSRF-Token': csrfToken
        },
        body: formData
      })
      .then(async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(data.error || 'Failed to upload image (HTTP ' + response.status + ')');
        }
        if (!data.location) {
          throw new Error('Invalid server response: missing image location.');
        }
        this.coverImage = data.location;
        this.coverType = 'image';
        // Auto-focus or prompt alt text if empty
        if (!this.coverAlt) {
          setTimeout(() => {
            const altInput = document.getElementById('cover_alt');
            if (altInput) altInput.focus();
          }, 100);
        }
      })
      .catch((err) => {
        this.coverUploadError = err.message || 'Image upload failed. Please try again.';
      })
      .finally(() => {
        this.isUploadingCover = false;
      });
    },

    removeCoverImage() {
      this.coverImage = '';
      this.coverAlt = '';
      this.coverUploadError = '';
    },

    initEditor() {
      const self = this;
      this.updateVideoPreview();

      const storageKey = 'natconnect_post_draft_' + (this.postId || 'new');
      const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

      // 1. Initialize TinyMCE Self-Hosted with GPL License
      tinymce.init({
        selector: '#post_content',
        license_key: 'gpl',
        height: 520,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'link lists image blockquote wordcount paste',
        toolbar: 'undo redo | blocks | bold italic | link image youtube blockquote | bullist numlist | removeformat',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
        paste_as_text: false,
        paste_data_images: false,
        automatic_uploads: true,
        image_description: true,
        image_dimensions: true,
        images_upload_url: '/admin/media/upload',
        images_upload_handler: function (blobInfo, progress) {
          return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open('POST', '/admin/media/upload');

            xhr.upload.onprogress = (e) => {
              progress(e.loaded / e.total * 100);
            };

            const token = document.querySelector('input[name="csrf_token"]')?.value || csrfToken;
            xhr.setRequestHeader('X-CSRF-Token', token);

            xhr.onload = () => {
              if (xhr.status === 403) {
                reject({ message: 'CSRF token error or session expired.', remove: true });
                return;
              }
              if (xhr.status < 200 || xhr.status >= 300) {
                let msg = 'HTTP Error: ' + xhr.status;
                try {
                  const resp = JSON.parse(xhr.responseText);
                  if (resp && resp.error) msg = resp.error;
                } catch (e) {}
                reject({ message: msg, remove: true });
                return;
              }

              try {
                const json = JSON.parse(xhr.responseText);
                if (!json || typeof json.location !== 'string') {
                  reject({ message: 'Invalid response from server.', remove: true });
                  return;
                }
                resolve(json.location);
              } catch (e) {
                reject({ message: 'Failed to parse upload response.', remove: true });
              }
            };

            xhr.onerror = () => {
              reject({ message: 'Network error during upload.', remove: true });
            };

            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            formData.append('csrf_token', token);
            xhr.send(formData);
          });
        },
        skin: 'oxide',
        content_css: 'default',
        content_style: `
          body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            font-size: 16px; 
            line-height: 1.7; 
            color: #1A1D29; 
            padding: 1.25rem; 
            max-width: 68ch; 
            margin: 0 auto; 
          }
          h2, h3 { font-family: 'Fraunces', Georgia, serif; color: #2F3A8F; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; }
          h2 { font-size: 1.5rem; line-height: 1.3; }
          h3 { font-size: 1.25rem; line-height: 1.35; }
          p { margin-bottom: 1.25em; }
          blockquote { border-left: 3px solid #2F3A8F; padding-left: 1rem; color: #4B5563; font-style: italic; margin: 1.5em 0; }
          a { color: #2E7D32; text-decoration: underline; }
          ul, ol { padding-left: 1.5rem; margin-bottom: 1.25em; }
          iframe { width: 100%; aspect-ratio: 16 / 9; border-radius: 0.75rem; margin: 1.5rem 0; border: 0; display: block; }
          img { max-width: 100%; height: auto; border-radius: 0.75rem; margin: 1.5rem 0; display: block; }
        `,
        setup(editor) {
          editor.ui.registry.addButton('youtube', {
            icon: 'embed',
            text: 'YouTube',
            tooltip: 'Insert YouTube Video',
            onAction: function() {
              const url = window.prompt('Enter YouTube video URL:');
              if (!url) return;
              const vid = youtubeId(url);
              if (vid) {
                const iframe = '<iframe src="https://www.youtube-nocookie.com/embed/' + vid + '" title="YouTube video player" frameborder="0" allowfullscreen class="w-full aspect-video rounded-xl my-4"></iframe>';
                editor.insertContent(iframe);
              } else {
                alert('Please enter a valid YouTube video URL or 11-character video ID.');
              }
            }
          });

          // Ensure inserted images get alt text prompts and width/height attributes
          editor.on('NodeChange', function(e) {
            const node = editor.selection.getNode();
            if (node && node.nodeName === 'IMG' && !node.getAttribute('data-alt-prompted')) {
              node.setAttribute('data-alt-prompted', 'true');
              const currentAlt = node.getAttribute('alt');
              if (!currentAlt || currentAlt.trim() === '') {
                setTimeout(() => {
                  const altPrompt = window.prompt('Enter alternative text (alt text) for this image (required for accessibility & SEO):', '');
                  if (altPrompt !== null && altPrompt.trim() !== '') {
                    node.setAttribute('alt', altPrompt.trim());
                  } else {
                    node.setAttribute('alt', '');
                  }
                  if (node.naturalWidth && !node.getAttribute('width')) {
                    node.setAttribute('width', node.naturalWidth);
                  }
                  if (node.naturalHeight && !node.getAttribute('height')) {
                    node.setAttribute('height', node.naturalHeight);
                  }
                  editor.setDirty(true);
                }, 80);
              }
            }
          });

          // Keep width and height attributes when image finishes loading
          editor.on('init', function() {
            editor.getDoc().addEventListener('load', function(e) {
              if (e.target && e.target.nodeName === 'IMG') {
                const img = e.target;
                if (!img.getAttribute('width') && img.naturalWidth) {
                  img.setAttribute('width', img.naturalWidth);
                }
                if (!img.getAttribute('height') && img.naturalHeight) {
                  img.setAttribute('height', img.naturalHeight);
                }
              }
            }, true);
          });
        }
      });

      // 2. Check localStorage for unsaved backups
      try {
        const raw = localStorage.getItem(storageKey);
        if (raw) {
          const parsed = JSON.parse(raw);
          if (parsed && parsed.timestamp) {
            this.backupData = parsed;
            this.hasBackup = true;
            this.backupTimeFormatted = new Date(parsed.timestamp).toLocaleString();
          }
        }
      } catch (e) {
        console.warn('LocalStorage error:', e);
      }

      // 3. Periodic Auto-Save to localStorage every 10 seconds
      setInterval(() => {
        self.saveToLocalStorage();
      }, 10000);

      // 4. Session Keepalive Heartbeat every 5 minutes (prevents 2h idle expiry during editing)
      setInterval(() => {
        fetch('/admin/keepalive', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
          }
        }).catch(err => console.warn('Keepalive ping failed:', err));
      }, 300000);

      // 5. Unsaved changes beforeunload warning
      window.addEventListener('beforeunload', (e) => {
        if (!self.isSubmitting) {
          e.preventDefault();
          e.returnValue = '';
        }
      });
    },

    onTitleInput() {
      if (!this.slugLocked) {
        this.slug = this.slugify(this.title);
      }
    },

    slugify(text) {
      return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
    },

    saveToLocalStorage() {
      if (this.isSubmitting) return;
      const storageKey = 'natconnect_post_draft_' + (this.postId || 'new');
      const content = tinymce.get('post_content') ? tinymce.get('post_content').getContent() : '';

      const data = {
        title: this.title,
        slug: this.slug,
        content: content,
        excerpt: this.excerpt,
        tags: this.tags,
        metaTitle: this.metaTitle,
        metaDescription: this.metaDescription,
        status: this.currentStatus,
        coverType: this.coverType,
        coverImage: this.coverImage,
        coverAlt: this.coverAlt,
        videoUrl: this.videoUrl,
        timestamp: Date.now()
      };

      try {
        localStorage.setItem(storageKey, JSON.stringify(data));
      } catch (e) {
        console.warn('LocalStorage save failed:', e);
      }
    },

    restoreBackup() {
      if (!this.backupData) return;
      this.title = this.backupData.title || this.title;
      this.slug = this.backupData.slug || this.slug;
      this.excerpt = this.backupData.excerpt || this.excerpt;
      this.tags = this.backupData.tags || this.tags;
      this.metaTitle = this.backupData.metaTitle || this.metaTitle;
      this.metaDescription = this.backupData.metaDescription || this.metaDescription;
      this.currentStatus = this.backupData.status || this.currentStatus;
      if (this.backupData.coverType) this.coverType = this.backupData.coverType;
      if (this.backupData.coverImage !== undefined) this.coverImage = this.backupData.coverImage;
      if (this.backupData.coverAlt !== undefined) this.coverAlt = this.backupData.coverAlt;
      if (this.backupData.videoUrl !== undefined) {
        this.videoUrl = this.backupData.videoUrl;
        this.updateVideoPreview();
      }

      if (this.backupData.content && tinymce.get('post_content')) {
        tinymce.get('post_content').setContent(this.backupData.content);
      }

      this.hasBackup = false;
    },

    dismissBackup() {
      this.hasBackup = false;
      this.clearBackupStorage();
    },

    clearBackupStorage() {
      const storageKey = 'natconnect_post_draft_' + (this.postId || 'new');
      try {
        localStorage.removeItem(storageKey);
      } catch (e) {
        console.warn('LocalStorage clear error:', e);
      }
    },

    submitAs(status) {
      this.currentStatus = status;
      this.submitForm();
    },

    submitPrimary() {
      if (this.currentStatus !== 'scheduled') {
        this.currentStatus = 'published';
      }
      this.submitForm();
    },

    submitForm() {
      if (this.coverType === 'image' && this.coverImage) {
        const alt = (this.coverAlt || '').trim();
        if (!alt) {
          alert("Please describe what's in the photo.");
          document.getElementById('cover_alt')?.focus();
          return;
        }
        if (/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i.test(alt) || (!alt.includes(' ') && alt.length < 10)) {
          alert("Please describe what's in the photo instead of using a filename.");
          document.getElementById('cover_alt')?.focus();
          return;
        }
      }
      this.isSubmitting = true;
      if (tinymce.get('post_content')) {
        const editor = tinymce.get('post_content');
        // Ensure image width and height attributes before saving
        const doc = editor.getDoc();
        if (doc) {
          const imgs = doc.querySelectorAll('img');
          imgs.forEach((img) => {
            if (!img.getAttribute('width') && img.naturalWidth) {
              img.setAttribute('width', img.naturalWidth);
            }
            if (!img.getAttribute('height') && img.naturalHeight) {
              img.setAttribute('height', img.naturalHeight);
            }
          });
        }
        editor.save(); // syncs editor content back to textarea
      }
      document.getElementById('formStatusInput').value = this.currentStatus;
      document.getElementById('postForm').submit();
    },

    previewPost() {
      if (!this.postId) {
        if (confirm('Please save this post as a draft first before previewing. Save draft now?')) {
          this.saveDraftAndPreview();
        }
        return;
      }

      // Check if dirty
      let isDirty = false;
      if (typeof tinymce !== 'undefined' && tinymce.get('post_content') && tinymce.get('post_content').isDirty()) {
        isDirty = true;
      }
      if (this.title !== (config.initialTitle || '') ||
          this.slug !== (config.initialSlug || '') ||
          this.coverImage !== (config.initialCoverImage || '') ||
          this.coverAlt !== (config.initialCoverAlt || '') ||
          this.videoUrl !== (config.initialVideoUrl || '')) {
        isDirty = true;
      }

      if (isDirty) {
        if (confirm('You have unsaved changes. Save draft before previewing?')) {
          this.saveDraftAndPreview();
          return;
        }
      }

      window.open('/admin/posts/' + this.postId + '/preview', '_blank');
    },

    saveDraftAndPreview() {
      let previewInput = document.getElementById('redirectToPreview');
      if (!previewInput) {
        previewInput = document.createElement('input');
        previewInput.type = 'hidden';
        previewInput.name = 'redirect_to_preview';
        previewInput.id = 'redirectToPreview';
        document.getElementById('postForm').appendChild(previewInput);
      }
      previewInput.value = '1';
      this.submitAs('draft');
    }
  };
}
</script>

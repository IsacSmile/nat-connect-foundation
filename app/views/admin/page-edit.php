<!-- NatConnect Foundation — Page Editor -->
<?php
  $pageId = (int) ($page['id'] ?? 0);
  $isSystem = !empty($page['is_system']);
  $isPrivacy = ($page['slug'] ?? '') === 'privacy';
  $actionUrl = $isNew ? '/admin/pages/new' : '/admin/pages/' . $pageId . '/edit';
?>

<div 
  class="max-w-5xl mx-auto space-y-6 pb-20"
  x-data="{
    title: '<?= e(addslashes($page['title'] ?? '')) ?>',
    slug: '<?= e(addslashes($page['slug'] ?? '')) ?>',
    slugLocked: <?= ($isNew && empty($page['slug'])) ? 'false' : 'true' ?>,
    isSystem: <?= $isSystem ? 'true' : 'false' ?>,
    deleteModalOpen: false,

    slugify(text) {
      return text.toString().toLowerCase().trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
    },

    onTitleInput() {
      if (!this.slugLocked && !this.isSystem) {
        this.slug = this.slugify(this.title);
      }
    }
  }"
>

  <!-- Top Action & Navigation Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
      <a 
        href="/admin/pages" 
        class="p-2 text-ink-muted hover:text-ink hover:bg-surface rounded-xl border border-border transition-colors flex items-center justify-center min-h-[40px] min-w-[40px]"
        title="Back to pages"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
      </a>
      <div>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">
          <?= $isNew ? 'New Page' : 'Edit Page' ?>
        </h1>
        <?php if ($isSystem): ?>
          <p class="text-xs text-primary font-medium mt-0.5">
            System page: cannot be deleted, slug cannot be changed<?= $isPrivacy ? ', and Privacy Policy cannot be unpublished' : '' ?>.
          </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Preview & Action Buttons -->
    <div class="flex items-center gap-3 self-end sm:self-auto">
      <?php if (!$isNew): ?>
        <a 
          href="/admin/pages/<?= $pageId ?>/preview" 
          target="_blank" 
          class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-xl border border-border transition-colors min-h-[44px]"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
          <span>Preview</span>
        </a>
      <?php endif; ?>

      <button 
        type="submit" 
        form="page-form"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
      >
        <span>Save page</span>
      </button>
    </div>
  </div>

  <!-- Error Alerts -->
  <?php if (!empty($errors)): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1">
      <p class="font-semibold">Please fix the following:</p>
      <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="page-form" action="<?= e($actionUrl) ?>" method="POST" class="space-y-6">
    <?= \App\Core\Csrf::field() ?>

    <!-- Main Content Card -->
    <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-6">
      
      <!-- Title -->
      <div>
        <label for="page_title" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Page Title <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          id="page_title" 
          name="title" 
          required 
          x-model="title"
          @input="onTitleInput()"
          placeholder="e.g. About Us" 
          class="w-full px-4 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-base font-medium text-ink outline-none transition-colors"
        >
      </div>

      <!-- Slug -->
      <div>
        <label for="page_slug" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          URL Slug <span class="text-rose-500">*</span>
        </label>
        <div class="flex items-center gap-2">
          <span class="text-sm text-ink-muted font-mono select-none">/</span>
          <input 
            type="text" 
            id="page_slug" 
            name="slug" 
            required 
            x-model="slug"
            @input="slugLocked = true"
            <?= $isSystem ? 'readonly' : '' ?>
            placeholder="about" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm font-mono outline-none transition-colors <?= $isSystem ? 'bg-surface text-ink-muted cursor-not-allowed' : 'text-ink' ?>"
          >
        </div>
        <p class="text-xs text-ink-muted mt-1.5">
          <?= $isSystem ? 'System page slug is permanent and cannot be modified.' : 'Reserved slugs (admin, api, post, category, tag, search, gallery, donate, contact, uploads, assets, setup, index) cannot be used.' ?>
        </p>
      </div>

      <!-- TinyMCE Content -->
      <div>
        <label for="page_content" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Page Content
        </label>
        <textarea 
          id="page_content" 
          name="content" 
          rows="16" 
          class="w-full rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none"
        ><?= e($page['content'] ?? '') ?></textarea>
      </div>

    </div>

    <!-- Navigation & Visibility Settings Card -->
    <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-6">
      <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Menu & Visibility</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- Status -->
        <div>
          <label for="page_status" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Publication Status
          </label>
          <select 
            id="page_status" 
            name="status" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors bg-white"
          >
            <option value="published" <?= ($page['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Published</option>
            <?php if (!$isPrivacy): ?>
              <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <?php endif; ?>
          </select>
          <?php if ($isPrivacy): ?>
            <p class="text-xs text-ink-muted mt-1">Privacy Policy must remain published at all times.</p>
          <?php endif; ?>
        </div>

        <!-- Menu Order -->
        <div>
          <label for="nav_order" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Menu Order
          </label>
          <input 
            type="number" 
            id="nav_order" 
            name="nav_order" 
            value="<?= (int) ($page['nav_order'] ?? 0) ?>" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors"
          >
          <p class="text-xs text-ink-muted mt-1">Lower numbers appear first in the navigation menu.</p>
        </div>
      </div>

      <!-- Show in Menu Checkbox -->
      <div class="pt-2">
        <label class="inline-flex items-start gap-3 cursor-pointer select-none">
          <input 
            type="checkbox" 
            name="show_in_nav" 
            value="1" 
            <?= !empty($page['show_in_nav']) ? 'checked' : '' ?>
            class="mt-0.5 h-4 w-4 rounded border-border text-primary focus:ring-primary"
          >
          <div>
            <span class="text-sm font-semibold text-ink">Show in navigation menu</span>
            <p class="text-xs text-ink-muted">Display a link to this page in the top header menu.</p>
          </div>
        </label>
      </div>

      <!-- Menu Label (Optional override) -->
      <div>
        <label for="nav_label" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Menu Label (Optional)
        </label>
        <input 
          type="text" 
          id="nav_label" 
          name="nav_label" 
          maxlength="40"
          value="<?= e($page['nav_label'] ?? '') ?>" 
          placeholder="e.g. Why NatConnect (leave blank to use Title)"
          class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors"
        >
      </div>
    </div>

    <!-- SEO Metadata Card -->
    <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-6">
      <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Search Engine Optimization (SEO)</h2>

      <!-- Meta Title -->
      <div>
        <label for="meta_title" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Meta Title
        </label>
        <input 
          type="text" 
          id="meta_title" 
          name="meta_title" 
          maxlength="255"
          value="<?= e($page['meta_title'] ?? '') ?>" 
          placeholder="Defaults to Page Title — Site Name"
          class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors"
        >
      </div>

      <!-- Meta Description -->
      <div>
        <label for="meta_description" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Meta Description
        </label>
        <textarea 
          id="meta_description" 
          name="meta_description" 
          rows="3" 
          placeholder="Brief summary for search engine results..."
          class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-1 focus:ring-primary text-sm text-ink outline-none transition-colors"
        ><?= e($page['meta_description'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Bottom Actions & Delete -->
    <div class="flex items-center justify-between pt-4">
      <?php if (!$isNew && !$isSystem): ?>
        <button 
          type="button" 
          @click="deleteModalOpen = true"
          class="px-4 py-2.5 text-sm font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-xl border border-rose-200 transition-colors min-h-[44px]"
        >
          Delete page
        </button>
      <?php else: ?>
        <div></div>
      <?php endif; ?>

      <div class="flex items-center gap-3">
        <a 
          href="/admin/pages" 
          class="px-5 py-2.5 text-sm font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-xl border border-border transition-colors min-h-[44px]"
        >
          Cancel
        </a>
        <button 
          type="submit" 
          class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
        >
          Save page
        </button>
      </div>
    </div>

  </form>

  <!-- Delete Modal for Non-System Pages -->
  <?php if (!$isNew && !$isSystem): ?>
    <div 
      x-show="deleteModalOpen" 
      x-cloak 
      class="fixed inset-0 z-50 overflow-y-auto" 
      role="dialog" 
      aria-modal="true"
    >
      <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm" @click="deleteModalOpen = false"></div>
      <div class="min-h-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-soft-lg space-y-4 border border-border" @click.away="deleteModalOpen = false">
          <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
          </div>
          <div class="text-center space-y-2">
            <h3 class="font-serif text-lg font-bold text-ink">Delete Page</h3>
            <p class="text-sm text-ink-muted">Are you sure you want to delete this page? This action cannot be undone.</p>
          </div>
          <form action="/admin/pages/<?= $pageId ?>/delete" method="POST" class="flex items-center justify-end gap-3 pt-2">
            <?= \App\Core\Csrf::field() ?>
            <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-sm font-semibold text-ink-muted hover:text-ink border border-border rounded-xl min-h-[44px]">Cancel</button>
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl min-h-[44px]">Delete page</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- TinyMCE Initialization -->
<script src="/assets/vendor/tinymce/tinymce.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

    tinymce.init({
      selector: '#page_content',
      license_key: 'gpl',
      height: 480,
      menubar: false,
      branding: false,
      promotion: false,
      plugins: 'link lists image blockquote wordcount paste',
      toolbar: 'undo redo | blocks | bold italic | link image blockquote | bullist numlist | removeformat',
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

          xhr.setRequestHeader('X-CSRF-Token', csrfToken);

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
              reject({ message: 'JSON parse error: ' + xhr.responseText, remove: true });
            }
          };

          xhr.onerror = () => {
            reject({ message: 'Network error occurred during image upload.', remove: true });
          };

          const formData = new FormData();
          formData.append('file', blobInfo.blob(), blobInfo.filename());
          formData.append('csrf_token', csrfToken);
          xhr.send(formData);
        });
      },
      content_style: 'body { font-family: Inter, sans-serif; font-size: 16px; line-height: 1.7; color: #1E232A; }'
    });
  });
</script>

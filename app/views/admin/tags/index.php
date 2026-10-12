<!-- NatConnect Foundation — Tags Management (Design Phase D1) -->
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ 
  deleteModalOpen: false,
  deleteTag: { id: 0, name: '', post_count: 0 }
}">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Tags</h1>
      <p class="text-xs text-ink-muted mt-1">Manage topics and causes displayed on the home page and across articles.</p>
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
      <button type="button" @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-900 p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <?php if (!empty($errors['general'])): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium">
      <?= e($errors['general']) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- Tags List Table (8 cols on lg) -->
    <div class="lg:col-span-8 bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
      <div class="p-5 border-b border-border flex items-center justify-between">
        <h2 class="font-serif text-lg font-bold text-ink">All Tags</h2>
        <span class="text-xs text-ink-muted font-medium"><?= count($tags) ?> total</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface/50 border-b border-border text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
              <th class="py-3 px-4">Name</th>
              <th class="py-3 px-4 text-center">Posts</th>
              <th class="py-3 px-4 text-center">Topic</th>
              <th class="py-3 px-4 text-center">Cause</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border text-sm">
            <?php if (empty($tags)): ?>
              <tr>
                <td colspan="5" class="py-8 text-center text-ink-muted">
                  No tags found. Tags are automatically created when editing posts or can be created here.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tags as $tag): ?>
                <tr class="hover:bg-surface/30 transition-colors">
                  <td class="py-3.5 px-4 font-medium text-ink">
                    <div class="flex items-center gap-3">
                      <?php if (!empty($tag['image_path'])): ?>
                        <img src="<?= e($tag['image_path']) ?>" alt="<?= e($tag['image_alt'] ?? $tag['name']) ?>" class="w-9 h-9 object-cover rounded-lg border border-border shrink-0">
                      <?php endif; ?>
                      <div>
                        <span class="font-semibold text-ink"><?= e($tag['name']) ?></span>
                        <span class="block text-xs text-ink-muted font-mono">/tag/<?= e($tag['slug']) ?></span>
                        <?php if (!empty($tag['description'])): ?>
                          <p class="text-xs text-ink-muted line-clamp-1 mt-0.5"><?= e($tag['description']) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= (int) $tag['post_count'] > 0 ? 'bg-primary/10 text-primary' : 'bg-surface text-ink-muted' ?>">
                      <?= formatNumberIN((int) $tag['post_count']) ?>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <?php if (!empty($tag['show_as_topic'])): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Yes
                      </span>
                    <?php else: ?>
                      <span class="text-xs text-ink-subtle">No</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <?php if (!empty($tag['show_as_cause'])): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Yes
                      </span>
                    <?php else: ?>
                      <span class="text-xs text-ink-subtle">No</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                    <a 
                      href="/admin/tags/<?= (int) $tag['id'] ?>/edit"
                      class="text-xs text-primary hover:text-primary-hover font-semibold px-2 py-1 rounded hover:bg-primary/5 transition-colors"
                    >
                      Edit
                    </a>
                    <button 
                      type="button"
                      @click="
                        deleteTag = {
                          id: <?= (int) $tag['id'] ?>,
                          name: '<?= e(addslashes($tag['name'])) ?>',
                          post_count: <?= (int) $tag['post_count'] ?>
                        };
                        deleteModalOpen = true;
                      "
                      class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1 rounded hover:bg-rose-50 transition-colors"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Add Tag Card (4 cols on lg) -->
    <div class="lg:col-span-4 bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-4">
      <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">New Tag</h2>

      <form action="/admin/tags" method="POST" enctype="multipart/form-data" class="space-y-4">
        <?= \App\Core\Csrf::field() ?>

        <!-- Tag Name -->
        <div>
          <label for="new_tag_name" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Name <span class="text-rose-500">*</span>
          </label>
          <input 
            type="text" 
            id="new_tag_name" 
            name="name" 
            required 
            value="<?= e($old['name'] ?? '') ?>"
            placeholder="e.g. Mangroves" 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['name']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
          <?php if (isset($errors['name'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['name']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Tag Slug -->
        <div>
          <label for="new_tag_slug" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Slug
          </label>
          <input 
            type="text" 
            id="new_tag_slug" 
            name="slug" 
            value="<?= e($old['slug'] ?? '') ?>"
            placeholder="Leave blank to auto-generate" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink font-mono"
          >
        </div>

        <!-- Description -->
        <div>
          <label for="new_tag_desc" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Description
          </label>
          <textarea 
            id="new_tag_desc" 
            name="description" 
            rows="2"
            placeholder="Required if designated as a cause" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ><?= e($old['description'] ?? '') ?></textarea>
        </div>

        <!-- Home Page Toggles -->
        <div class="space-y-2 pt-1 border-t border-border">
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              type="checkbox" 
              name="show_as_topic" 
              value="1" 
              <?= !empty($old['show_as_topic']) ? 'checked' : '' ?>
              class="rounded border-border text-primary focus:ring-primary h-4 w-4"
            >
            <span class="text-xs font-medium text-ink">Show as topic chip on home</span>
          </label>

          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              type="checkbox" 
              name="show_as_cause" 
              value="1" 
              <?= !empty($old['show_as_cause']) ? 'checked' : '' ?>
              class="rounded border-border text-primary focus:ring-primary h-4 w-4"
            >
            <span class="text-xs font-medium text-ink">Show as a cause on home</span>
          </label>
          <p class="text-[11px] text-ink-subtle">Causes require an image and description. Edit the tag to upload imagery.</p>
        </div>

        <button 
          type="submit" 
          class="w-full px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
        >
          Add Tag
        </button>
      </form>
    </div>

  </div>

  <!-- Delete Tag Modal -->
  <div 
    x-show="deleteModalOpen" 
    x-cloak 
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true"
  >
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div 
        x-show="deleteModalOpen" 
        x-transition:enter="ease-out duration-300" 
        x-transition:enter-start="opacity-0" 
        x-transition:enter-end="opacity-100" 
        x-transition:leave="ease-in duration-200" 
        x-transition:leave-start="opacity-100" 
        x-transition:leave-end="opacity-0" 
        @click="deleteModalOpen = false" 
        class="fixed inset-0 bg-ink/40 backdrop-blur-sm transition-opacity" 
        aria-hidden="true"
      ></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div 
        x-show="deleteModalOpen" 
        x-transition:enter="ease-out duration-300" 
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
        x-transition:leave="ease-in duration-200" 
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-border"
      >
        <form :action="'/admin/tags/' + deleteTag.id + '/delete'" method="POST">
          <?= \App\Core\Csrf::field() ?>

          <div class="p-6">
            <h3 class="font-serif text-lg font-bold text-ink mb-2">Delete Tag</h3>
            <p class="text-sm text-ink-muted mb-4">
              Are you sure you want to delete tag <strong class="text-ink" x-text="deleteTag.name"></strong>?
            </p>
            <div class="p-3 bg-surface rounded-xl border border-border text-xs text-ink-muted">
              <p>Deleting a tag removes tag links only. <strong>Posts will never be deleted.</strong></p>
            </div>
          </div>

          <div class="bg-surface/50 px-6 py-4 border-t border-border flex items-center justify-end gap-3">
            <button 
              type="button" 
              @click="deleteModalOpen = false" 
              class="px-4 py-2 border border-border text-ink hover:bg-surface text-sm font-semibold rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl transition-colors"
            >
              Confirm Delete
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

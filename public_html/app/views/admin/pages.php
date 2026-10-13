<!-- NatConnect Foundation — Pages Management -->
<div class="space-y-6 max-w-7xl mx-auto" x-data="{
  deleteModalOpen: false,
  pageToDelete: { id: 0, title: '' },
  confirmDelete(id, title) {
    this.pageToDelete = { id, title };
    this.deleteModalOpen = true;
  }
}">

  <!-- Header & Primary CTA -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Pages</h1>
      <p class="text-xs text-ink-muted mt-1">Manage public website pages and header navigation links.</p>
    </div>
    <a 
      href="/admin/pages/new" 
      class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px] self-start sm:self-auto"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      <span>New page</span>
    </a>
  </div>

  <!-- Flash Messages -->
  <?php if (!empty($success)): ?>
    <div class="p-4 rounded-xl bg-accent/10 border border-accent/20 text-accent-strong text-sm font-medium flex items-center justify-between">
      <span><?= e($success) ?></span>
      <button type="button" @click="$el.parentElement.remove()" class="text-accent-strong hover:text-accent-hover p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium flex items-center justify-between">
      <span><?= e($error) ?></span>
      <button type="button" @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-900 p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
  <?php endif; ?>

  <!-- Pages Table Card -->
  <div class="bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="bg-surface border-b border-border text-xs uppercase tracking-wider text-ink-muted">
            <th class="py-3.5 px-4 font-semibold">Title</th>
            <th class="py-3.5 px-4 font-semibold">Status</th>
            <th class="py-3.5 px-4 font-semibold">In Menu</th>
            <th class="py-3.5 px-4 font-semibold">Updated</th>
            <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <?php if (empty($pages)): ?>
            <tr>
              <td colspan="5" class="py-12 text-center text-ink-muted">
                No pages found. Click "New page" to create one.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($pages as $p): ?>
              <tr class="hover:bg-surface/50 transition-colors">
                <!-- Title & Slug -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2">
                    <a href="/admin/pages/<?= (int) $p['id'] ?>/edit" class="font-medium text-ink hover:text-primary transition-colors">
                      <?= e($p['title']) ?>
                    </a>
                    <?php if (!empty($p['is_system'])): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-primary/10 text-primary">
                        System
                      </span>
                    <?php endif; ?>
                  </div>
                  <span class="text-xs text-ink-muted font-mono">/<?= e($p['slug']) ?></span>
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4">
                  <?php if ($p['status'] === 'published'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-accent/10 text-accent-strong">
                      Published
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-ink-muted">
                      Draft
                    </span>
                  <?php endif; ?>
                </td>

                <!-- In Menu -->
                <td class="py-3.5 px-4">
                  <?php if (!empty($p['show_in_nav'])): ?>
                    <span class="inline-flex items-center gap-1.5 text-xs text-accent-strong font-medium">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                      Yes <?= !empty($p['nav_label']) ? '(' . e($p['nav_label']) . ')' : '' ?>
                      <span class="text-ink-subtle text-[11px] font-mono">#<?= (int) $p['nav_order'] ?></span>
                    </span>
                  <?php else: ?>
                    <span class="text-xs text-ink-subtle">No</span>
                  <?php endif; ?>
                </td>

                <!-- Updated Date -->
                <td class="py-3.5 px-4 text-xs text-ink-muted whitespace-nowrap">
                  <?= formatDate($p['updated_at'] ?? $p['created_at']) ?>
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div class="inline-flex items-center gap-2">
                    <!-- Preview -->
                    <a 
                      href="/admin/pages/<?= (int) $p['id'] ?>/preview" 
                      target="_blank" 
                      class="px-2.5 py-1.5 text-xs font-semibold text-ink-muted hover:text-primary hover:bg-surface rounded-lg border border-border transition-colors"
                      title="Preview page"
                    >
                      Preview
                    </a>

                    <!-- Edit -->
                    <a 
                      href="/admin/pages/<?= (int) $p['id'] ?>/edit" 
                      class="px-2.5 py-1.5 text-xs font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-lg border border-border transition-colors"
                      title="Edit page"
                    >
                      Edit
                    </a>

                    <!-- Delete (Non-system pages only) -->
                    <?php if (empty($p['is_system'])): ?>
                      <button 
                        type="button" 
                        @click="confirmDelete(<?= (int) $p['id'] ?>, '<?= e(addslashes($p['title'])) ?>')"
                        class="px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg border border-rose-200 transition-colors"
                        title="Delete page"
                      >
                        Delete
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Delete Confirmation Modal (Alpine.js) -->
  <div 
    x-show="deleteModalOpen" 
    x-cloak 
    class="fixed inset-0 z-50 overflow-y-auto" 
    role="dialog" 
    aria-modal="true" 
    aria-labelledby="delete-modal-title"
  >
    <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" @click="deleteModalOpen = false"></div>
    <div class="min-h-full flex items-center justify-center p-4">
      <div 
        class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-soft-lg space-y-4 border border-border"
        @click.away="deleteModalOpen = false"
      >
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
        </div>

        <div class="text-center space-y-2">
          <h3 id="delete-modal-title" class="font-serif text-lg font-bold text-ink">Delete Page</h3>
          <p class="text-sm text-ink-muted">
            Are you sure you want to delete <span class="font-semibold text-ink" x-text="pageToDelete.title"></span>? This action cannot be undone.
          </p>
        </div>

        <form :action="'/admin/pages/' + pageToDelete.id + '/delete'" method="POST" class="flex items-center justify-end gap-3 pt-2">
          <?= \App\Core\Csrf::field() ?>
          <button 
            type="button" 
            @click="deleteModalOpen = false"
            class="px-4 py-2 text-sm font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-xl border border-border transition-colors min-h-[44px]"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-soft transition-colors min-h-[44px]"
          >
            Delete page
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<!-- NatConnect Foundation — Categories Management -->
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ 
  editModalOpen: false, 
  deleteModalOpen: false,
  editCategory: { id: 0, name: '', slug: '', description: '' },
  deleteCategory: { id: 0, name: '', post_count: 0 },
  targetCategory: ''
}">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Categories</h1>
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

    <!-- Categories List Table (8 cols on lg) -->
    <div class="lg:col-span-8 bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
      <div class="p-5 border-b border-border flex items-center justify-between">
        <h2 class="font-serif text-lg font-bold text-ink">All Categories</h2>
        <span class="text-xs text-ink-muted font-medium"><?= count($categories) ?> total</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface/50 border-b border-border text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
              <th class="py-3 px-4">Name</th>
              <th class="py-3 px-4">Slug</th>
              <th class="py-3 px-4 text-center">Order</th>
              <th class="py-3 px-4 text-center">Posts</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border text-sm">
            <?php if (empty($categories)): ?>
              <tr>
                <td colspan="5" class="py-8 text-center text-ink-muted">
                  No categories found. Create your first category using the form.
                </td>
              </tr>
            <?php else: ?>
              <?php $catTotal = count($categories); ?>
              <?php foreach ($categories as $index => $cat): ?>
                <?php
                  $isFirst = ($index === 0);
                  $isLast = ($index === $catTotal - 1);
                ?>
                <tr class="hover:bg-surface/30 transition-colors">
                  <td class="py-3.5 px-4 font-medium text-ink">
                    <?= e($cat['name']) ?>
                    <?php if (!empty($cat['description'])): ?>
                      <p class="text-xs text-ink-muted font-normal line-clamp-1 mt-0.5"><?= e($cat['description']) ?></p>
                    <?php endif; ?>
                  </td>
                  <td class="py-3.5 px-4 text-ink-muted font-mono text-xs">
                    <?= e($cat['slug']) ?>
                  </td>
                  <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1">
                      <form action="/admin/categories/<?= (int) $cat['id'] ?>/move" method="POST" class="inline">
                        <?= \App\Core\Csrf::field() ?>
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
                      <span class="text-xs font-mono text-ink-muted px-1"><?= (int) ($cat['sort_order'] ?? ($index + 1)) ?></span>
                      <form action="/admin/categories/<?= (int) $cat['id'] ?>/move" method="POST" class="inline">
                        <?= \App\Core\Csrf::field() ?>
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
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <?php
                      $catBadge = $cat['post_count'] > 0 
                        ? 'bg-primary/10 text-primary' 
                        : 'bg-surface text-ink-muted';
                    ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $catBadge ?>">
                      <?= formatNumberIN($cat['post_count']) ?>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                    <button 
                      type="button"
                      @click="
                        editCategory = {
                          id: <?= (int) $cat['id'] ?>,
                          name: '<?= e(addslashes($cat['name'])) ?>',
                          slug: '<?= e(addslashes($cat['slug'])) ?>',
                          description: '<?= e(addslashes($cat['description'] ?? '')) ?>'
                        };
                        editModalOpen = true;
                      "
                      class="inline-flex items-center text-xs font-semibold text-primary hover:text-primary-hover px-2.5 py-1.5 rounded-lg hover:bg-primary/5 transition-colors"
                    >
                      Edit
                    </button>

                    <button 
                      type="button"
                      @click="
                        deleteCategory = {
                          id: <?= (int) $cat['id'] ?>,
                          name: '<?= e(addslashes($cat['name'])) ?>',
                          post_count: <?= (int) $cat['post_count'] ?>
                        };
                        targetCategory = '';
                        deleteModalOpen = true;
                      "
                      class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-800 px-2.5 py-1.5 rounded-lg hover:bg-rose-50 transition-colors"
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

    <!-- Create Category Form (4 cols on lg) -->
    <div class="lg:col-span-4 bg-white border border-border rounded-2xl p-6 shadow-soft-sm sticky top-6">
      <h2 class="font-serif text-lg font-bold text-ink mb-4">Add Category</h2>

      <form action="/admin/categories" method="POST" class="space-y-4">
        <?= \App\Core\Csrf::field() ?>

        <div>
          <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">Name <span class="text-rose-500">*</span></label>
          <input 
            type="text" 
            id="name" 
            name="name" 
            value="<?= e($old['name'] ?? '') ?>" 
            required 
            placeholder="Category name"
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['name']) ? 'border-rose-400 bg-rose-50/30' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
          >
          <?php if (isset($errors['name'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['name']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="slug" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">Slug (optional)</label>
          <input 
            type="text" 
            id="slug" 
            name="slug" 
            value="<?= e($old['slug'] ?? '') ?>" 
            placeholder=""
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['slug']) ? 'border-rose-400 bg-rose-50/30' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm font-mono text-ink placeholder:text-ink-subtle"
          >
          <p class="text-[11px] text-ink-subtle mt-1">Lowercase letters, numbers, and hyphens only.</p>
          <?php if (isset($errors['slug'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['slug']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">Description</label>
          <textarea 
            id="description" 
            name="description" 
            rows="3" 
            placeholder="Optional description"
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
          ><?= e($old['description'] ?? '') ?></textarea>
        </div>

        <button 
          type="submit" 
          class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
        >
          Add Category
        </button>
      </form>
    </div>

  </div>

  <!-- Edit Category Modal -->
  <div 
    x-show="editModalOpen" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/60 backdrop-blur-sm"
    @keydown.escape.window="editModalOpen = false"
  >
    <div 
      @click.away="editModalOpen = false" 
      class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-border"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-serif text-lg font-bold text-ink">Edit Category</h3>
        <button type="button" @click="editModalOpen = false" class="text-ink-muted hover:text-ink p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form :action="'/admin/categories/' + editCategory.id + '/edit'" method="POST" class="space-y-4">
        <?= \App\Core\Csrf::field() ?>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">Category Name <span class="text-rose-500">*</span></label>
          <input 
            type="text" 
            name="name" 
            x-model="editCategory.name" 
            required 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">Slug <span class="text-rose-500">*</span></label>
          <input 
            type="text" 
            name="slug" 
            x-model="editCategory.slug" 
            required 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm font-mono text-ink"
          >
          <p class="text-[11px] text-ink-subtle mt-1">Changing slug may affect category links.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">Description</label>
          <textarea 
            name="description" 
            x-model="editCategory.description" 
            rows="3" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button 
            type="button" 
            @click="editModalOpen = false" 
            class="px-4 py-2 text-sm font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            class="px-5 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors"
          >
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Category Modal (Handles Reassignment when posts > 0) -->
  <div 
    x-show="deleteModalOpen" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/60 backdrop-blur-sm"
    @keydown.escape.window="deleteModalOpen = false"
  >
    <div 
      @click.away="deleteModalOpen = false" 
      class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-border"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-serif text-lg font-bold text-ink">Delete Category</h3>
        <button type="button" @click="deleteModalOpen = false" class="text-ink-muted hover:text-ink p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form :action="'/admin/categories/' + deleteCategory.id + '/delete'" method="POST" class="space-y-4">
        <?= \App\Core\Csrf::field() ?>

        <template x-if="deleteCategory.post_count === 0">
          <div>
            <p class="text-sm text-ink mb-2">
              Are you sure you want to delete category <strong class="text-ink" x-text="deleteCategory.name"></strong>?
            </p>
            <p class="text-xs text-ink-muted">This category has no assigned posts. This action cannot be undone.</p>
          </div>
        </template>

        <template x-if="deleteCategory.post_count > 0">
          <div class="space-y-4">
            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs">
              <span class="font-semibold" x-text="deleteCategory.name"></span> contains 
              <span class="font-bold" x-text="deleteCategory.post_count"></span> post(s). You must move these posts to another category before deleting.
            </div>

            <div>
              <label for="move_to_category_id" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">
                Move posts to: <span class="text-rose-500">*</span>
              </label>
              <select 
                id="move_to_category_id" 
                name="move_to_category_id" 
                x-model="targetCategory" 
                required 
                class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white"
              >
                <option value="">-- Choose destination category --</option>
                <?php foreach ($categories as $optCat): ?>
                  <template x-if="deleteCategory.id !== <?= (int) $optCat['id'] ?>">
                    <option value="<?= (int) $optCat['id'] ?>"><?= e($optCat['name']) ?></option>
                  </template>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </template>

        <div class="flex items-center justify-end gap-3 pt-3">
          <button 
            type="button" 
            @click="deleteModalOpen = false" 
            class="px-4 py-2 text-sm font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-soft transition-colors"
          >
            Confirm Delete
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<!-- NatConnect Foundation — Editorial Posts Management -->
<div class="space-y-6 max-w-7xl mx-auto" x-data="{
  selectedIds: [],
  selectAll: false,
  deleteModalOpen: false,
  bulkDeleteModalOpen: false,
  postToDelete: { id: 0, title: '' },
  
  toggleAll() {
    this.selectAll = !this.selectAll;
    if (this.selectAll) {
      const checkboxes = document.querySelectorAll('.post-checkbox');
      this.selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    } else {
      this.selectedIds = [];
    }
  },
  
  toggleItem(id) {
    id = parseInt(id);
    const index = this.selectedIds.indexOf(id);
    if (index > -1) {
      this.selectedIds.splice(index, 1);
      this.selectAll = false;
    } else {
      this.selectedIds.push(id);
    }
  }
}">

  <!-- Header & Primary CTA -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Posts</h1>
    </div>
    <a 
      href="/admin/posts/new" 
      class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px] self-start sm:self-auto"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      <span>New post</span>
    </a>
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

  <!-- Filters & Search Toolbar -->
  <div class="bg-white border border-border rounded-2xl p-4 sm:p-5 shadow-soft-sm">
    <form action="/admin/posts" method="GET" class="flex flex-col lg:flex-row lg:items-center gap-4 justify-between">
      
      <!-- Status Tabs / Dropdown with Effective Status Counts -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs font-semibold">
        <?php
          $statusTabs = [
            'all'       => ['label' => 'All Posts',  'count' => $statusCounts['all'] ?? null],
            'published' => ['label' => 'Published',  'count' => $statusCounts['published'] ?? null],
            'draft'     => ['label' => 'Drafts',     'count' => $statusCounts['draft'] ?? null],
            'scheduled' => ['label' => 'Scheduled',  'count' => $statusCounts['scheduled'] ?? null],
          ];
        ?>
        <?php foreach ($statusTabs as $stKey => $tab): ?>
          <?php
            $isActive = ($currentStatus === $stKey);
            $queryUrl = '/admin/posts?' . http_build_query(array_merge($_GET, ['status' => $stKey, 'page' => 1]));
            $tabClasses = $isActive 
              ? 'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-colors whitespace-nowrap bg-primary text-white shadow-soft-sm' 
              : 'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-colors whitespace-nowrap bg-surface text-ink-muted hover:text-ink hover:bg-gray-100';
            $badgeClasses = $isActive
              ? 'bg-white/20 text-white'
              : 'bg-black/5 text-ink-muted';
          ?>
          <a 
            href="<?= e($queryUrl) ?>"
            class="<?= $tabClasses ?>"
          >
            <span><?= e($tab['label']) ?></span>
            <?php if (isset($tab['count']) && $tab['count'] !== null): ?>
              <span class="text-[10px] font-mono px-1.5 py-0.5 rounded-full <?= $badgeClasses ?>"><?= (int) $tab['count'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Category Filter & Search Input -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <input type="hidden" name="status" value="<?= e($currentStatus) ?>">

        <select 
          name="category" 
          onchange="this.form.submit()"
          class="px-3.5 py-2 rounded-xl border border-border text-sm text-ink bg-white focus:outline-none focus:ring-2 focus:ring-primary min-h-[40px]"
        >
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= $currentCategory === (int) $cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="relative min-w-[240px]">
          <input 
            type="search" 
            name="search" 
            value="<?= e($currentSearch) ?>"
            placeholder="Search by title..."
            class="w-full pl-9 pr-4 py-2 rounded-xl border border-border text-sm text-ink bg-white focus:outline-none focus:ring-2 focus:ring-primary min-h-[40px] placeholder:text-ink-subtle"
          >
          <svg class="w-4 h-4 text-ink-subtle absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/></svg>
        </div>

        <button 
          type="submit" 
          class="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl transition-colors shrink-0"
        >
          Filter
        </button>

        <?php if ($currentStatus !== 'all' || $currentCategory > 0 || $currentSearch !== ''): ?>
          <a 
            href="/admin/posts" 
            class="px-3 py-2 text-xs font-semibold text-ink-muted hover:text-ink rounded-xl border border-border flex items-center justify-center shrink-0 hover:bg-surface transition-colors"
          >
            Clear
          </a>
        <?php endif; ?>
      </div>

    </form>
  </div>

  <!-- Bulk Actions Floating Bar (Shows when 1+ checkboxes selected) -->
  <div 
    x-show="selectedIds.length > 0" 
    x-cloak 
    class="p-3 bg-ink text-white rounded-xl flex items-center justify-between shadow-lg"
  >
    <div class="flex items-center gap-3 text-sm">
      <span class="font-semibold text-accent" x-text="selectedIds.length + ' post(s) selected'"></span>
    </div>
    <div class="flex items-center gap-2">
      <button 
        type="button" 
        @click="bulkDeleteModalOpen = true"
        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <span>Delete Selected</span>
      </button>
      <button 
        type="button" 
        @click="selectedIds = []; selectAll = false"
        class="px-3 py-1.5 text-xs text-white/70 hover:text-white rounded-lg transition-colors"
      >
        Deselect
      </button>
    </div>
  </div>

  <!-- Posts Listing Container -->
  <div class="bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
    
    <!-- Desktop Table View (Hidden on mobile) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface/50 border-b border-border text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
            <th class="py-3.5 px-4 w-10 text-center">
              <input 
                type="checkbox" 
                :checked="selectAll" 
                @change="toggleAll()" 
                class="rounded border-border text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                aria-label="Select all posts on page"
              >
            </th>
            <th class="py-3.5 px-4">Title</th>
            <th class="py-3.5 px-4">Status</th>
            <th class="py-3.5 px-4">Category</th>
            <th class="py-3.5 px-4">Author</th>
            <th class="py-3.5 px-4">Date</th>
            <th class="py-3.5 px-4 text-center">Views</th>
            <th class="py-3.5 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border text-sm">
          <?php if (empty($posts)): ?>
            <tr>
              <td colspan="8" class="py-12 text-center text-ink-muted">
                <svg class="w-10 h-10 mx-auto text-ink-subtle mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                <p class="font-medium">No posts match your filters.</p>
                <p class="text-xs text-ink-subtle mt-1">Try resetting filters or write a new post.</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($posts as $post): ?>
              <?php
                $effStatus = $post['effective_status'] ?? effectiveStatus($post);
                $statusBadge = match($effStatus) {
                  'published' => 'bg-accent/10 text-accent-strong border-accent/20',
                  'scheduled' => 'bg-primary/10 text-primary border-primary/20',
                  default     => 'bg-surface text-ink-muted border-border',
                };
              ?>
              <tr class="hover:bg-surface/30 transition-colors">
                <td class="py-3.5 px-4 text-center">
                  <input 
                    type="checkbox" 
                    class="post-checkbox rounded border-border text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                    value="<?= (int) $post['id'] ?>"
                    :checked="selectedIds.includes(<?= (int) $post['id'] ?>)"
                    @change="toggleItem(<?= (int) $post['id'] ?>)"
                  >
                </td>
                <td class="py-3.5 px-4">
                  <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="font-serif font-bold text-ink hover:text-primary transition-colors line-clamp-1">
                    <?= e($post['title']) ?>
                  </a>
                  <span class="text-xs text-ink-subtle font-mono mt-0.5 block">/post/<?= e($post['slug']) ?></span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $statusBadge ?>">
                    <?= ucfirst($effStatus) ?>
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap text-ink-muted">
                  <?= e($post['category_name'] ?? 'Uncategorized') ?>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap text-xs text-ink-muted">
                  <?= e($post['author_name'] ?: 'NatConnect Foundation') ?>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap text-xs text-ink-muted">
                  <?php if ($effStatus === 'scheduled'): ?>
                    <span class="text-ink-muted font-medium" title="Scheduled date">
                      <?= formatDate($post['published_at'], 'd M Y, H:i') ?>
                    </span>
                  <?php elseif ($post['published_at']): ?>
                    <span class="text-ink-muted"><?= formatDate($post['published_at']) ?></span>
                  <?php else: ?>
                    <span class="text-ink-muted"><?= formatDate($post['created_at']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap font-medium text-ink">
                  <?= formatNumberIN($post['views']) ?>
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                  <a 
                    href="/admin/posts/<?= (int) $post['id'] ?>/edit" 
                    class="inline-flex items-center text-xs font-semibold text-primary hover:text-primary-hover px-2.5 py-1.5 rounded-lg hover:bg-primary/5 transition-colors"
                  >
                    Edit
                  </a>
                  <button 
                    type="button" 
                    @click="
                      postToDelete = { id: <?= (int) $post['id'] ?>, title: '<?= e(addslashes($post['title'])) ?>' };
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

    <!-- Mobile Stacked Card View (Shown on < md) -->
    <div class="md:hidden divide-y divide-border">
      <?php if (empty($posts)): ?>
        <div class="p-8 text-center text-ink-muted">
          No posts match your filters.
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <?php
            $effStatus = $post['effective_status'] ?? effectiveStatus($post);
            $statusBadge = match($effStatus) {
              'published' => 'bg-accent/10 text-accent-strong border-accent/20',
              'scheduled' => 'bg-primary/10 text-primary border-primary/20',
              default     => 'bg-surface text-ink-muted border-border',
            };
          ?>
          <div class="p-4 space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3">
                <input 
                  type="checkbox" 
                  class="post-checkbox rounded border-border text-primary focus:ring-primary w-4 h-4 cursor-pointer mt-1"
                  value="<?= (int) $post['id'] ?>"
                  :checked="selectedIds.includes(<?= (int) $post['id'] ?>)"
                  @change="toggleItem(<?= (int) $post['id'] ?>)"
                >
                <div>
                  <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="font-serif font-bold text-ink hover:text-primary transition-colors text-base line-clamp-2">
                    <?= e($post['title']) ?>
                  </a>
                  <div class="flex items-center gap-2 mt-1 flex-wrap text-xs text-ink-muted">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border <?= $statusBadge ?>">
                      <?= ucfirst($effStatus) ?>
                    </span>
                    <span>•</span>
                    <span><?= e($post['category_name'] ?? 'Uncategorized') ?></span>
                    <span>•</span>
                    <span><?= formatNumberIN($post['views']) ?> views</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between text-xs text-ink-muted pt-2 border-t border-border">
              <span><?= formatDate($post['published_at'] ?? $post['created_at']) ?></span>
              <div class="space-x-3">
                <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="font-semibold text-primary hover:text-primary-hover">Edit</a>
                <button 
                  type="button" 
                  @click="
                    postToDelete = { id: <?= (int) $post['id'] ?>, title: '<?= e(addslashes($post['title'])) ?>' };
                    deleteModalOpen = true;
                  "
                  class="font-semibold text-rose-600 hover:text-rose-800"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Pagination Footer -->
    <?php if ($totalPages > 1): ?>
      <div class="p-4 sm:px-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-ink-muted">
        <div>
          Showing <span class="font-semibold text-ink"><?= ($page - 1) * $perPage + 1 ?></span> to 
          <span class="font-semibold text-ink"><?= min($page * $perPage, $total) ?></span> of 
          <span class="font-semibold text-ink"><?= $total ?></span> posts
        </div>

        <div class="flex items-center gap-1.5">
          <?php if ($page > 1): ?>
            <a 
              href="/admin/posts?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
              class="px-3 py-1.5 rounded-lg border border-border text-ink hover:bg-surface font-medium transition-colors"
            >
              Previous
            </a>
          <?php endif; ?>

          <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <?php
              $pageClasses = ($p === $page)
                ? 'px-3 py-1.5 rounded-lg font-medium transition-colors bg-primary text-white shadow-soft-sm'
                : 'px-3 py-1.5 rounded-lg font-medium transition-colors border border-border text-ink hover:bg-surface';
            ?>
            <a 
              href="/admin/posts?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
              class="<?= $pageClasses ?>"
            >
              <?= $p ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a 
              href="/admin/posts?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
              class="px-3 py-1.5 rounded-lg border border-border text-ink hover:bg-surface font-medium transition-colors"
            >
              Next
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <!-- Single Delete Confirmation Modal -->
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
        <h3 class="font-serif text-lg font-bold text-ink">Delete Post</h3>
        <button type="button" @click="deleteModalOpen = false" class="text-ink-muted hover:text-ink p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <p class="text-sm text-ink mb-2">
        Are you sure you want to delete <strong class="text-ink" x-text="postToDelete.title"></strong>?
      </p>
      <p class="text-xs text-ink-muted mb-6">
        This permanently removes the post along with all associated comments, views, and likes.
      </p>

      <form :action="'/admin/posts/' + postToDelete.id + '/delete'" method="POST" class="flex items-center justify-end gap-3">
        <?= \App\Core\Csrf::field() ?>
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
          Delete
        </button>
      </form>
    </div>
  </div>

  <!-- Bulk Delete Confirmation Modal -->
  <div 
    x-show="bulkDeleteModalOpen" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/60 backdrop-blur-sm"
    @keydown.escape.window="bulkDeleteModalOpen = false"
  >
    <div 
      @click.away="bulkDeleteModalOpen = false" 
      class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-border"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-serif text-lg font-bold text-ink">Delete Selected Posts</h3>
        <button type="button" @click="bulkDeleteModalOpen = false" class="text-ink-muted hover:text-ink p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <p class="text-sm text-ink mb-2">
        Are you sure you want to delete <strong class="text-rose-600" x-text="selectedIds.length"></strong> selected post(s)?
      </p>
      <p class="text-xs text-ink-muted mb-6">
        This action cannot be undone. All related comments and metrics will be permanently deleted.
      </p>

      <form action="/admin/posts/bulk-delete" method="POST" class="flex items-center justify-end gap-3">
        <?= \App\Core\Csrf::field() ?>
        <template x-for="id in selectedIds" :key="id">
          <input type="hidden" name="ids[]" :value="id">
        </template>

        <button 
          type="button" 
          @click="bulkDeleteModalOpen = false" 
          class="px-4 py-2 text-sm font-semibold text-ink-muted hover:text-ink hover:bg-surface rounded-xl transition-colors"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-soft transition-colors"
        >
          Delete All Selected
        </button>
      </form>
    </div>
  </div>

</div>

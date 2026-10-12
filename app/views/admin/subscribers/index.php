<!-- NatConnect Foundation — Subscribers (Design Phase D1) -->
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ 
  deleteModalOpen: false,
  deleteSub: { id: 0, email: '' }
}">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Subscribers</h1>
      <p class="text-xs text-ink-muted mt-1">Reader email list collected with explicit consent. Stored securely without automated sending.</p>
    </div>

    <!-- Actions: Export CSV -->
    <div class="flex items-center gap-3">
      <a 
        href="/admin/subscribers/export" 
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-border hover:bg-surface text-ink text-sm font-semibold rounded-xl shadow-soft-sm transition-colors min-h-[44px]"
      >
        <svg class="w-4 h-4 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        <span>Export CSV</span>
      </a>
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

  <?php if (!empty($_SESSION['flash_errors']['general'])): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium">
      <?= e($_SESSION['flash_errors']['general']) ?>
    </div>
  <?php endif; ?>

  <!-- Search & Stats Filter Row -->
  <div class="bg-white border border-border rounded-2xl p-4 shadow-soft-sm flex flex-col sm:flex-row items-center justify-between gap-4">
    <form action="/admin/subscribers" method="GET" class="w-full sm:w-auto flex-1 max-w-md">
      <div class="relative">
        <input 
          type="search" 
          name="q" 
          value="<?= e($search ?? '') ?>" 
          placeholder="Search by email..." 
          class="w-full pl-9 pr-4 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink placeholder:text-ink-subtle"
        >
        <svg class="w-4 h-4 text-ink-subtle absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
      </div>
    </form>

    <div class="text-xs text-ink-muted flex items-center gap-3">
      <?php if (!empty($search)): ?>
        <a href="/admin/subscribers" class="text-primary hover:underline font-medium">Clear search</a>
        <span>•</span>
      <?php endif; ?>
      <span>Total Subscribers: <strong class="text-ink font-semibold"><?= formatNumberIN($totalCount) ?></strong></span>
    </div>
  </div>

  <!-- Subscribers Table -->
  <div class="bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface/50 border-b border-border text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
            <th class="py-3 px-4">Email</th>
            <th class="py-3 px-4">Consent Date</th>
            <th class="py-3 px-4">Source</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border text-sm">
          <?php if (empty($subscribers)): ?>
            <tr>
              <td colspan="4" class="py-8 text-center text-ink-muted">
                <?= !empty($search) ? 'No subscribers match your search query.' : 'No subscribers yet.' ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($subscribers as $sub): ?>
              <tr class="hover:bg-surface/30 transition-colors">
                <td class="py-3.5 px-4 font-medium text-ink font-mono text-xs">
                  <?= e($sub['email']) ?>
                </td>
                <td class="py-3.5 px-4 text-ink-muted text-xs">
                  <?= e(date('d M Y, h:i A', strtotime($sub['consent_at']))) ?>
                </td>
                <td class="py-3.5 px-4 text-xs">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-surface text-ink-muted border border-border">
                    <?= e($sub['source']) ?>
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button 
                    type="button"
                    @click="
                      deleteSub = {
                        id: <?= (int) $sub['id'] ?>,
                        email: '<?= e(addslashes($sub['email'])) ?>'
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="p-4 border-t border-border flex items-center justify-between text-xs text-ink-muted">
        <span>Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> items)</span>
        <div class="flex items-center gap-1.5">
          <?php if ($page > 1): ?>
            <a 
              href="/admin/subscribers?page=<?= $page - 1 ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
              class="px-3 py-1.5 rounded-lg border border-border hover:bg-surface text-ink font-semibold transition-colors"
            >
              Previous
            </a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <a 
              href="/admin/subscribers?page=<?= $page + 1 ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
              class="px-3 py-1.5 rounded-lg border border-border hover:bg-surface text-ink font-semibold transition-colors"
            >
              Next
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Delete Modal -->
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
        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-border"
      >
        <form :action="'/admin/subscribers/' + deleteSub.id + '/delete'" method="POST">
          <?= \App\Core\Csrf::field() ?>

          <div class="p-6">
            <h3 class="font-serif text-lg font-bold text-ink mb-2">Delete Subscriber</h3>
            <p class="text-sm text-ink-muted">
              Are you sure you want to remove <strong class="text-ink font-mono text-xs" x-text="deleteSub.email"></strong> from the subscriber list?
            </p>
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

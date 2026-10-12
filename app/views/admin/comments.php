<!-- NatConnect Foundation — Comments Moderation View -->
<div class="space-y-6 max-w-7xl mx-auto" x-data="{
  selectedIds: [],
  selectAll: false,
  bulkAction: '',
  
  toggleAll() {
    this.selectAll = !this.selectAll;
    if (this.selectAll) {
      const checkboxes = document.querySelectorAll('.comment-checkbox');
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
  },

  confirmBulk(action) {
    if (this.selectedIds.length === 0) return;
    if (action === 'delete') {
      if (!confirm('Are you sure you want to permanently delete ' + this.selectedIds.length + ' comment(s)?')) {
        return;
      }
    }
    this.bulkAction = action;
    this.$nextTick(() => {
      this.$refs.bulkForm.submit();
    });
  }
}">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Comments</h1>
    </div>
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

  <!-- Tabs Navigation -->
  <div class="border-b border-border">
    <nav class="flex space-x-6" aria-label="Comment Status Tabs">
      <!-- Pending Tab -->
      <a 
        href="/admin/comments?status=pending" 
        class="pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition-colors <?= $currentTab === 'pending' ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink hover:border-border' ?>"
      >
        <span>Pending</span>
        <span class="px-2 py-0.5 rounded-full text-xs <?= $currentTab === 'pending' ? 'bg-primary/10 text-primary' : 'bg-surface-alt text-ink-muted' ?>">
          <?= (int) ($counts['pending'] ?? 0) ?>
        </span>
      </a>

      <!-- Approved Tab -->
      <a 
        href="/admin/comments?status=approved" 
        class="pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition-colors <?= $currentTab === 'approved' ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink hover:border-border' ?>"
      >
        <span>Approved</span>
        <span class="px-2 py-0.5 rounded-full text-xs <?= $currentTab === 'approved' ? 'bg-primary/10 text-primary' : 'bg-surface-alt text-ink-muted' ?>">
          <?= (int) ($counts['approved'] ?? 0) ?>
        </span>
      </a>

      <!-- Spam Tab -->
      <a 
        href="/admin/comments?status=spam" 
        class="pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition-colors <?= $currentTab === 'spam' ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink hover:border-border' ?>"
      >
        <span>Spam</span>
        <span class="px-2 py-0.5 rounded-full text-xs <?= $currentTab === 'spam' ? 'bg-primary/10 text-primary' : 'bg-surface-alt text-ink-muted' ?>">
          <?= (int) ($counts['spam'] ?? 0) ?>
        </span>
      </a>
    </nav>
  </div>

  <!-- Bulk Action Bar (Visible when items selected) -->
  <div 
    x-show="selectedIds.length > 0" 
    x-cloak 
    class="p-4 bg-primary/5 border border-primary/20 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-soft-sm transition-all"
  >
    <div class="text-sm font-semibold text-primary">
      <span x-text="selectedIds.length"></span> comment(s) selected
    </div>

    <!-- Hidden Bulk Form -->
    <form x-ref="bulkForm" action="/admin/comments/bulk" method="POST" class="flex flex-wrap items-center gap-2">
      <?= \App\Core\Csrf::field() ?>
      <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
      <input type="hidden" name="action" :value="bulkAction">
      <template x-for="id in selectedIds" :key="id">
        <input type="hidden" name="ids[]" :value="id">
      </template>

      <!-- Bulk Action Buttons -->
      <?php if ($currentTab !== 'approved'): ?>
        <button 
          type="button" 
          @click="confirmBulk('approve')" 
          class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-soft transition-colors"
        >
          Approve selected
        </button>
      <?php endif; ?>

      <?php if ($currentTab !== 'spam'): ?>
        <button 
          type="button" 
          @click="confirmBulk('spam')" 
          class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold shadow-soft transition-colors"
        >
          Mark as spam
        </button>
      <?php endif; ?>

      <button 
        type="button" 
        @click="confirmBulk('delete')" 
        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-soft transition-colors"
      >
        Delete selected
      </button>

      <button 
        type="button" 
        @click="selectedIds = []; selectAll = false" 
        class="px-3 py-1.5 text-xs text-ink-muted hover:text-ink font-medium"
      >
        Cancel
      </button>
    </form>
  </div>

  <?php if (empty($comments)): ?>
    <!-- Empty State -->
    <div class="bg-white border border-border rounded-2xl p-12 text-center shadow-soft-sm">
      <div class="w-12 h-12 rounded-full bg-surface-alt flex items-center justify-center mx-auto mb-3 text-ink-muted">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.009Z"/></svg>
      </div>
      <h3 class="font-serif text-lg font-bold text-ink mb-1">No comments to review</h3>
      <p class="text-xs text-ink-muted">There are currently no <?= e($currentTab) ?> comments.</p>
    </div>
  <?php else: ?>

    <!-- Desktop Table View (Hidden on mobile) -->
    <div class="hidden md:block bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface/50 border-b border-border text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
              <th class="py-3 px-4 w-10 text-center">
                <input 
                  type="checkbox" 
                  @click="toggleAll()" 
                  :checked="selectAll" 
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                  aria-label="Select all comments"
                >
              </th>
              <th class="py-3 px-4 w-48">Author</th>
              <th class="py-3 px-4">Comment</th>
              <th class="py-3 px-4 w-52">Post</th>
              <th class="py-3 px-4 w-32">Date</th>
              <th class="py-3 px-4 text-right w-44">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border text-sm">
            <?php foreach ($comments as $comment): ?>
              <tr class="hover:bg-surface/30 transition-colors">
                <!-- Checkbox -->
                <td class="py-4 px-4 text-center align-top">
                  <input 
                    type="checkbox" 
                    value="<?= (int) $comment['id'] ?>" 
                    class="comment-checkbox rounded border-border text-primary focus:ring-primary h-4 w-4"
                    :checked="selectedIds.includes(<?= (int) $comment['id'] ?>)"
                    @click="toggleItem(<?= (int) $comment['id'] ?>)"
                    aria-label="Select comment from <?= e($comment['name']) ?>"
                  >
                </td>

                <!-- Author info -->
                <td class="py-4 px-4 align-top">
                  <div class="font-semibold text-ink"><?= e($comment['name']) ?></div>
                  <div class="text-xs text-ink-muted break-all"><?= e($comment['email']) ?></div>
                </td>

                <!-- Comment Body -->
                <td class="py-4 px-4 align-top text-ink-soft leading-relaxed">
                  <div class="text-sm max-h-36 overflow-y-auto whitespace-pre-wrap break-words">
                    <?= nl2br(e($comment['body'])) ?>
                  </div>
                </td>

                <!-- Post Link -->
                <td class="py-4 px-4 align-top">
                  <?php if (!empty($comment['post_slug'])): ?>
                    <a 
                      href="/post/<?= e($comment['post_slug']) ?>" 
                      target="_blank" 
                      class="text-xs font-semibold text-primary hover:underline line-clamp-2"
                    >
                      <?= e($comment['post_title'] ?: 'View Post') ?> &rarr;
                    </a>
                  <?php else: ?>
                    <span class="text-xs text-ink-subtle">Post deleted</span>
                  <?php endif; ?>
                </td>

                <!-- Date -->
                <td class="py-4 px-4 align-top text-xs text-ink-subtle whitespace-nowrap">
                  <?= formatDate($comment['created_at'], 'd M Y') ?><br>
                  <span class="text-[11px]"><?= date('h:i A', strtotime($comment['created_at'])) ?></span>
                </td>

                <!-- Row Actions -->
                <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <?php if ($comment['status'] !== 'approved'): ?>
                      <form action="/admin/comments/<?= (int) $comment['id'] ?>/approve" method="POST" class="inline">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
                        <button 
                          type="submit" 
                          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                          title="Approve comment"
                        >
                          Approve
                        </button>
                      </form>
                    <?php endif; ?>

                    <?php if ($comment['status'] !== 'spam'): ?>
                      <form action="/admin/comments/<?= (int) $comment['id'] ?>/spam" method="POST" class="inline">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
                        <button 
                          type="submit" 
                          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors"
                          title="Mark as spam"
                        >
                          Spam
                        </button>
                      </form>
                    <?php endif; ?>

                    <form 
                      action="/admin/comments/<?= (int) $comment['id'] ?>/delete" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Permanently delete this comment?');"
                    >
                      <?= \App\Core\Csrf::field() ?>
                      <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
                      <button 
                        type="submit" 
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors"
                        title="Delete comment"
                      >
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile Stacked Card View (Visible only on mobile < md) -->
    <div class="md:hidden space-y-4">
      <?php foreach ($comments as $comment): ?>
        <div class="bg-white border border-border rounded-2xl p-4 shadow-soft-sm space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
              <input 
                type="checkbox" 
                value="<?= (int) $comment['id'] ?>" 
                class="comment-checkbox rounded border-border text-primary focus:ring-primary h-4 w-4"
                :checked="selectedIds.includes(<?= (int) $comment['id'] ?>)"
                @click="toggleItem(<?= (int) $comment['id'] ?>)"
                aria-label="Select comment"
              >
              <div>
                <div class="font-semibold text-sm text-ink"><?= e($comment['name']) ?></div>
                <div class="text-xs text-ink-muted"><?= e($comment['email']) ?></div>
              </div>
            </div>
            <span class="text-[11px] text-ink-subtle whitespace-nowrap">
              <?= formatDate($comment['created_at'], 'd M Y') ?>
            </span>
          </div>

          <!-- Post reference -->
          <?php if (!empty($comment['post_slug'])): ?>
            <div class="text-xs text-ink-muted">
              Post: 
              <a href="/post/<?= e($comment['post_slug']) ?>" target="_blank" class="font-medium text-primary hover:underline">
                <?= e($comment['post_title'] ?: 'View Post') ?>
              </a>
            </div>
          <?php endif; ?>

          <!-- Comment body -->
          <div class="text-sm text-ink-soft bg-surface/50 p-3 rounded-xl break-words leading-relaxed">
            <?= nl2br(e($comment['body'])) ?>
          </div>

          <!-- Mobile Actions -->
          <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-border">
            <?php if ($comment['status'] !== 'approved'): ?>
              <form action="/admin/comments/<?= (int) $comment['id'] ?>/approve" method="POST" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                  Approve
                </button>
              </form>
            <?php endif; ?>

            <?php if ($comment['status'] !== 'spam'): ?>
              <form action="/admin/comments/<?= (int) $comment['id'] ?>/spam" method="POST" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                  Spam
                </button>
              </form>
            <?php endif; ?>

            <form 
              action="/admin/comments/<?= (int) $comment['id'] ?>/delete" 
              method="POST" 
              class="inline ml-auto"
              onsubmit="return confirm('Permanently delete this comment?');"
            >
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="return_status" value="<?= e($currentTab) ?>">
              <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                Delete
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total'] > 1): ?>
      <div class="flex items-center justify-between border-t border-border pt-4">
        <div class="text-xs text-ink-muted">
          Page <?= (int) $pagination['current'] ?> of <?= (int) $pagination['total'] ?> (<?= (int) $pagination['count'] ?> comments)
        </div>
        <div class="flex items-center gap-2">
          <?php if ($pagination['current'] > 1): ?>
            <a 
              href="/admin/comments?status=<?= e($currentTab) ?>&page=<?= $pagination['current'] - 1 ?>" 
              class="px-3 py-1.5 rounded-lg border border-border text-xs font-medium text-ink hover:bg-surface transition-colors"
            >
              &larr; Previous
            </a>
          <?php endif; ?>

          <?php if ($pagination['current'] < $pagination['total']): ?>
            <a 
              href="/admin/comments?status=<?= e($currentTab) ?>&page=<?= $pagination['current'] + 1 ?>" 
              class="px-3 py-1.5 rounded-lg border border-border text-xs font-medium text-ink hover:bg-surface transition-colors"
            >
              Next &rarr;
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<!-- NatConnect Foundation — Admin Dashboard -->
<div class="space-y-8">
  
  <!-- Dashboard Top Action Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-soft-sm">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Foundation Overview</h1>
      <p class="text-sm text-ink-muted mt-1">Live editorial metrics, reader reach, and post lifecycle status.</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <a 
        href="/admin/posts/new" 
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        <span>New Article</span>
      </a>
    </div>
  </div>

  <!-- 6 Stat Cards Grid: 2 cols on mobile, 3 on md, 6 on xl -->
  <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 sm:gap-6">
    
    <!-- 1. Total Posts -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm hover:border-primary/30 transition-colors">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Total Posts</span>
        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-ink">
        <?= formatNumberIN($stats['total_posts'] ?? 0) ?>
      </div>
      <p class="text-[11px] text-ink-subtle mt-1.5">Across all statuses</p>
    </div>

    <!-- 2. Published Posts -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm hover:border-accent-strong/40 transition-colors">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Published</span>
        <svg class="w-4 h-4 text-accent-strong" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-accent-strong">
        <?= formatNumberIN($stats['published_posts'] ?? 0) ?>
      </div>
      <p class="text-[11px] text-ink-subtle mt-1.5">Live on public website</p>
    </div>

    <!-- 3. Draft Posts -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm hover:border-slate-400 transition-colors">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Drafts</span>
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-slate-700">
        <?= formatNumberIN($stats['draft_posts'] ?? 0) ?>
      </div>
      <p class="text-[11px] text-ink-subtle mt-1.5">Unpublished work</p>
    </div>

    <!-- 4. Scheduled Posts -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm hover:border-blue-400 transition-colors">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Scheduled</span>
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-blue-600">
        <?= formatNumberIN($stats['scheduled_posts'] ?? 0) ?>
      </div>
      <p class="text-[11px] text-ink-subtle mt-1.5">Pending release time</p>
    </div>

    <!-- 5. Pending Comments -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm hover:border-amber-400 transition-colors">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Comments</span>
        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-amber-600">
        <?= formatNumberIN($stats['pending_comments'] ?? 0) ?>
      </div>
      <p class="text-[11px] text-ink-subtle mt-1.5">Needs moderation</p>
    </div>

    <!-- 6. Total Views -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm hover:border-primary/30 transition-colors">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Total Views</span>
        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-primary">
        <?= formatNumberIN($stats['total_views'] ?? 0) ?>
      </div>
      <p class="text-[11px] text-ink-subtle mt-1.5">Cumulative reads</p>
    </div>

  </div>

  <!-- Content Activity Tables (Top 5 by Views & 5 Recently Updated) -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- 1. Top 5 Posts by Views -->
    <div class="bg-white rounded-2xl border border-border shadow-soft-sm overflow-hidden flex flex-col justify-between">
      <div>
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <div>
            <h2 class="font-serif font-bold text-lg text-ink">Top Articles by Views</h2>
            <p class="text-xs text-ink-muted mt-0.5">Highest readership engagement</p>
          </div>
          <a href="/admin/posts" class="text-xs font-semibold text-primary hover:text-primary-hover transition-colors">
            All posts &rarr;
          </a>
        </div>

        <?php if (!empty($topPosts)): ?>
          <div class="divide-y divide-border-subtle">
            <?php foreach ($topPosts as $post): ?>
              <?php
                $statusBadge = match($post['status'] ?? 'draft') {
                    'published' => 'bg-accent/10 text-accent-strong border-accent/20',
                    'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
                    default     => 'bg-slate-100 text-slate-700 border-slate-200',
                };
              ?>
              <div class="p-4 sm:px-6 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex px-2 py-0.5 text-[10px] uppercase font-semibold tracking-wider rounded-md border <?= $statusBadge ?>">
                      <?= e($post['status'] ?? 'draft') ?>
                    </span>
                    <span class="text-xs text-ink-muted">
                      <?= formatDate($post['published_at'] ?? $post['created_at']) ?>
                    </span>
                  </div>
                  <h3 class="font-serif font-bold text-sm text-ink truncate">
                    <?= e($post['title']) ?>
                  </h3>
                </div>
                <div class="flex items-center gap-4 shrink-0">
                  <span class="text-xs font-semibold text-ink-muted flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?= formatNumberIN($post['views'] ?? 0) ?>
                  </span>
                  <a 
                    href="/admin/posts/<?= (int) $post['id'] ?>/edit" 
                    class="text-xs font-semibold text-primary hover:text-primary-hover p-2 -m-2 rounded-lg"
                    aria-label="Edit <?= e($post['title']) ?>"
                  >
                    Edit
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <!-- Proper Empty State (Zero sample/fake data) -->
          <div class="p-8 sm:p-12 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-surface border border-border mx-auto flex items-center justify-center text-ink-subtle">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            </div>
            <h3 class="font-serif font-bold text-base text-ink">No Articles Yet</h3>
            <p class="text-xs text-ink-muted max-w-sm mx-auto leading-relaxed">
              When articles are created and read by visitors, your top 5 performing stories will appear here.
            </p>
            <div class="pt-2">
              <a 
                href="/admin/posts/new" 
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-accent-strong hover:bg-accent-hover text-white text-xs font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>Compose First Article</span>
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2. 5 Recently Updated Posts -->
    <div class="bg-white rounded-2xl border border-border shadow-soft-sm overflow-hidden flex flex-col justify-between">
      <div>
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <div>
            <h2 class="font-serif font-bold text-lg text-ink">Recently Updated</h2>
            <p class="text-xs text-ink-muted mt-0.5">Latest editorial changes and revisions</p>
          </div>
          <a href="/admin/posts" class="text-xs font-semibold text-primary hover:text-primary-hover transition-colors">
            Manage all &rarr;
          </a>
        </div>

        <?php if (!empty($recentPosts)): ?>
          <div class="divide-y divide-border-subtle">
            <?php foreach ($recentPosts as $post): ?>
              <?php
                $statusBadge = match($post['status'] ?? 'draft') {
                    'published' => 'bg-accent/10 text-accent-strong border-accent/20',
                    'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
                    default     => 'bg-slate-100 text-slate-700 border-slate-200',
                };
              ?>
              <div class="p-4 sm:px-6 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex px-2 py-0.5 text-[10px] uppercase font-semibold tracking-wider rounded-md border <?= $statusBadge ?>">
                      <?= e($post['status'] ?? 'draft') ?>
                    </span>
                    <span class="text-xs text-ink-muted">
                      Updated <?= formatDate($post['updated_at'] ?? $post['created_at']) ?>
                    </span>
                  </div>
                  <h3 class="font-serif font-bold text-sm text-ink truncate">
                    <?= e($post['title']) ?>
                  </h3>
                </div>
                <div class="flex items-center gap-4 shrink-0">
                  <span class="text-xs font-semibold text-ink-muted flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?= formatNumberIN($post['views'] ?? 0) ?>
                  </span>
                  <a 
                    href="/admin/posts/<?= (int) $post['id'] ?>/edit" 
                    class="text-xs font-semibold text-primary hover:text-primary-hover p-2 -m-2 rounded-lg"
                    aria-label="Edit <?= e($post['title']) ?>"
                  >
                    Edit
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <!-- Proper Empty State (Zero sample/fake data) -->
          <div class="p-8 sm:p-12 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-surface border border-border mx-auto flex items-center justify-center text-ink-subtle">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <h3 class="font-serif font-bold text-base text-ink">No Activity Recorded</h3>
            <p class="text-xs text-ink-muted max-w-sm mx-auto leading-relaxed">
              Recent edits and publication actions will automatically display here.
            </p>
            <div class="pt-2">
              <a 
                href="/admin/posts/new" 
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-accent-strong hover:bg-accent-hover text-white text-xs font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>Create New Article</span>
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

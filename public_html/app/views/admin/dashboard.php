<!-- NatConnect Foundation — Admin Dashboard -->
<div class="space-y-6 sm:space-y-8">
  
  <!-- Dashboard Header with Single Primary Action -->
  <div class="flex items-center justify-between gap-4">
    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Dashboard</h1>
    <a 
      href="/admin/posts/new" 
      class="inline-flex items-center gap-2 px-4 sm:px-5 py-2.5 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      <span>New post</span>
    </a>
  </div>

  <?php if (!empty($launchChecklist) && !$launchChecklist['allPassed']): ?>
    <!-- Launch Checklist Card (Visible only while at least one check fails) -->
    <div class="bg-white rounded-2xl border border-amber-200/90 shadow-soft-sm overflow-hidden p-5 sm:p-6">
      <div class="flex items-center justify-between gap-4 mb-4 pb-3 border-b border-border/70">
        <div class="flex items-center gap-2.5">
          <span class="inline-flex w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
          <h2 class="font-serif text-lg font-bold text-ink">Launch checklist</h2>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
          <?= (int) $launchChecklist['failCount'] ?> remaining
        </span>
      </div>

      <ul class="divide-y divide-border/40 text-sm">
        <?php foreach ($launchChecklist['checks'] as $check): ?>
          <li class="py-2.5 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
            <div class="flex items-start gap-2.5 min-w-0">
              <?php if ($check['passed']): ?>
                <span class="text-emerald-600 font-bold shrink-0 mt-0.5" aria-label="Passed">✓</span>
                <span class="text-ink-soft text-sm"><?= htmlspecialchars($check['message']) ?></span>
              <?php else: ?>
                <span class="text-amber-600 font-bold shrink-0 mt-0.5" aria-label="Failed">✗</span>
                <div class="min-w-0">
                  <span class="text-ink font-medium text-sm block"><?= htmlspecialchars($check['message']) ?></span>
                  <?php if (!empty($check['details'])): ?>
                    <span class="text-xs text-ink-muted block mt-0.5"><?= htmlspecialchars($check['details'][0]) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <?php if (!$check['passed'] && !empty($check['fix_url'])): ?>
              <a 
                href="<?= htmlspecialchars($check['fix_url']) ?>" 
                class="shrink-0 text-xs font-semibold text-primary hover:text-primary-hover hover:underline inline-flex items-center gap-1 min-h-[28px] focus:outline-none focus-visible:ring-1 focus-visible:ring-primary rounded"
              >
                <span><?= htmlspecialchars($check['fix_label'] ?? 'Edit') ?></span>
                <span aria-hidden="true">&rarr;</span>
              </a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php
    $pendingComments = (int) ($stats['pending_comments'] ?? 0);
  ?>

  <!-- 6 Stat Cards Grid: 2 cols on mobile, 3 on md, 6 only at 2xl (1536px+) -->
  <div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-6 gap-4 sm:gap-6">
    
    <!-- 1. Total Posts -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Total posts</span>
        <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-ink">
        <?= formatNumberIN($stats['total_posts'] ?? 0) ?>
      </div>
    </div>

    <!-- 2. Published -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Published</span>
        <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-ink">
        <?= formatNumberIN($stats['published_posts'] ?? 0) ?>
      </div>
    </div>

    <!-- 3. Drafts -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Drafts</span>
        <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-ink">
        <?= formatNumberIN($stats['draft_posts'] ?? 0) ?>
      </div>
    </div>

    <!-- 4. Scheduled -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Scheduled</span>
        <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-ink">
        <?= formatNumberIN($stats['scheduled_posts'] ?? 0) ?>
      </div>
    </div>

    <!-- 5. To review (accent-strong border and badge when > 0, links to /admin/comments?status=pending) -->
    <a href="/admin/comments?status=pending" class="block bg-white p-5 rounded-2xl <?= $pendingComments > 0 ? 'border-2 border-accent-strong ring-1 ring-accent-strong/20' : 'border border-border' ?> shadow-soft-sm transition-all hover:border-primary/50 group">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span class="truncate group-hover:text-primary transition-colors">To review</span>
        <svg class="w-4 h-4 text-ink-subtle group-hover:text-primary shrink-0 transition-colors" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div class="flex items-center justify-between">
        <div class="text-2xl sm:text-3xl font-serif font-bold text-ink group-hover:text-primary transition-colors">
          <?= formatNumberIN($pendingComments) ?>
        </div>
        <?php if ($pendingComments > 0): ?>
          <span class="inline-flex px-2 py-0.5 text-[10px] uppercase font-semibold tracking-wider rounded-md bg-accent/10 text-accent-strong border border-accent/20">
            Action
          </span>
        <?php endif; ?>
      </div>
    </a>

    <!-- 6. Total Views -->
    <div class="bg-white p-5 rounded-2xl border border-border shadow-soft-sm">
      <div class="flex items-center justify-between text-ink-muted text-xs font-semibold uppercase tracking-wider mb-2">
        <span>Total views</span>
        <svg class="w-4 h-4 text-ink-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
      </div>
      <div class="text-2xl sm:text-3xl font-serif font-bold text-ink">
        <?= formatNumberIN($stats['total_views'] ?? 0) ?>
      </div>
    </div>

  </div>

  <!-- Content Activity Tables (Most viewed posts & Recently edited) -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
    
    <!-- 1. Most viewed posts -->
    <div class="bg-white rounded-2xl border border-border shadow-soft-sm overflow-hidden flex flex-col justify-between">
      <div>
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <h2 class="font-serif font-bold text-lg text-ink">Most viewed posts</h2>
          <a href="/admin/posts" class="text-xs font-semibold text-primary hover:text-primary-hover transition-colors">
            All posts &rarr;
          </a>
        </div>

        <?php if (!empty($topPosts)): ?>
          <div class="divide-y divide-border-subtle">
            <?php foreach ($topPosts as $post): ?>
              <?php
                $effStatus = $post['effective_status'] ?? effectiveStatus($post);
                $statusBadge = match($effStatus) {
                    'published' => 'bg-accent/10 text-accent-strong border-accent/20',
                    'scheduled' => 'bg-primary/10 text-primary border-primary/20',
                    default     => 'bg-surface text-ink-muted border-border',
                };
              ?>
              <div class="p-4 sm:px-6 flex items-center justify-between gap-4 hover:bg-surface transition-colors">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex px-2 py-0.5 text-[10px] uppercase font-semibold tracking-wider rounded-md border <?= $statusBadge ?>">
                      <?= e($effStatus) ?>
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
                  <span class="text-xs font-medium text-ink-muted flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
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
          <!-- Clean Plain-Language Empty State -->
          <p class="p-8 sm:p-12 text-center text-sm text-ink-muted">
            No posts yet.
          </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2. Recently edited -->
    <div class="bg-white rounded-2xl border border-border shadow-soft-sm overflow-hidden flex flex-col justify-between">
      <div>
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
          <h2 class="font-serif font-bold text-lg text-ink">Recently edited</h2>
          <a href="/admin/posts" class="text-xs font-semibold text-primary hover:text-primary-hover transition-colors">
            All posts &rarr;
          </a>
        </div>

        <?php if (!empty($recentPosts)): ?>
          <div class="divide-y divide-border-subtle">
            <?php foreach ($recentPosts as $post): ?>
              <?php
                $effStatus = $post['effective_status'] ?? effectiveStatus($post);
                $statusBadge = match($effStatus) {
                    'published' => 'bg-accent/10 text-accent-strong border-accent/20',
                    'scheduled' => 'bg-primary/10 text-primary border-primary/20',
                    default     => 'bg-surface text-ink-muted border-border',
                };
              ?>
              <div class="p-4 sm:px-6 flex items-center justify-between gap-4 hover:bg-surface transition-colors">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex px-2 py-0.5 text-[10px] uppercase font-semibold tracking-wider rounded-md border <?= $statusBadge ?>">
                      <?= e($effStatus) ?>
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
                  <span class="text-xs font-medium text-ink-muted flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
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
          <!-- Clean Plain-Language Empty State -->
          <p class="p-8 sm:p-12 text-center text-sm text-ink-muted">
            No posts yet.
          </p>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

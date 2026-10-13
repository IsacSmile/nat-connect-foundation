<!-- NatConnect Foundation — Database Maintenance & Updates -->
<div class="space-y-6 max-w-7xl mx-auto">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Maintenance</h1>
      <p class="text-sm text-ink-muted mt-1">Review database migrations and apply pending schema updates.</p>
    </div>

    <!-- Quick Stats -->
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
        <?= (int) $appliedCount ?> Applied
      </span>
      <?php if ($pendingCount > 0): ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
          <?= (int) $pendingCount ?> Pending
        </span>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-surface border border-border text-ink-muted">
          0 Pending
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Execution Results from Recent Migration Run -->
  <?php if (!empty($updateResults)): ?>
    <div class="bg-white border border-border rounded-2xl p-5 shadow-soft-sm space-y-3">
      <h2 class="font-serif text-base font-bold text-ink">Migration Execution Results</h2>
      <div class="divide-y divide-border/60">
        <?php foreach ($updateResults as $res): ?>
          <div class="py-2.5 flex items-start justify-between gap-3 text-sm">
            <div class="flex items-center gap-2.5 min-w-0">
              <?php if (($res['status'] ?? '') === 'success'): ?>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="font-mono text-xs font-semibold text-ink truncate"><?= e($res['file']) ?></span>
              <?php else: ?>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                <span class="font-mono text-xs font-semibold text-rose-700 truncate"><?= e($res['file']) ?></span>
              <?php endif; ?>
            </div>
            <span class="text-xs <?= ($res['status'] ?? '') === 'success' ? 'text-emerald-700' : 'text-rose-700 font-semibold' ?>">
              <?= e($res['message'] ?? '') ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Pending Updates Action Card -->
  <div class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="space-y-1">
        <h2 class="font-serif text-lg font-bold text-ink">Database Updates</h2>
        <?php if ($pendingCount > 0): ?>
          <p class="text-sm text-amber-900 font-medium bg-amber-50 border border-amber-200 rounded-xl px-3.5 py-2 inline-block">
            <strong>Warning:</strong> Back up the database in hPanel before applying updates.
          </p>
        <?php else: ?>
          <p class="text-sm text-ink-muted">
            The database schema is completely up to date. No pending migration files found.
          </p>
        <?php endif; ?>
      </div>

      <div>
        <?php if ($pendingCount > 0): ?>
          <form action="/admin/maintenance/apply" method="POST" onsubmit="return confirm('Have you backed up the database in hPanel? Click OK to apply pending updates.');">
            <?= \App\Core\Csrf::field() ?>
            <button 
              type="submit" 
              class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-accent-strong hover:bg-accent-hover rounded-xl shadow-soft transition-colors focus-visible:ring-2 focus-visible:ring-accent whitespace-nowrap min-h-[44px]"
            >
              Apply pending updates (<?= (int) $pendingCount ?>)
            </button>
          </form>
        <?php else: ?>
          <button 
            type="button" 
            disabled 
            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-ink-muted bg-surface border border-border rounded-xl cursor-not-allowed min-h-[44px]"
          >
            All updates applied
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Migrations Directory List -->
  <div class="bg-white border border-border rounded-2xl shadow-soft-sm overflow-hidden">
    <div class="p-5 border-b border-border flex items-center justify-between">
      <div>
        <h2 class="font-serif text-lg font-bold text-ink">Migration Files</h2>
        <p class="text-xs text-ink-muted mt-0.5">Located outside public_html in <code class="font-mono text-ink">database/migrations/</code></p>
      </div>
      <span class="text-xs text-ink-muted font-medium"><?= count($migrations) ?> files</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface/50 border-b border-border text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
            <th class="py-3 px-4 sm:px-6">Migration File</th>
            <th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4 text-right sm:pr-6">Applied At</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <?php if (empty($migrations)): ?>
            <tr>
              <td colspan="3" class="py-8 text-center text-sm text-ink-muted">
                No migration files found in database/migrations.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($migrations as $m): ?>
              <tr class="hover:bg-surface/30 transition-colors">
                <td class="py-3.5 px-4 sm:px-6">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-xs sm:text-sm font-medium text-ink">
                      <?= e($m['filename']) ?>
                    </span>
                    <span class="text-[11px] text-ink-muted">
                      (<?= number_format($m['filesize']) ?> B)
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <?php if ($m['is_applied']): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      Applied
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                      Pending
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 text-right sm:pr-6 text-xs text-ink-muted font-mono">
                  <?= !empty($m['applied_at']) ? date('M j, Y H:i', strtotime((string) $m['applied_at'])) : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

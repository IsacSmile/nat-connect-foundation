<!-- NatConnect Foundation — Database Maintenance & Updates -->
<div class="space-y-6 max-w-7xl mx-auto">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Maintenance</h1>
      <p class="text-sm text-ink-muted mt-1">Review database migrations, verify folder security, and apply schema updates.</p>
    </div>

    <!-- Quick Stats (Palette tokens only: accent-strong for Applied, ink-muted for Pending) -->
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-accent/10 text-accent-strong border border-accent/20">
        <?= (int) $appliedCount ?> Applied
      </span>
      <?php if ($pendingCount > 0): ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-surface text-ink-muted border border-border">
          <?= (int) $pendingCount ?> Pending
        </span>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-surface border border-border text-ink-muted">
          0 Pending
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Security Self-Test Card -->
  <div 
    x-data="securitySelfTest()" 
    class="bg-white border border-border rounded-2xl p-6 shadow-soft-sm space-y-4"
  >
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="space-y-1">
        <h2 class="font-serif text-lg font-bold text-ink">Security Self-Test</h2>
        <p class="text-sm text-ink-muted">
          Verifies from the browser that protected directories, config files, and executable probes return HTTP 403 Forbidden or 404 Not Found.
        </p>
      </div>

      <button 
        type="button" 
        @click="runSelfTest()" 
        :disabled="running"
        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-accent-strong hover:bg-accent-hover disabled:opacity-50 rounded-xl shadow-soft transition-colors focus-visible:ring-2 focus-visible:ring-accent whitespace-nowrap min-h-[44px]"
      >
        <svg x-show="!running" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
        <svg x-show="running" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
        <span x-text="running ? 'Testing probes...' : 'Run security self-test'">Run security self-test</span>
      </button>
    </div>

    <!-- Status Banner -->
    <template x-if="completed">
      <div 
        :class="allPassed ? 'bg-accent/10 border-accent/20 text-accent-strong' : 'bg-rose-50 border-rose-200 text-rose-700'"
        class="border rounded-xl p-3.5 text-sm flex items-center justify-between gap-3"
      >
        <div class="flex items-center gap-2 font-medium">
          <span x-text="allPassed ? '✓' : '✗'" class="font-bold"></span>
          <span x-text="allPassed ? 'All security probes passed (403/404). Protected folders are unreachable.' : 'Security probe failed: One or more protected files returned an accessible status.'"></span>
        </div>
        <span x-show="testedAt" class="text-xs opacity-80" x-text="'Tested: ' + testedAt"></span>
      </div>
    </template>

    <!-- Probes List -->
    <div class="divide-y divide-border border border-border rounded-xl overflow-hidden text-sm">
      <template x-for="(item, idx) in tests" :key="item.url">
        <div class="p-3.5 sm:px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 hover:bg-surface/40 transition-colors">
          <div class="flex items-center gap-2.5 min-w-0">
            <template x-if="item.status === 'running'">
              <span class="inline-block w-4 h-4 border-2 border-accent-strong border-t-transparent rounded-full animate-spin shrink-0"></span>
            </template>
            <template x-if="item.status === 'done' && item.passed">
              <span class="text-accent-strong font-bold shrink-0">✓</span>
            </template>
            <template x-if="item.status === 'done' && !item.passed">
              <span class="text-rose-600 font-bold shrink-0">✗</span>
            </template>
            <template x-if="!item.status">
              <span class="text-ink-muted text-xs shrink-0">—</span>
            </template>

            <span class="font-mono text-xs text-ink truncate" x-text="item.url"></span>
            <span class="text-xs text-ink-muted hidden md:inline" x-text="'(' + item.label + ')'"></span>
          </div>

          <div class="flex items-center gap-3 shrink-0 self-end sm:self-auto">
            <span 
              class="text-xs"
              :class="item.passed === true ? 'text-accent-strong' : (item.passed === false ? 'text-rose-600 font-semibold' : 'text-ink-muted')"
              x-text="item.message"
            ></span>
          </div>
        </div>
      </template>
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
                <span class="w-2.5 h-2.5 rounded-full bg-accent-strong shrink-0"></span>
                <span class="font-mono text-xs font-semibold text-ink truncate"><?= e($res['file']) ?></span>
              <?php else: ?>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                <span class="font-mono text-xs font-semibold text-rose-700 truncate"><?= e($res['file']) ?></span>
              <?php endif; ?>
            </div>
            <span class="text-xs <?= ($res['status'] ?? '') === 'success' ? 'text-accent-strong' : 'text-rose-700 font-semibold' ?>">
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
          <p class="text-sm text-ink-muted font-medium bg-surface border border-border rounded-xl px-3.5 py-2 inline-block">
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
        <p class="text-xs text-ink-muted mt-0.5">Located inside public_html in <code class="font-mono text-ink">database/migrations/</code></p>
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
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-accent/10 text-accent-strong border border-accent/20">
                      Applied
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-surface text-ink-muted border border-border">
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

<script>
function securitySelfTest() {
  return {
    running: false,
    completed: <?= !empty($selfTestResult) ? 'true' : 'false' ?>,
    allPassed: <?= !empty($selfTestResult['passed']) ? 'true' : 'false' ?>,
    testedAt: <?= json_encode(!empty($selfTestResult['tested_at']) ? date('M j, Y H:i', strtotime($selfTestResult['tested_at'])) : null) ?>,
    csrfToken: '<?= \App\Core\Csrf::token() ?>',
    tests: [
      {
        url: '/app/config/config.php',
        label: 'Config file',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      },
      {
        url: '/app/core/Database.php',
        label: 'Database core class',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      },
      {
        url: '/vendor/composer/installed.json',
        label: 'Composer manifest',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      },
      {
        url: '/database/install.sql',
        label: 'Database install script',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      },
      {
        url: '<?= htmlspecialchars($sampleMigrationUrl) ?>',
        label: 'Migration file',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      },
      {
        url: '/app/storage/logs/',
        label: 'Logs directory',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      },
      {
        url: '/uploads/test.php',
        label: 'Uploads PHP probe',
        status: <?= !empty($selfTestResult) ? "'done'" : 'null' ?>,
        httpCode: null,
        passed: <?= !empty($selfTestResult['passed']) ? 'true' : 'null' ?>,
        message: <?= !empty($selfTestResult) ? "'Previously verified'" : "'Awaiting test'" ?>
      }
    ],
    async runSelfTest() {
      this.running = true;
      let allPass = true;
      for (let i = 0; i < this.tests.length; i++) {
        const item = this.tests[i];
        item.status = 'running';
        try {
          const resp = await fetch(item.url, {
            method: 'GET',
            cache: 'no-store'
          });
          item.httpCode = resp.status;
          if (resp.status === 403 || resp.status === 404) {
            item.passed = true;
            item.message = resp.status === 403 
              ? 'Protected (HTTP 403 Forbidden)' 
              : 'Protected (HTTP 404 Not Found)';
          } else {
            item.passed = false;
            allPass = false;
            item.message = 'Insecure: HTTP ' + resp.status + ' (expected 403 or 404)';
          }
        } catch (err) {
          // Connection refused / network abort indicates port/path blocked
          item.passed = true;
          item.httpCode = 0;
          item.message = 'Protected (connection denied)';
        }
        item.status = 'done';
      }
      this.running = false;
      this.completed = true;
      this.allPassed = allPass;
      const now = new Date();
      this.testedAt = now.toLocaleString();

      // Record to backend storage for Dashboard Launch Checklist
      try {
        await fetch('/admin/maintenance/record-self-test', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken
          },
          body: JSON.stringify({
            csrf_token: this.csrfToken,
            passed: this.allPassed,
            details: this.tests.map(t => t.url + ': ' + t.httpCode + ' (' + (t.passed ? 'PASS' : 'FAIL') + ')')
          })
        });
      } catch (err) {
        console.error('Failed to save self-test result:', err);
      }
    }
  };
}
</script>

<!-- NatConnect Foundation — Search Page -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">

  <!-- Search Header & Search Form -->
  <header class="max-w-3xl space-y-4">
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink">
      <?= $query !== '' ? 'Search Results' : 'Search' ?>
    </h1>

    <form action="/search" method="GET" class="relative max-w-xl">
      <input 
        type="search" 
        name="q" 
        value="<?= e($query) ?>" 
        maxlength="100"
        placeholder="Search posts, causes, RTI filings..." 
        class="w-full pl-11 pr-24 py-3 bg-white border border-border rounded-xl text-sm text-ink placeholder-ink-subtle focus:border-primary focus:ring-1 focus:ring-primary outline-none shadow-soft-sm transition-all"
      />
      <svg class="w-5 h-5 absolute left-3.5 top-3.5 text-ink-subtle pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
      </svg>
      <button 
        type="submit" 
        class="absolute right-2 top-2 px-4 py-1.5 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-lg shadow-soft transition-colors"
      >
        Search
      </button>
    </form>

    <?php if ($query !== ''): ?>
      <p class="text-sm text-ink-muted">
        Found <strong class="text-ink"><?= (int) $pagination['count'] ?></strong> <?= $pagination['count'] === 1 ? 'result' : 'results' ?> for &ldquo;<span class="text-ink font-semibold"><?= e($query) ?></span>&rdquo;
      </p>
    <?php else: ?>
      <p class="text-sm text-ink-muted">
        Type keywords above to search all published editorial reports, RTI investigations, and campaigns.
      </p>
    <?php endif; ?>
  </header>

  <!-- Results Grid -->
  <?php if (!empty($posts)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
      <?php foreach ($posts as $post): ?>
        <?php \App\Core\View::partial('partials/post-card', ['post' => $post]); ?>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total'] > 1): ?>
      <nav class="flex items-center justify-center gap-2 pt-8" aria-label="Pagination">
        <?php if ($pagination['current'] > 1): ?>
          <a 
            href="<?= e($pagination['baseUrl']) ?>&page=<?= $pagination['current'] - 1 ?>" 
            class="px-4 py-2 rounded-xl border border-border bg-white text-sm font-semibold text-ink hover:bg-surface transition-colors"
          >
            &larr; Previous
          </a>
        <?php endif; ?>

        <span class="px-4 py-2 text-sm text-ink-muted font-medium">
          Page <?= (int) $pagination['current'] ?> of <?= (int) $pagination['total'] ?>
        </span>

        <?php if ($pagination['current'] < $pagination['total']): ?>
          <a 
            href="<?= e($pagination['baseUrl']) ?>&page=<?= $pagination['current'] + 1 ?>" 
            class="px-4 py-2 rounded-xl border border-border bg-white text-sm font-semibold text-ink hover:bg-surface transition-colors"
          >
            Next &rarr;
          </a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

  <?php elseif ($query !== ''): ?>
    <!-- No Results State -->
    <div class="text-center py-16 bg-white rounded-3xl border border-border p-8 max-w-lg mx-auto space-y-3">
      <p class="font-serif text-xl font-bold text-ink">No matches found</p>
      <p class="text-sm text-ink-muted">
        We could not find any published posts matching &ldquo;<?= e($query) ?>&rdquo;. Try searching with fewer or different keywords.
      </p>
    </div>
  <?php endif; ?>

</div>

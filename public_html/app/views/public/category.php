<!-- NatConnect Foundation — Category Archive -->
<div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">

  <!-- Category Header -->
  <header class="border-b border-border pb-8 max-w-3xl space-y-3">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/10 text-accent-strong text-xs font-semibold uppercase tracking-wider">
      Category
    </div>
    
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink">
      <?= e($category['name']) ?>
    </h1>

    <?php if (!empty($category['description'])): ?>
      <p class="text-ink/80 text-base sm:text-lg leading-relaxed">
        <?= e($category['description']) ?>
      </p>
    <?php endif; ?>

    <p class="text-xs text-ink-muted">
      Showing <?= (int) $pagination['count'] ?> published <?= $pagination['count'] === 1 ? 'post' : 'posts' ?>
    </p>
  </header>

  <!-- Posts Grid -->
  <?php if (!empty($posts)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
      <?php foreach ($posts as $post): ?>
        <?php \App\Core\View::partial('partials/post-card', ['post' => $post]); ?>
      <?php endforeach; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($pagination['total'] > 1): ?>
      <nav class="flex items-center justify-center gap-2 pt-8" aria-label="Pagination">
        <?php if ($pagination['current'] > 1): ?>
          <a 
            href="<?= e($pagination['baseUrl']) ?>?page=<?= $pagination['current'] - 1 ?>" 
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
            href="<?= e($pagination['baseUrl']) ?>?page=<?= $pagination['current'] + 1 ?>" 
            class="px-4 py-2 rounded-xl border border-border bg-white text-sm font-semibold text-ink hover:bg-surface transition-colors"
          >
            Next &rarr;
          </a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

  <?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-16 bg-white rounded-3xl border border-border p-8 max-w-lg mx-auto space-y-3">
      <p class="font-serif text-xl font-bold text-ink">No posts found</p>
      <p class="text-sm text-ink-muted">There are currently no published reports in this category.</p>
      <div class="pt-2">
        <a href="/" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-primary hover:underline">
          &larr; Return to Homepage
        </a>
      </div>
    </div>
  <?php endif; ?>

</div>

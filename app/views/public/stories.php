<!-- NatConnect Foundation — All Stories Archive (Design Phase D1) -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">

  <!-- Header -->
  <header class="border-b border-border pb-8 max-w-4xl space-y-4">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/10 text-accent-strong text-xs font-semibold uppercase tracking-wider">
      Archive
    </div>
    
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink">
      <?= $selectedCategory ? e($selectedCategory['name']) : 'All Stories' ?>
    </h1>

    <p class="text-ink/80 text-base sm:text-lg leading-relaxed">
      <?= $selectedCategory && !empty($selectedCategory['description']) 
            ? e($selectedCategory['description']) 
            : 'Explore our complete archive of investigative reports, RTI findings, wetland updates, and grassroots environmental actions.' ?>
    </p>

    <!-- Category Filter Chips -->
    <?php
      $chipBase = 'inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors';
      $chipActive = 'bg-primary text-white shadow-soft-sm';
      $chipInactive = 'bg-surface border border-border text-ink hover:border-primary/40';
    ?>
    <div class="pt-2 flex flex-wrap items-center gap-2" aria-label="Category filter">
      <a 
        href="/stories" 
        class="<?= $chipBase . ' ' . (empty($selectedCategory) ? $chipActive : $chipInactive) ?>"
      >
        All
      </a>
      <?php foreach ($categories as $cat): ?>
        <?php $isActive = !empty($selectedCategory) && (int) $selectedCategory['id'] === (int) $cat['id']; ?>
        <a 
          href="/stories?category=<?= e($cat['slug']) ?>" 
          class="<?= $chipBase . ' ' . ($isActive ? $chipActive : $chipInactive) ?>"
        >
          <?= e($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <p class="text-xs text-ink-muted pt-1">
      Showing <?= (int) $totalPosts ?> published <?= $totalPosts === 1 ? 'story' : 'stories' ?>
    </p>
  </header>

  <!-- Stories Grid -->
  <?php if (!empty($posts)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
      <?php foreach ($posts as $post): ?>
        <?php \App\Core\View::partial('partials/post-card', ['post' => $post]); ?>
      <?php endforeach; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
      <?php 
        $catParam = $selectedCategory ? '&category=' . urlencode($selectedCategory['slug']) : '';
      ?>
      <nav class="flex items-center justify-center gap-2 pt-8" aria-label="Pagination">
        <?php if ($currentPage > 1): ?>
          <a 
            href="/stories?page=<?= $currentPage - 1 ?><?= $catParam ?>" 
            class="px-4 py-2 rounded-xl border border-border bg-white text-sm font-semibold text-ink hover:bg-surface transition-colors"
          >
            &larr; Previous
          </a>
        <?php endif; ?>

        <span class="px-4 py-2 text-sm text-ink-muted font-medium">
          Page <?= (int) $currentPage ?> of <?= (int) $totalPages ?>
        </span>

        <?php if ($currentPage < $totalPages): ?>
          <a 
            href="/stories?page=<?= $currentPage + 1 ?><?= $catParam ?>" 
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
      <p class="font-serif text-xl font-bold text-ink">No stories found</p>
      <p class="text-sm text-ink-muted">There are currently no published reports in this section.</p>
      <div class="pt-2">
        <a href="/stories" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-primary hover:underline">
          &larr; View all stories
        </a>
      </div>
    </div>
  <?php endif; ?>

</div>

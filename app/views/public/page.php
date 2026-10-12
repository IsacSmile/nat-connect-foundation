<div class="max-w-4xl mx-auto px-5 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-8">

  <!-- Header: Left-aligned Fraunces weight 500 title -->
  <header class="space-y-3 max-w-[68ch]">
    <!-- Exactly ONE H1 -->
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-medium text-ink tracking-tight leading-[1.15]">
      <?= e($page['title']) ?>
    </h1>

    <?php if (!empty($page['updated_at'])): ?>
      <p class="text-xs text-ink-muted">
        Last updated <?= formatDate($page['updated_at']) ?>
      </p>
    <?php endif; ?>
  </header>

  <!-- Editorial Prose Body (Same layout and typography as post article, ~68ch, text-ink, no bordered box) -->
  <main class="prose max-w-[68ch] text-ink leading-relaxed">
    <?= $page['content'] ?>
  </main>

</div>


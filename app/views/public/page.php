<!-- NatConnect Foundation — Public Page Template -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">

  <!-- Header -->
  <header class="space-y-4">
    <!-- Exactly ONE H1 -->
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink tracking-tight leading-[1.15]">
      <?= e($page['title']) ?>
    </h1>

    <?php if (!empty($page['updated_at'])): ?>
      <p class="text-xs text-ink-muted">
        Last updated <?= formatDate($page['updated_at']) ?>
      </p>
    <?php endif; ?>
  </header>

  <!-- Editorial Prose Body (Same typography as posts, NO like button, beacon, share row, or comments) -->
  <article class="bg-white rounded-3xl border border-border p-6 sm:p-10 lg:p-12 shadow-soft-sm">
    <div class="prose max-w-none text-ink-soft text-[17px] sm:text-[18px] leading-[1.75]">
      <?= $page['content'] ?>
    </div>
  </article>

</div>

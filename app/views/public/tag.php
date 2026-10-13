<!-- NatConnect Foundation — Tag Archive -->
<div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10">

  <!-- Tag Header -->
  <?php if (!empty($tag['show_as_cause'])): ?>
    <!-- Cause Header -->
    <header class="border-b border-border pb-8 space-y-6">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/10 text-accent-strong text-xs font-semibold uppercase tracking-wider">
        Cause
      </div>

      <div class="grid grid-cols-1 <?= !empty($tag['image_path']) ? 'lg:grid-cols-12 gap-8 items-center' : '' ?>">
        <?php if (!empty($tag['image_path'])): ?>
          <?php
            $causeImg800 = \App\Core\ImageUploader::getVariantUrl($tag['image_path'], 800);
            $causeAlt = !empty($tag['image_alt']) ? $tag['image_alt'] : $tag['name'];
            $causeAttrs = \App\Core\ImageUploader::getResponsiveAttributes($tag['image_path'], '(max-width: 1024px) 100vw, 500px');
          ?>
          <div class="lg:col-span-5 aspect-[16/10] w-full overflow-hidden rounded-xl border border-ink/10 bg-surface shadow-soft-sm">
            <?php if ($causeAttrs): ?>
              <img 
                src="<?= e($causeAttrs['src']) ?>" 
                srcset="<?= e($causeAttrs['srcset']) ?>" 
                sizes="<?= e($causeAttrs['sizes']) ?>" 
                alt="<?= e($causeAlt) ?>" 
                class="w-full h-full object-cover"
                loading="eager"
              >
            <?php else: ?>
              <img 
                src="<?= e($causeImg800) ?>" 
                alt="<?= e($causeAlt) ?>" 
                class="w-full h-full object-cover"
                loading="eager"
              >
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="<?= !empty($tag['image_path']) ? 'lg:col-span-7' : 'max-w-3xl' ?> space-y-4">
          <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink">
            <?= e($tag['name']) ?>
          </h1>

          <?php if (!empty($tag['description'])): ?>
            <p class="text-base sm:text-lg text-ink-muted leading-relaxed">
              <?= e($tag['description']) ?>
            </p>
          <?php endif; ?>

          <p class="text-xs text-ink-muted">
            Showing <?= (int) $pagination['count'] ?> published <?= $pagination['count'] === 1 ? 'story' : 'stories' ?>
          </p>
        </div>
      </div>
    </header>
  <?php else: ?>
    <!-- Standard Tag Header -->
    <header class="border-b border-border pb-8 max-w-3xl space-y-3">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wider">
        Tag Archive
      </div>
      
      <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink">
        #<?= e($tag['name']) ?>
      </h1>

      <?php if (!empty($tag['description'])): ?>
        <p class="text-sm sm:text-base text-ink-muted leading-relaxed">
          <?= e($tag['description']) ?>
        </p>
      <?php endif; ?>

      <p class="text-xs text-ink-muted">
        Showing <?= (int) $pagination['count'] ?> published <?= $pagination['count'] === 1 ? 'post' : 'posts' ?>
      </p>
    </header>
  <?php endif; ?>

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
      <p class="text-sm text-ink-muted">There are currently no published reports associated with this tag.</p>
      <div class="pt-2">
        <a href="/" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-primary hover:underline">
          &larr; Return to Homepage
        </a>
      </div>
    </div>
  <?php endif; ?>

</div>

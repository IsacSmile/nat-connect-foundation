<!-- NatConnect Foundation — Styled 404 Page -->
<div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-12 sm:py-20 space-y-16">

  <!-- 404 Notice & Search Box -->
  <div class="text-center max-w-xl mx-auto space-y-6">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold uppercase tracking-wider">
      Error 404
    </div>

    <!-- Exactly ONE h1 -->
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink tracking-tight">
      Page Not Found
    </h1>

    <p class="text-base text-ink-muted leading-relaxed">
      The story or page you were looking for may have moved, been renamed, or does not exist. Try searching our reports below:
    </p>

    <!-- Search Box -->
    <form action="/search" method="GET" class="relative max-w-md mx-auto">
      <input 
        type="search" 
        name="q" 
        placeholder="Search posts, causes, RTI..." 
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
  </div>

  <!-- Latest 3 Published Posts -->
  <?php if (!empty($latestPosts)): ?>
    <section class="space-y-6">
      <div class="border-b border-border pb-3">
        <h2 class="font-serif text-xl sm:text-2xl font-bold text-ink">
          Recent Stories You Might Like
        </h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
        <?php foreach ($latestPosts as $p): ?>
          <?php \App\Core\View::partial('partials/post-card', ['post' => $p]); ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</div>

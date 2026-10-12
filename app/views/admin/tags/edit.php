<!-- NatConnect Foundation — Edit Tag (Design Phase D1) -->
<?php
  $nameVal = $old['name'] ?? $tag['name'];
  $slugVal = $old['slug'] ?? $tag['slug'];
  $descVal = $old['description'] ?? ($tag['description'] ?? '');
  $topicVal = isset($old['show_as_topic']) ? !empty($old['show_as_topic']) : !empty($tag['show_as_topic']);
  $causeVal = isset($old['show_as_cause']) ? !empty($old['show_as_cause']) : !empty($tag['show_as_cause']);
  $orderVal = (int) ($old['sort_order'] ?? ($tag['sort_order'] ?? 0));
  $imageAltVal = $old['image_alt'] ?? ($tag['image_alt'] ?? '');
  $hasImage = !empty($tag['image_path']);
?>

<div class="space-y-6 max-w-4xl mx-auto pb-12" x-data="{ removeImage: false }">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a 
        href="/admin/tags" 
        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-border text-ink hover:bg-surface transition-colors"
        aria-label="Back to tags"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
      </a>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Edit Tag: <?= e($tag['name']) ?></h1>
    </div>
  </div>

  <!-- Flash Messages -->
  <?php if (!empty($errors['general'])): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium">
      <?= e($errors['general']) ?>
    </div>
  <?php endif; ?>

  <form 
    action="/admin/tags/<?= (int) $tag['id'] ?>/edit" 
    method="POST" 
    enctype="multipart/form-data" 
    class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-6"
  >
    <?= \App\Core\Csrf::field() ?>

    <!-- Name & Slug -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Name <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          id="name" 
          name="name" 
          required 
          value="<?= e($nameVal) ?>"
          class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['name']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
        >
        <?php if (isset($errors['name'])): ?>
          <p class="text-xs text-rose-600 mt-1"><?= e($errors['name']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="slug" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Slug <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          id="slug" 
          name="slug" 
          required 
          value="<?= e($slugVal) ?>"
          class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['slug']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink font-mono"
        >
        <p class="text-[11px] text-ink-subtle mt-1">Changing the slug creates an automatic 301 redirect.</p>
        <?php if (isset($errors['slug'])): ?>
          <p class="text-xs text-rose-600 mt-1"><?= e($errors['slug']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <div>
      <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
        Description
      </label>
      <textarea 
        id="description" 
        name="description" 
        rows="3" 
        class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['description']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
        placeholder="Required if this tag is marked as a cause."
      ><?= e($descVal) ?></textarea>
      <p class="text-[11px] text-ink-subtle mt-1">Causes must have a description.</p>
      <?php if (isset($errors['description'])): ?>
        <p class="text-xs text-rose-600 mt-1"><?= e($errors['description']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Image Upload & Alt Text -->
    <div class="p-5 bg-surface rounded-xl border border-border space-y-4">
      <h3 class="font-serif text-sm font-bold text-ink">Tag / Cause Image</h3>
      <p class="text-xs text-ink-muted">A cause must have an image and descriptive alt text. WebP variants (1600, 800, 400) generated automatically.</p>

      <?php if ($hasImage): ?>
        <div x-show="!removeImage" class="flex items-center gap-4 p-3 bg-white rounded-xl border border-border">
          <img src="<?= e($tag['image_path']) ?>" alt="<?= e($tag['image_alt'] ?? $tag['name']) ?>" class="w-20 h-20 object-cover rounded-lg border border-border shrink-0">
          <div class="space-y-1">
            <p class="text-xs font-semibold text-ink">Current Image</p>
            <p class="text-xs text-ink-muted font-mono truncate max-w-xs"><?= e($tag['image_path']) ?></p>
            <button 
              type="button" 
              @click="removeImage = true" 
              class="text-xs text-rose-600 hover:text-rose-700 font-semibold underline"
            >
              Remove image
            </button>
          </div>
        </div>
        <input type="hidden" name="remove_image" :value="removeImage ? '1' : '0'">
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="tag_image" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            <?= $hasImage ? 'Replace Image' : 'Upload Image' ?>
          </label>
          <input 
            type="file" 
            id="tag_image" 
            name="image" 
            accept="image/jpeg,image/png,image/webp" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-ink hover:file:bg-border/60"
          >
          <?php if (isset($errors['image'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['image']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="image_alt" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Alternative Text (Alt Text)
          </label>
          <input 
            type="text" 
            id="image_alt" 
            name="image_alt" 
            value="<?= e($imageAltVal) ?>" 
            placeholder="Describe the image content..."
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['image_alt']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white"
          >
          <p class="text-[11px] text-ink-subtle mt-1">Required if an image is provided. Cannot be a filename.</p>
          <?php if (isset($errors['image_alt'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['image_alt']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Home Page Taxonomies & Order -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-border">
      <div class="flex items-center">
        <label class="relative flex items-start gap-2.5 cursor-pointer">
          <input 
            type="checkbox" 
            name="show_as_topic" 
            value="1" 
            <?= $topicVal ? 'checked' : '' ?>
            class="mt-0.5 rounded border-border text-primary focus:ring-primary h-4 w-4"
          >
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-ink block">Show as topic chip on home</span>
            <span class="text-[11px] text-ink-subtle block">Displayed in "Explore by topic" filter chips.</span>
          </div>
        </label>
      </div>

      <div class="flex items-center">
        <label class="relative flex items-start gap-2.5 cursor-pointer">
          <input 
            type="checkbox" 
            name="show_as_cause" 
            value="1" 
            <?= $causeVal ? 'checked' : '' ?>
            class="mt-0.5 rounded border-border text-primary focus:ring-primary h-4 w-4"
          >
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-ink block">Show as a cause on home</span>
            <span class="text-[11px] text-ink-subtle block">Displayed in "Causes we care about" section.</span>
          </div>
        </label>
      </div>

      <div>
        <label for="sort_order" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
          Sort Order
        </label>
        <input 
          type="number" 
          id="sort_order" 
          name="sort_order" 
          value="<?= $orderVal ?>" 
          class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
        >
        <p class="text-[11px] text-ink-subtle mt-1">Lower numbers display first (0, 1, 2...).</p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between pt-4 border-t border-border">
      <a 
        href="/admin/tags" 
        class="px-4 py-2.5 border border-border text-ink hover:bg-surface text-sm font-semibold rounded-xl transition-colors"
      >
        Cancel
      </a>
      <button 
        type="submit" 
        class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
      >
        Update Tag
      </button>
    </div>

  </form>

</div>

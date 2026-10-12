<!-- NatConnect Foundation — Admin Settings (Design Phase D1) -->
<?php
  $activeTab = $currentTab ?? 'general';
?>
<div class="space-y-6 max-w-4xl mx-auto pb-12" x-data="{ tab: '<?= e($activeTab) ?>' }">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Settings</h1>
  </div>

  <!-- Tabs Navigation -->
  <div class="flex items-center gap-2 border-b border-border">
    <button 
      type="button" 
      @click="tab = 'general'" 
      :class="tab === 'general' ? 'border-primary text-primary font-semibold' : 'border-transparent text-ink-muted hover:text-ink font-medium'"
      class="px-4 py-2.5 text-sm border-b-2 transition-colors min-h-[44px]"
    >
      General
    </button>
    <button 
      type="button" 
      @click="tab = 'home'" 
      :class="tab === 'home' ? 'border-primary text-primary font-semibold' : 'border-transparent text-ink-muted hover:text-ink font-medium'"
      class="px-4 py-2.5 text-sm border-b-2 transition-colors min-h-[44px]"
    >
      Home page
    </button>
  </div>

  <!-- Flash Messages -->
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="p-4 rounded-xl bg-accent/10 border border-accent/20 text-accent-strong text-sm font-medium flex items-center justify-between">
      <span><?= e($_SESSION['flash_success']) ?></span>
      <button type="button" @click="$el.parentElement.remove()" class="text-accent-strong hover:text-accent-hover p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <form action="/admin/settings" method="POST" enctype="multipart/form-data" class="space-y-6">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="_tab" :value="tab">

    <!-- ============================================================= -->
    <!-- TAB 1: GENERAL SETTINGS (Site, Contact, Social, Donate)      -->
    <!-- ============================================================= -->
    <div x-show="tab === 'general'" class="space-y-6">

      <!-- 1. Site Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5">
        <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Site</h2>

        <!-- Site Name -->
        <div>
          <label for="site_name" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Site Name <span class="text-rose-500">*</span>
          </label>
          <input 
            type="text" 
            id="site_name" 
            name="site_name" 
            required 
            value="<?= e($settings['site_name'] ?? 'NatConnect Foundation') ?>" 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['site_name']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
          <?php if (isset($errors['site_name'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['site_name']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Tagline -->
        <div>
          <label for="site_tagline" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Tagline
          </label>
          <input 
            type="text" 
            id="site_tagline" 
            name="site_tagline" 
            value="<?= e($settings['site_tagline'] ?? '') ?>" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
        </div>

        <!-- Mission Statement -->
        <div>
          <label for="mission_statement" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Mission statement
          </label>
          <textarea 
            id="mission_statement" 
            name="mission_statement" 
            rows="2" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ><?= e($settings['mission_statement'] ?? '') ?></textarea>
          <p class="text-xs text-ink-muted mt-1">Shown in the home intro section under the site name.</p>
        </div>

        <!-- Footer About Text -->
        <div>
          <label for="footer_about" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Footer About Text
          </label>
          <textarea 
            id="footer_about" 
            name="footer_about" 
            rows="3" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ><?= e($settings['footer_about'] ?? '') ?></textarea>
        </div>

        <!-- Gallery Page Intro -->
        <div>
          <label for="gallery_intro" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Gallery Page Intro Text
          </label>
          <textarea 
            id="gallery_intro" 
            name="gallery_intro" 
            rows="2" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            placeholder="Optional introductory sentence shown at the top of the photo gallery page"
          ><?= e($settings['gallery_intro'] ?? '') ?></textarea>
          <p class="text-xs text-ink-muted mt-1">Intro text for the public gallery page. Leave empty to hide.</p>
        </div>
      </div>

      <!-- 2. Contact Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5">
        <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Contact</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- Email -->
          <div>
            <label for="contact_email" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Email Address
            </label>
            <input 
              type="email" 
              id="contact_email" 
              name="contact_email" 
              value="<?= e($settings['contact_email'] ?? '') ?>" 
              class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['contact_email']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
            <?php if (isset($errors['contact_email'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['contact_email']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Phone -->
          <div>
            <label for="contact_phone" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Phone Number
            </label>
            <input 
              type="text" 
              id="contact_phone" 
              name="contact_phone" 
              value="<?= e($settings['contact_phone'] ?? '') ?>" 
              class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- WhatsApp Number -->
          <div>
            <label for="contact_whatsapp" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              WhatsApp Number
            </label>
            <input 
              type="text" 
              id="contact_whatsapp" 
              name="contact_whatsapp" 
              value="<?= e($settings['contact_whatsapp'] ?? '') ?>" 
              class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
          </div>
        </div>

        <!-- Address -->
        <div>
          <label for="contact_address" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Address
          </label>
          <textarea 
            id="contact_address" 
            name="contact_address" 
            rows="2" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ><?= e($settings['contact_address'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- 3. Social Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5">
        <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Social</h2>
        <p class="text-xs text-ink-muted">Leave blank to hide the link on the website.</p>

        <div class="space-y-4">
          <!-- Facebook -->
          <div>
            <label for="social_facebook" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Facebook URL
            </label>
            <input 
              type="url" 
              id="social_facebook" 
              name="social_facebook" 
              value="<?= e($settings['social_facebook'] ?? '') ?>" 
              placeholder="https://facebook.com/..." 
              class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['social_facebook']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
            <?php if (isset($errors['social_facebook'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['social_facebook']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Instagram -->
          <div>
            <label for="social_instagram" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Instagram URL
            </label>
            <input 
              type="url" 
              id="social_instagram" 
              name="social_instagram" 
              value="<?= e($settings['social_instagram'] ?? '') ?>" 
              placeholder="https://instagram.com/..." 
              class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['social_instagram']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
            <?php if (isset($errors['social_instagram'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['social_instagram']) ?></p>
            <?php endif; ?>
          </div>

          <!-- X (Twitter) -->
          <div>
            <label for="social_x" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              X (Twitter) URL
            </label>
            <input 
              type="url" 
              id="social_x" 
              name="social_x" 
              value="<?= e($settings['social_x'] ?? '') ?>" 
              placeholder="https://x.com/..." 
              class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['social_x']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
            <?php if (isset($errors['social_x'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['social_x']) ?></p>
            <?php endif; ?>
          </div>

          <!-- YouTube -->
          <div>
            <label for="social_youtube" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              YouTube URL
            </label>
            <input 
              type="url" 
              id="social_youtube" 
              name="social_youtube" 
              value="<?= e($settings['social_youtube'] ?? '') ?>" 
              placeholder="https://youtube.com/..." 
              class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['social_youtube']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
            <?php if (isset($errors['social_youtube'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['social_youtube']) ?></p>
            <?php endif; ?>
          </div>

          <!-- LinkedIn -->
          <div>
            <label for="social_linkedin" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              LinkedIn URL
            </label>
            <input 
              type="url" 
              id="social_linkedin" 
              name="social_linkedin" 
              value="<?= e($settings['social_linkedin'] ?? '') ?>" 
              placeholder="https://linkedin.com/in/..." 
              class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['social_linkedin']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
            >
            <?php if (isset($errors['social_linkedin'])): ?>
              <p class="text-xs text-rose-600 mt-1"><?= e($errors['social_linkedin']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- 4. Donate Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5" x-data="{ removeQr: false }">
        <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Donate</h2>
        <p class="text-xs text-ink-muted">If all donation fields are left blank, donation links and the donate page are hidden.</p>

        <!-- Donate Intro Text -->
        <div>
          <label for="donate_intro" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Donate Intro Text
          </label>
          <textarea 
            id="donate_intro" 
            name="donate_intro" 
            rows="3" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ><?= e($settings['donate_intro'] ?? '') ?></textarea>
        </div>

        <!-- UPI ID -->
        <div>
          <label for="donate_upi_id" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            UPI ID
          </label>
          <input 
            type="text" 
            id="donate_upi_id" 
            name="donate_upi_id" 
            value="<?= e($settings['donate_upi_id'] ?? '') ?>" 
            placeholder="e.g. natconnect@upi" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
        </div>

        <!-- UPI QR Code Image -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            UPI QR Code Image
          </label>
          
          <?php $hasQr = !empty($settings['donate_upi_qr']); ?>
          <?php if ($hasQr): ?>
            <div x-show="!removeQr" class="mb-3 flex items-center gap-4 p-3 bg-surface rounded-xl border border-border">
              <img src="<?= e($settings['donate_upi_qr']) ?>" alt="UPI QR Code" class="w-24 h-24 object-contain rounded-lg border border-border bg-white p-1">
              <div class="space-y-1">
                <p class="text-xs font-semibold text-ink">Current QR Code</p>
                <button 
                  type="button" 
                  @click="removeQr = true" 
                  class="text-xs text-rose-600 hover:text-rose-700 font-semibold underline"
                >
                  Remove QR code
                </button>
              </div>
            </div>
            <input type="hidden" name="remove_upi_qr" :value="removeQr ? '1' : '0'">
          <?php endif; ?>

          <input 
            type="file" 
            id="upi_qr_file" 
            name="upi_qr_file" 
            accept="image/jpeg,image/png,image/webp" 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['donate_upi_qr']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-ink hover:file:bg-border/60"
          >
          <?php if (isset($errors['donate_upi_qr'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['donate_upi_qr']) ?></p>
          <?php endif; ?>
          <p class="text-[11px] text-ink-subtle mt-1">JPEG, PNG, or WebP. Strips EXIF metadata automatically.</p>
        </div>

        <!-- Bank Details Text -->
        <div>
          <label for="donate_bank_details" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Bank Account Details
          </label>
          <textarea 
            id="donate_bank_details" 
            name="donate_bank_details" 
            rows="4" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink font-mono"
          ><?= e($settings['donate_bank_details'] ?? '') ?></textarea>
        </div>
      </div>

    </div>

    <!-- ============================================================= -->
    <!-- TAB 2: HOME PAGE SETTINGS (Announcement, Hero, Sections)      -->
    <!-- ============================================================= -->
    <div x-show="tab === 'home'" class="space-y-6">

      <!-- 1. Announcement Bar Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5">
        <div class="flex items-center justify-between border-b border-border pb-3">
          <div>
            <h2 class="font-serif text-lg font-bold text-ink">Announcement Bar</h2>
            <p class="text-xs text-ink-muted">A dismissible or sticky notice shown at the very top of the home page. Empty = hidden.</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input 
              type="checkbox" 
              name="section_show_announcement" 
              value="1" 
              <?= !empty($settings['section_show_announcement']) ? 'checked' : '' ?>
              class="rounded border-border text-primary focus:ring-primary h-4 w-4"
            >
            <span class="ml-2 text-xs font-semibold text-ink">Show on Home</span>
          </label>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="announcement_text" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">
              Announcement Text
            </label>
            <span class="text-[11px] text-ink-subtle">Max 120 chars</span>
          </div>
          <input 
            type="text" 
            id="announcement_text" 
            name="announcement_text" 
            maxlength="120"
            value="<?= e($settings['announcement_text'] ?? '') ?>" 
            placeholder="e.g. Join our upcoming coastal cleanup campaign this Saturday in Uran." 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['announcement_text']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
          <?php if (isset($errors['announcement_text'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['announcement_text']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="announcement_link" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Announcement Link (Optional)
          </label>
          <input 
            type="text" 
            id="announcement_link" 
            name="announcement_link" 
            value="<?= e($settings['announcement_link'] ?? '') ?>" 
            placeholder="e.g. /post/coastal-cleanup-drive or https://..." 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink font-mono"
          >
        </div>
      </div>

      <!-- 2. Hero Section Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5">
        <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">Hero Section</h2>
        <p class="text-xs text-ink-muted">The primary headline and introduction shown at the top of the home page. Defaults are flagged for review before launch.</p>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="home_hero_headline" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">
              Hero Headline
            </label>
            <span class="text-[11px] text-ink-subtle">Max 80 chars</span>
          </div>
          <input 
            type="text" 
            id="home_hero_headline" 
            name="home_hero_headline" 
            maxlength="80"
            value="<?= e($settings['home_hero_headline'] ?? '') ?>" 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['home_hero_headline']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink font-serif font-bold"
          >
          <?php if (isset($errors['home_hero_headline'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['home_hero_headline']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="home_hero_intro" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">
              Hero Intro
            </label>
            <span class="text-[11px] text-ink-subtle">Max 200 chars</span>
          </div>
          <textarea 
            id="home_hero_intro" 
            name="home_hero_intro" 
            rows="3" 
            maxlength="200"
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['home_hero_intro']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          ><?= e($settings['home_hero_intro'] ?? '') ?></textarea>
          <?php if (isset($errors['home_hero_intro'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['home_hero_intro']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Auto-rotate Hero Slider -->
        <div class="pt-3 border-t border-border">
          <label class="relative inline-flex items-center cursor-pointer">
            <input 
              type="checkbox" 
              name="hero_slider_autorotate" 
              value="1" 
              <?= !empty($settings['hero_slider_autorotate']) ? 'checked' : '' ?>
              class="rounded border-border text-primary focus:ring-primary h-4 w-4"
            >
            <span class="ml-2.5 text-xs font-semibold text-ink">Auto-rotate hero slider</span>
          </label>
          <p class="text-xs text-ink-muted mt-1">Rotates slides every 7 seconds (default OFF). Pauses on hover, focus, touch, and hidden tabs. Disabled if visitor prefers reduced motion.</p>
        </div>
      </div>

      <!-- Impact Numbers Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5">
        <div class="border-b border-border pb-3">
          <h2 class="font-serif text-lg font-bold text-ink">Impact Numbers</h2>
          <p class="text-xs text-ink-muted mt-0.5">Up to 4 number/label pairs displayed under the hero. Shown 2x2 on mobile, 4 in a row on desktop. Hidden entirely if none are filled.</p>
          <p class="text-xs text-accent-strong font-medium mt-1">Only use real, verifiable figures.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <?php for ($i = 1; $i <= 4; $i++): ?>
            <?php
              $numKey = "home_impact_number_{$i}";
              $lblKey = "home_impact_label_{$i}";
            ?>
            <div class="p-4 rounded-xl border border-border bg-surface/50 space-y-3">
              <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted block">Figure <?= $i ?></span>
              <div>
                <label for="<?= $numKey ?>" class="block text-[11px] text-ink-muted mb-1">Number Text (Max 12 chars)</label>
                <input 
                  type="text" 
                  id="<?= $numKey ?>" 
                  name="<?= $numKey ?>" 
                  maxlength="12"
                  placeholder="e.g. 50+ or 1,200" 
                  value="<?= e($settings[$numKey] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border <?= isset($errors[$numKey]) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm font-serif font-bold text-ink bg-white"
                >
                <?php if (isset($errors[$numKey])): ?>
                  <p class="text-xs text-rose-600 mt-1"><?= e($errors[$numKey]) ?></p>
                <?php endif; ?>
              </div>
              <div>
                <label for="<?= $lblKey ?>" class="block text-[11px] text-ink-muted mb-1">Label (Max 40 chars)</label>
                <input 
                  type="text" 
                  id="<?= $lblKey ?>" 
                  name="<?= $lblKey ?>" 
                  maxlength="40"
                  placeholder="e.g. RTI Applications Filed" 
                  value="<?= e($settings[$lblKey] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border <?= isset($errors[$lblKey]) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink bg-white"
                >
                <?php if (isset($errors[$lblKey])): ?>
                  <p class="text-xs text-rose-600 mt-1"><?= e($errors[$lblKey]) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Get Involved Section Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-6">
        <div class="flex items-center justify-between border-b border-border pb-3">
          <div>
            <h2 class="font-serif text-lg font-bold text-ink">Get Involved Section</h2>
            <p class="text-xs text-ink-muted mt-0.5">3 action cards encouraging community engagement (id="get-involved").</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input 
              type="checkbox" 
              name="section_show_get_involved" 
              value="1" 
              <?= !empty($settings['section_show_get_involved'] ?? '1') ? 'checked' : '' ?>
              class="rounded border-border text-primary focus:ring-primary h-4 w-4"
            >
            <span class="ml-2 text-xs font-semibold text-ink">Show section</span>
          </label>
        </div>

        <div>
          <label for="section_title_get_involved" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Section Title
          </label>
          <input 
            type="text" 
            id="section_title_get_involved" 
            name="section_title_get_involved" 
            value="<?= e($settings['section_title_get_involved'] ?? 'Get involved') ?>" 
            class="w-full px-3.5 py-2.5 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink font-serif font-bold"
          >
        </div>

        <div class="space-y-6 divide-y divide-border">
          <?php
            $defaultCardIcons = ['hand-heart', 'megaphone', 'pen'];
            $defaultCardTitles = ['Volunteer with us', 'Report an environmental concern', 'Share a story'];
          ?>
          <?php for ($i = 1; $i <= 3; $i++): ?>
            <?php
              $tKey = "home_card_{$i}_title";
              $xKey = "home_card_{$i}_text";
              $bKey = "home_card_{$i}_btn_label";
              $lKey = "home_card_{$i}_link";
              $wKey = "home_card_{$i}_wa_message";
            ?>
            <div class="pt-5 first:pt-0 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-ink">
                  Card <?= $i ?>: <?= e($defaultCardTitles[$i - 1]) ?> (Fixed icon: <?= $defaultCardIcons[$i - 1] ?>)
                </span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="<?= $tKey ?>" class="block text-[11px] text-ink-muted mb-1">Title</label>
                  <input 
                    type="text" 
                    id="<?= $tKey ?>" 
                    name="<?= $tKey ?>" 
                    value="<?= e($settings[$tKey] ?? $defaultCardTitles[$i - 1]) ?>" 
                    class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  >
                </div>
                <div>
                  <label for="<?= $bKey ?>" class="block text-[11px] text-ink-muted mb-1">Button Label</label>
                  <input 
                    type="text" 
                    id="<?= $bKey ?>" 
                    name="<?= $bKey ?>" 
                    value="<?= e($settings[$bKey] ?? 'Contact us') ?>" 
                    class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  >
                </div>
              </div>
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="<?= $xKey ?>" class="block text-[11px] text-ink-muted">Short Text</label>
                  <span class="text-[10px] text-ink-subtle">Max 140 chars</span>
                </div>
                <textarea 
                  id="<?= $xKey ?>" 
                  name="<?= $xKey ?>" 
                  rows="2" 
                  maxlength="140"
                  class="w-full px-3.5 py-2 rounded-xl border <?= isset($errors[$xKey]) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink"
                ><?= e($settings[$xKey] ?? '') ?></textarea>
                <?php if (isset($errors[$xKey])): ?>
                  <p class="text-xs text-rose-600 mt-1"><?= e($errors[$xKey]) ?></p>
                <?php endif; ?>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="<?= $lKey ?>" class="block text-[11px] text-ink-muted mb-1">Link Destination</label>
                  <input 
                    type="text" 
                    id="<?= $lKey ?>" 
                    name="<?= $lKey ?>" 
                    value="<?= e($settings[$lKey] ?? '/contact') ?>" 
                    placeholder="e.g. /contact, https://..., or 'whatsapp'" 
                    class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink font-mono"
                  >
                  <p class="text-[10px] text-ink-subtle mt-1">Set to <code>whatsapp</code> to open WhatsApp, or enter a path/URL.</p>
                </div>
                <div>
                  <label for="<?= $wKey ?>" class="block text-[11px] text-ink-muted mb-1">WhatsApp Prefilled Message</label>
                  <input 
                    type="text" 
                    id="<?= $wKey ?>" 
                    name="<?= $wKey ?>" 
                    value="<?= e($settings[$wKey] ?? '') ?>" 
                    placeholder="e.g. Hello NatConnect, I would like to volunteer." 
                    class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink"
                  >
                  <p class="text-[10px] text-ink-subtle mt-1">Used when Link Destination is set to <code>whatsapp</code>.</p>
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- About Band Photo Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-5" x-data="{ removeAboutPhoto: false }">
        <h2 class="font-serif text-lg font-bold text-ink border-b border-border pb-3">About Band Photo</h2>
        <p class="text-xs text-ink-muted">Optional photo (4:3 aspect ratio) shown in the About section on the home page. When provided, the photo is displayed alongside the mission statement.</p>

        <?php if (!empty($settings['about_band_photo'])): ?>
          <div x-show="!removeAboutPhoto" class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl border border-border bg-surface/50">
            <img 
              src="<?= e(\App\Core\ImageUploader::getVariantUrl($settings['about_band_photo'], 400)) ?>" 
              alt="<?= e($settings['about_band_photo_alt'] ?? 'About band photo') ?>" 
              class="w-32 h-24 object-cover rounded-lg border border-border"
            >
            <div class="space-y-2">
              <p class="text-xs text-ink font-medium">Current photo configured</p>
              <label class="inline-flex items-center cursor-pointer text-xs text-rose-600 hover:text-rose-700">
                <input 
                  type="checkbox" 
                  name="remove_about_band_photo" 
                  value="1" 
                  x-model="removeAboutPhoto"
                  class="rounded border-border text-rose-600 focus:ring-rose-500 h-4 w-4 mr-1.5"
                >
                Remove photo
              </label>
            </div>
          </div>
        <?php endif; ?>

        <div>
          <label for="about_band_photo_file" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            <?= !empty($settings['about_band_photo']) ? 'Replace Photo' : 'Upload Photo (4:3 Recommended)' ?>
          </label>
          <input 
            type="file" 
            id="about_band_photo_file" 
            name="about_band_photo_file" 
            accept="image/jpeg,image/png,image/webp" 
            class="w-full text-xs text-ink file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-hover file:cursor-pointer"
          >
          <?php if (isset($errors['about_band_photo'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['about_band_photo']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="about_band_photo_alt" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
            Photo Alternative Text (Alt Text)
          </label>
          <input 
            type="text" 
            id="about_band_photo_alt" 
            name="about_band_photo_alt" 
            value="<?= e($settings['about_band_photo_alt'] ?? '') ?>" 
            placeholder="Descriptive text for accessibility (required if photo is set)" 
            class="w-full px-3.5 py-2.5 rounded-xl border <?= isset($errors['about_band_photo_alt']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
          >
          <?php if (isset($errors['about_band_photo_alt'])): ?>
            <p class="text-xs text-rose-600 mt-1"><?= e($errors['about_band_photo_alt']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- 3. Section Titles, Intros & Visibility Card -->
      <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm space-y-6">
        <div class="border-b border-border pb-3">
          <h2 class="font-serif text-lg font-bold text-ink">Section Titles & Visibility</h2>
          <p class="text-xs text-ink-muted mt-0.5">Customize headings and intro texts for home page sections, or toggle their visibility.</p>
        </div>

        <div class="space-y-6 divide-y divide-border">

          <!-- Section: Latest Stories -->
          <div class="pt-4 first:pt-0 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Latest Stories</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_latest" 
                  value="1" 
                  <?= !empty($settings['section_show_latest'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="section_title_latest" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_latest" 
                  name="section_title_latest" 
                  value="<?= e($settings['section_title_latest'] ?? 'Latest stories') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_latest" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_latest" 
                  name="section_subtitle_latest" 
                  value="<?= e($settings['section_subtitle_latest'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
            </div>
          </div>

          <!-- Section: Explore by topic -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Explore by Topic</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_topics" 
                  value="1" 
                  <?= !empty($settings['section_show_topics'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="section_title_topics" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_topics" 
                  name="section_title_topics" 
                  value="<?= e($settings['section_title_topics'] ?? 'Explore by topic') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_topics" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_topics" 
                  name="section_subtitle_topics" 
                  value="<?= e($settings['section_subtitle_topics'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
            </div>
          </div>

          <!-- Section: Causes we care about -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Causes</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_causes" 
                  value="1" 
                  <?= !empty($settings['section_show_causes'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="section_title_causes" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_causes" 
                  name="section_title_causes" 
                  value="<?= e($settings['section_title_causes'] ?? 'Causes we care about') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_causes" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_causes" 
                  name="section_subtitle_causes" 
                  value="<?= e($settings['section_subtitle_causes'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
            </div>
          </div>

          <!-- Section: Campaigns -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Campaigns</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_campaigns" 
                  value="1" 
                  <?= !empty($settings['section_show_campaigns'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="section_title_campaigns" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_campaigns" 
                  name="section_title_campaigns" 
                  value="<?= e($settings['section_title_campaigns'] ?? 'Campaigns') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_campaigns" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_campaigns" 
                  name="section_subtitle_campaigns" 
                  value="<?= e($settings['section_subtitle_campaigns'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
            </div>
          </div>

          <!-- Section: Right to Information -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Right to Information (RTI)</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_rti" 
                  value="1" 
                  <?= !empty($settings['section_show_rti'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="section_title_rti" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_rti" 
                  name="section_title_rti" 
                  value="<?= e($settings['section_title_rti'] ?? 'Right to Information') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_rti" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_rti" 
                  name="section_subtitle_rti" 
                  value="<?= e($settings['section_subtitle_rti'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="section_intro_rti" class="block text-[11px] text-ink-muted">Intro Text</label>
                  <span class="text-[10px] text-ink-subtle">Max 200 chars</span>
                </div>
                <textarea 
                  id="section_intro_rti" 
                  name="section_intro_rti" 
                  rows="2" 
                  maxlength="200"
                  class="w-full px-3.5 py-2 rounded-xl border <?= isset($errors['section_intro_rti']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink"
                ><?= e($settings['section_intro_rti'] ?? 'Our ongoing investigations and RTI appeals uncovering wetland encroachments and environmental governance issues.') ?></textarea>
                <?php if (isset($errors['section_intro_rti'])): ?>
                  <p class="text-xs text-rose-600 mt-1"><?= e($errors['section_intro_rti']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Section: From the gallery -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Gallery</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_gallery" 
                  value="1" 
                  <?= !empty($settings['section_show_gallery'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="section_title_gallery" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_gallery" 
                  name="section_title_gallery" 
                  value="<?= e($settings['section_title_gallery'] ?? 'From the gallery') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_gallery" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_gallery" 
                  name="section_subtitle_gallery" 
                  value="<?= e($settings['section_subtitle_gallery'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
            </div>
          </div>

          <!-- Section: Stay informed (Newsletter) -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Stay Informed (Newsletter)</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_newsletter" 
                  value="1" 
                  <?= !empty($settings['section_show_newsletter'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="section_title_newsletter" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_newsletter" 
                  name="section_title_newsletter" 
                  value="<?= e($settings['section_title_newsletter'] ?? 'Stay informed') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <label for="section_subtitle_newsletter" class="block text-[11px] text-ink-muted mb-1">Section Subtitle (Optional)</label>
                <input 
                  type="text" 
                  id="section_subtitle_newsletter" 
                  name="section_subtitle_newsletter" 
                  value="<?= e($settings['section_subtitle_newsletter'] ?? '') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                  placeholder="Leave empty to hide"
                >
              </div>
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="section_intro_newsletter" class="block text-[11px] text-ink-muted">Intro Text</label>
                  <span class="text-[10px] text-ink-subtle">Max 160 chars</span>
                </div>
                <textarea 
                  id="section_intro_newsletter" 
                  name="section_intro_newsletter" 
                  rows="2" 
                  maxlength="160"
                  class="w-full px-3.5 py-2 rounded-xl border <?= isset($errors['section_intro_newsletter']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink"
                ><?= e($settings['section_intro_newsletter'] ?? 'Grassroots investigations, campaign updates, and environmental governance research from NatConnect Foundation.') ?></textarea>
                <?php if (isset($errors['section_intro_newsletter'])): ?>
                  <p class="text-xs text-rose-600 mt-1"><?= e($errors['section_intro_newsletter']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Section: Support our work (Donate band) -->
          <div class="pt-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold uppercase tracking-wider text-ink">Section: Support Our Work (Donate)</label>
              <label class="inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  name="section_show_donate" 
                  value="1" 
                  <?= !empty($settings['section_show_donate'] ?? '1') ? 'checked' : '' ?>
                  class="rounded border-border text-primary focus:ring-primary h-4 w-4"
                >
                <span class="ml-2 text-xs font-medium text-ink">Show section</span>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="section_title_donate" class="block text-[11px] text-ink-muted mb-1">Section Title</label>
                <input 
                  type="text" 
                  id="section_title_donate" 
                  name="section_title_donate" 
                  value="<?= e($settings['section_title_donate'] ?? 'Support our work') ?>" 
                  class="w-full px-3.5 py-2 rounded-xl border border-border focus:outline-none focus:ring-2 focus:ring-primary text-sm text-ink"
                >
              </div>
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="section_intro_donate" class="block text-[11px] text-ink-muted">Intro Text</label>
                  <span class="text-[10px] text-ink-subtle">Max 160 chars</span>
                </div>
                <textarea 
                  id="section_intro_donate" 
                  name="section_intro_donate" 
                  rows="2" 
                  maxlength="160"
                  class="w-full px-3.5 py-2 rounded-xl border <?= isset($errors['section_intro_donate']) ? 'border-rose-400 bg-rose-50/20' : 'border-border' ?> focus:outline-none focus:ring-2 focus:ring-primary text-xs text-ink"
                ><?= e($settings['section_intro_donate'] ?? 'Your support powers our independent ground investigations, legal notices, and conservation advocacy.') ?></textarea>
                <?php if (isset($errors['section_intro_donate'])): ?>
                  <p class="text-xs text-rose-600 mt-1"><?= e($errors['section_intro_donate']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Submit Button (Navy Primary Style) -->
    <div class="flex items-center justify-end pt-2">
      <button 
        type="submit" 
        class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px]"
      >
        Save
      </button>
    </div>
  </form>

</div>

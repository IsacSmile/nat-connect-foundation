<!-- NatConnect Foundation — Global Public Footer -->
<?php
  $siteName = setting('site_name', 'NatConnect Foundation');
  $footerAbout = setting('footer_about');
  $email = trim((string) setting('contact_email', ''));
  $phone = trim((string) setting('contact_phone', ''));
  $whatsapp = trim((string) setting('contact_whatsapp', ''));
  $address = trim((string) setting('contact_address', ''));
  
  $socials = [
      'facebook'  => setting('social_facebook'),
      'instagram' => setting('social_instagram'),
      'x'         => setting('social_x'),
      'youtube'   => setting('social_youtube'),
      'linkedin'  => setting('social_linkedin'),
  ];
  
  // Filter only set social URLs
  $activeSocials = array_filter($socials, fn($url) => !empty(trim((string)$url)));
  $isDonateActive = \App\Models\SettingModel::isDonateActive();

  // Check if Contact column has any non-empty content
  $hasContact = !empty($address) || !empty($email) || !empty($phone) || !empty($whatsapp);

  // Dynamic grid columns based on whether Contact column is populated
  $gridColsClass = $hasContact
      ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4'
      : 'grid-cols-1 md:grid-cols-3 lg:grid-cols-3';
?>

<footer class="mt-auto bg-white border-t border-border">
  <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-12 lg:py-16">
    <div class="grid <?= $gridColsClass ?> gap-10 lg:gap-8">
      
      <!-- Column 1: Brand & About -->
      <div class="lg:col-span-1 space-y-4">
        <a href="/" class="inline-flex items-center gap-3 group">
          <img 
            src="<?= asset('assets/img/logo-mark.png') ?>" 
            alt="<?= e($siteName) ?>" 
            class="h-10 w-auto object-contain"
          />
          <span class="font-serif font-bold text-lg sm:text-xl text-primary group-hover:text-primary-hover transition-colors">
            <?= e($siteName) ?>
          </span>
        </a>
        
        <?php if (!empty($footerAbout)): ?>
          <p class="text-sm text-ink/80 leading-relaxed">
            <?= e($footerAbout) ?>
          </p>
        <?php endif; ?>

        <!-- Social Icons (Only those configured in Settings) -->
        <?php if (!empty($activeSocials)): ?>
          <div class="pt-2 flex items-center gap-3">
            <?php if (!empty($socials['facebook'])): ?>
              <a href="<?= e($socials['facebook']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="p-2 text-ink-muted hover:text-primary hover:bg-surface rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              </a>
            <?php endif; ?>

            <?php if (!empty($socials['instagram'])): ?>
              <a href="<?= e($socials['instagram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="p-2 text-ink-muted hover:text-primary hover:bg-surface rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
              </a>
            <?php endif; ?>

            <?php if (!empty($socials['x'])): ?>
              <a href="<?= e($socials['x']) ?>" target="_blank" rel="noopener noreferrer" aria-label="X (formerly Twitter)" class="p-2 text-ink-muted hover:text-primary hover:bg-surface rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
            <?php endif; ?>

            <?php if (!empty($socials['youtube'])): ?>
              <a href="<?= e($socials['youtube']) ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="p-2 text-ink-muted hover:text-primary hover:bg-surface rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
              </a>
            <?php endif; ?>

            <?php if (!empty($socials['linkedin'])): ?>
              <a href="<?= e($socials['linkedin']) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="p-2 text-ink-muted hover:text-primary hover:bg-surface rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.53 1.53 0 1 0 0-3.05 1.53 1.53 0 0 0 0 3.05M5.07 18.5h2.79v-8.37H5.07v8.37z"/></svg>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Column 2: Quick Links Navigation -->
      <div>
        <h4 class="font-serif font-bold text-ink text-base mb-4">Quick Links</h4>
        <ul class="space-y-2.5 text-sm text-ink/80">
          <li><a href="/" class="hover:text-primary transition-colors">Home</a></li>
          <?php $aboutPage = \App\Models\PageModel::findBySlugPublished('about'); ?>
          <?php if ($aboutPage): ?>
            <li><a href="/<?= e($aboutPage['slug']) ?>" class="hover:text-primary transition-colors"><?= e($aboutPage['title']) ?></a></li>
          <?php endif; ?>
          <li><a href="/category/causes" class="hover:text-primary transition-colors">Causes</a></li>
          <li><a href="/category/news" class="hover:text-primary transition-colors">News</a></li>
          <li><a href="/category/campaigns" class="hover:text-primary transition-colors">Campaigns</a></li>
          <li><a href="/category/rti-applications" class="hover:text-primary transition-colors">RTI Applications</a></li>
          <li><a href="/gallery" class="hover:text-primary transition-colors">Gallery</a></li>
        </ul>
      </div>

      <!-- Column 3: Information & Support -->
      <div>
        <h4 class="font-serif font-bold text-ink text-base mb-4">Information</h4>
        <ul class="space-y-2.5 text-sm text-ink/80">
          <li><a href="/contact" class="hover:text-primary transition-colors">Contact</a></li>
          <?php $privacyPage = \App\Models\PageModel::findBySlugPublished('privacy'); ?>
          <?php if ($privacyPage): ?>
            <li><a href="/<?= e($privacyPage['slug']) ?>" class="hover:text-primary transition-colors"><?= e($privacyPage['title']) ?></a></li>
          <?php endif; ?>
          <?php if ($isDonateActive): ?>
            <li class="pt-1">
              <a href="/donate" class="inline-flex items-center gap-1.5 text-accent-strong font-semibold hover:text-accent-hover transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
                <span>Donate</span>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <?php if ($hasContact): ?>
      <!-- Column 4: Contact Details from Settings -->
      <div class="space-y-3">
        <h4 class="font-serif font-bold text-ink text-base mb-4">Contact</h4>
        <div class="space-y-2.5 text-sm text-ink/80">
          <?php if (!empty($address)): ?>
            <div class="flex items-start gap-2.5">
              <svg class="w-4 h-4 text-ink-muted shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
              <span><?= nl2br(e($address)) ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($email)): ?>
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-ink-muted shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
              <a href="mailto:<?= e($email) ?>" class="hover:text-primary transition-colors"><?= e($email) ?></a>
            </div>
          <?php endif; ?>

          <?php if (!empty($phone)): ?>
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-ink-muted shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
              <a href="tel:<?= e($phone) ?>" class="hover:text-primary transition-colors"><?= e($phone) ?></a>
            </div>
          <?php endif; ?>

          <?php if (!empty($whatsapp)): ?>
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-accent-strong shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
              <a href="https://wa.me/<?= preg_replace('/[^\d]/', '', $whatsapp) ?>" target="_blank" rel="noopener noreferrer" class="text-accent-strong hover:underline font-medium"><?= e($whatsapp) ?></a>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- Bottom Copyright -->
    <div class="mt-12 pt-8 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-ink-muted">
      <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
      <?php $legalLine = setting('legal_disclosures'); ?>
      <?php if (!empty($legalLine)): ?>
        <p><?= e($legalLine) ?></p>
      <?php endif; ?>
    </div>

  </div>
</footer>

<!-- NatConnect Foundation — Contact Us Page -->
<?php
  $email = $settings['contact_email'] ?? '';
  $phone = $settings['contact_phone'] ?? '';
  $whatsapp = $settings['contact_whatsapp'] ?? '';
  $address = $settings['contact_address'] ?? '';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 space-y-12">

  <!-- Header -->
  <header class="space-y-4 max-w-2xl">
    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-accent/10 border border-accent/20 text-accent-strong font-semibold text-xs tracking-wider uppercase">
      Get In Touch
    </div>

    <!-- Exactly ONE h1 -->
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink tracking-tight">
      Contact Us
    </h1>

    <p class="text-base sm:text-lg text-ink-muted leading-relaxed">
      Reach out to NatConnect Foundation to share environmental reports, RTI queries, or media inquiries.
    </p>
  </header>

  <!-- Contact Cards Grid -->
  <?php
    $hasEmail = !empty($email);
    $hasPhone = !empty($phone);
    $hasWhatsapp = !empty($whatsapp);
    $hasAddress = !empty($address);
    $hasAnyContact = $hasEmail || $hasPhone || $hasWhatsapp || $hasAddress;
  ?>

  <?php if ($hasAnyContact): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- Email -->
      <?php if ($hasEmail): ?>
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-border shadow-soft-sm space-y-3">
          <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
          </div>
          <h2 class="font-serif text-lg font-bold text-ink">Email</h2>
          <p class="text-sm">
            <a href="mailto:<?= e($email) ?>" class="text-primary hover:underline font-semibold break-all"><?= e($email) ?></a>
          </p>
        </div>
      <?php endif; ?>

      <!-- Phone -->
      <?php if ($hasPhone): ?>
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-border shadow-soft-sm space-y-3">
          <div class="w-12 h-12 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
          </div>
          <h2 class="font-serif text-lg font-bold text-ink">Phone</h2>
          <p class="text-sm">
            <a href="tel:<?= e($phone) ?>" class="text-ink hover:text-primary font-semibold"><?= e($phone) ?></a>
          </p>
        </div>
      <?php endif; ?>

      <!-- WhatsApp -->
      <?php if ($hasWhatsapp): ?>
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-border shadow-soft-sm space-y-3">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
          </div>
          <h2 class="font-serif text-lg font-bold text-ink">WhatsApp</h2>
          <p class="text-sm">
            <a 
              href="https://wa.me/<?= preg_replace('/[^\d]/', '', $whatsapp) ?>" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="inline-flex items-center gap-1.5 text-accent-strong hover:underline font-semibold"
            >
              <span>Message on WhatsApp (<?= e($whatsapp) ?>)</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
          </p>
        </div>
      <?php endif; ?>

      <!-- Postal Address -->
      <?php if ($hasAddress): ?>
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-border shadow-soft-sm space-y-3">
          <div class="w-12 h-12 rounded-xl bg-surface text-ink-muted flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
          </div>
          <h2 class="font-serif text-lg font-bold text-ink">Office Address</h2>
          <p class="text-sm text-ink/80 leading-relaxed">
            <?= nl2br(e($address)) ?>
          </p>
        </div>
      <?php endif; ?>

    </div>
  <?php else: ?>
    <div class="bg-white p-10 rounded-2xl border border-border shadow-soft-sm text-center max-w-lg mx-auto">
      <p class="text-sm text-ink-muted">Please check back soon for our updated contact information.</p>
    </div>
  <?php endif; ?>

</div>

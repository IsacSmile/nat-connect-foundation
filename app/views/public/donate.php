<!-- NatConnect Foundation — Donate Page -->
<?php
  $intro = $settings['donate_intro'] ?? '';
  $upiId = $settings['donate_upi_id'] ?? '';
  $upiQr = $settings['donate_upi_qr'] ?? '';
  $bankDetails = $settings['donate_bank_details'] ?? '';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 space-y-12">

  <!-- Header -->
  <header class="space-y-4 max-w-2xl">
    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-accent/10 border border-accent/20 text-accent-strong font-semibold text-xs tracking-wider uppercase">
      Support Our Mission
    </div>

    <!-- Exactly ONE h1 -->
    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-ink tracking-tight">
      Donate to NatConnect Foundation
    </h1>

    <?php if (!empty($intro)): ?>
      <p class="text-base sm:text-lg text-ink/85 leading-relaxed">
        <?= nl2br(e($intro)) ?>
      </p>
    <?php endif; ?>
  </header>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">

    <!-- UPI Donation Card (Rendered if either UPI ID or UPI QR is present) -->
    <?php if (!empty($upiId) || !empty($upiQr)): ?>
      <div class="bg-white rounded-3xl border border-border p-6 sm:p-8 shadow-soft-sm space-y-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent-strong flex items-center justify-center font-bold text-xs uppercase">
            UPI
          </div>
          <div>
            <h2 class="font-serif text-xl font-bold text-ink">UPI Payment</h2>
            <p class="text-xs text-ink-muted">Direct instant bank transfer via any UPI app</p>
          </div>
        </div>

        <?php if (!empty($upiQr)): ?>
          <div class="flex flex-col items-center justify-center p-4 bg-surface rounded-2xl border border-border">
            <img 
              src="<?= e($upiQr) ?>" 
              alt="Scan UPI QR Code to donate" 
              class="w-56 h-56 object-contain rounded-xl border border-border-subtle bg-white p-2 shadow-soft-sm"
              loading="eager"
            >
            <p class="text-xs text-ink-muted mt-2">Scan with GPay, PhonePe, Paytm, or BHIM</p>
          </div>
        <?php endif; ?>

        <?php if (!empty($upiId)): ?>
          <div class="space-y-1.5" x-data="{ copied: false }">
            <label class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">UPI ID / VPA</label>
            <div class="flex items-center justify-between p-3 bg-surface rounded-xl border border-border font-mono text-sm text-ink select-all">
              <span class="font-semibold break-all"><?= e($upiId) ?></span>
              <button 
                type="button" 
                @click="navigator.clipboard.writeText('<?= e(addslashes($upiId)) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                class="ml-3 shrink-0 px-3 py-1 bg-white hover:bg-surface border border-border text-xs font-semibold text-primary rounded-lg transition-colors"
                x-text="copied ? 'Copied!' : 'Copy'"
              >
                Copy
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Bank Account Details Card (Rendered only if bank details text is set) -->
    <?php if (!empty($bankDetails)): ?>
      <div class="bg-white rounded-3xl border border-border p-6 sm:p-8 shadow-soft-sm space-y-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5m-15 10.5V10.5M3 21h18"/></svg>
          </div>
          <div>
            <h2 class="font-serif text-xl font-bold text-ink">Bank Account Details</h2>
            <p class="text-xs text-ink-muted">Direct NEFT / RTGS / IMPS wire transfer</p>
          </div>
        </div>

        <div class="p-5 bg-surface rounded-2xl border border-border font-mono text-xs sm:text-sm text-ink leading-relaxed whitespace-pre-wrap select-all">
<?= e($bankDetails) ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

</div>

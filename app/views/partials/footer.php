<!-- NatConnect Foundation — Global Public Footer -->
<footer class="mt-auto bg-white border-t border-border-subtle">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8">
      
      <!-- Brand & Mission Column (Full Logo Only in Footer) -->
      <div class="lg:col-span-2 space-y-4">
        <a href="/" class="inline-flex items-center gap-3">
          <img 
            src="<?= asset('assets/img/logo.png') ?>" 
            alt="NatConnect Foundation" 
            class="h-16 w-auto object-contain"
          />
        </a>
        
        <p class="text-sm text-ink/80 leading-relaxed max-w-md">
          [CLIENT COPY: mission statement and organizational description]
        </p>

        <div class="pt-1 text-sm text-ink/75">
          <span>[CLIENT COPY: official address and contact details]</span>
        </div>
      </div>

      <!-- Navigation Links (Minimum text-sm, ink/80 for WCAG AA contrast) -->
      <div>
        <h4 class="font-serif font-bold text-ink text-base mb-4">Navigation</h4>
        <ul class="space-y-3 text-sm text-ink/80">
          <li><a href="/" class="hover:text-primary transition-colors">Home</a></li>
          <li><a href="/about" class="hover:text-primary transition-colors">About</a></li>
          <li><a href="/category/causes" class="hover:text-primary transition-colors">Causes</a></li>
          <li><a href="/category/news" class="hover:text-primary transition-colors">News</a></li>
          <li><a href="/category/campaigns" class="hover:text-primary transition-colors">Campaigns</a></li>
          <li><a href="/category/rti-applications" class="hover:text-primary transition-colors">RTI</a></li>
          <li><a href="/gallery" class="hover:text-primary transition-colors">Gallery</a></li>
        </ul>
      </div>

      <!-- Additional Links & Support -->
      <div>
        <h4 class="font-serif font-bold text-ink text-base mb-4">Information & Support</h4>
        <ul class="space-y-3 text-sm text-ink/80">
          <li><a href="/contact" class="hover:text-primary transition-colors">Contact</a></li>
          <li><a href="/privacy" class="hover:text-primary transition-colors">Privacy Policy</a></li>
          <li>
            <a href="/donate" class="inline-flex items-center gap-1.5 text-accent font-semibold hover:text-accent-700 transition-colors">
              <!-- Lucide Heart, stroke-width 1.75 -->
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
              </svg>
              <span>Donate</span>
            </a>
          </li>
        </ul>
      </div>

    </div>

    <!-- Bottom Copyright Bar -->
    <div class="mt-12 pt-8 border-t border-border-subtle flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-ink/75">
      <p>&copy; <?= date('Y') ?> NatConnect Foundation. All rights reserved.</p>
      <p class="text-sm text-ink/70">[CLIENT COPY: legal / registration disclosures]</p>
    </div>

  </div>
</footer>

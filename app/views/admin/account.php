<!-- NatConnect Foundation — Admin Account & Security Settings -->
<div class="max-w-4xl space-y-8">
  
  <!-- Header Title -->
  <div>
    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink">Account</h1>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- 1. Your details Card -->
    <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm">
      <div class="flex items-center gap-3 border-b border-border-subtle pb-4 mb-6">
        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <h2 class="font-serif font-bold text-lg text-ink">Your details</h2>
        </div>
      </div>

      <form action="/admin/account/profile" method="POST" class="space-y-5">
        <?= \App\Core\Csrf::field() ?>

        <div>
          <label for="name" class="block text-xs font-semibold text-ink-soft mb-1.5">
            Full Name
          </label>
          <input 
            type="text" 
            id="name" 
            name="name" 
            value="<?= e($user['name'] ?? '') ?>" 
            required 
            maxlength="150"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
          />
        </div>

        <div>
          <label for="email" class="block text-xs font-semibold text-ink-soft mb-1.5">
            Email Address
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= e($user['email'] ?? '') ?>" 
            required 
            maxlength="191"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
          />
        </div>

        <div>
          <label for="profile_current_password" class="block text-xs font-semibold text-ink-soft mb-1.5">
            Current Password <span class="font-normal text-ink-muted">(Required only if changing email)</span>
          </label>
          <input 
            type="password" 
            id="profile_current_password" 
            name="current_password" 
            autocomplete="current-password"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
          />
        </div>

        <button 
          type="submit" 
          class="w-full py-2.5 px-4 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px] flex items-center justify-center gap-2"
        >
          <span>Save</span>
        </button>
      </form>
    </div>

    <!-- 2. Change password Card -->
    <div class="bg-white border border-border rounded-2xl p-6 sm:p-8 shadow-soft-sm">
      <div class="flex items-center gap-3 border-b border-border-subtle pb-4 mb-6">
        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div>
          <h2 class="font-serif font-bold text-lg text-ink">Change password</h2>
        </div>
      </div>

      <form action="/admin/account/password" method="POST" class="space-y-5">
        <?= \App\Core\Csrf::field() ?>

        <div>
          <label for="current_password" class="block text-xs font-semibold text-ink-soft mb-1.5">
            Current Password
          </label>
          <input 
            type="password" 
            id="current_password" 
            name="current_password" 
            required 
            autocomplete="current-password"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
          />
        </div>

        <div>
          <label for="new_password" class="block text-xs font-semibold text-ink-soft mb-1.5">
            New Password <span class="font-normal text-ink-muted">(Min 12 chars)</span>
          </label>
          <input 
            type="password" 
            id="new_password" 
            name="new_password" 
            minlength="12" 
            required 
            autocomplete="new-password"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
          />
        </div>

        <div>
          <label for="confirm_password" class="block text-xs font-semibold text-ink-soft mb-1.5">
            Confirm New Password
          </label>
          <input 
            type="password" 
            id="confirm_password" 
            name="confirm_password" 
            minlength="12" 
            required 
            autocomplete="new-password"
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all min-h-[44px]"
          />
        </div>

        <button 
          type="submit" 
          class="w-full py-2.5 px-4 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors min-h-[44px] flex items-center justify-center gap-2"
        >
          <span>Update password</span>
        </button>
      </form>
    </div>

  </div>

  <!-- Password Recovery Note -->
  <div class="p-4 sm:p-5 rounded-xl bg-surface border border-border-subtle text-xs text-ink-muted">
    Forgot your password? Contact your website developer.
  </div>

</div>

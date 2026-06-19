window.__cashierShared = () => ({
  cartOpen: false,
  isOffline: !navigator.onLine,
  isPWA: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
  showSyncSplash: false,
  syncProgress: 0,
  syncStatus: '',

  init() {
    if (!navigator.onLine && !this.isPWA) {
      window.location.href = '/network-error';
      return;
    }
    window.addEventListener('online', () => { this.isOffline = false; });
    window.addEventListener('offline', () => {
      this.isOffline = true;
      this.cartOpen = false;
      this.paymentModalOpen = false;
      if (!this.isPWA) { window.location.href = '/network-error'; return; }
      this.loadOfflineData();
    });
    if (this.isPWA && !navigator.onLine) { this.cartOpen = false; this.loadOfflineData(); }
    // Small delay ensures Livewire ($wire) is fully initialized
    if (this.isPWA) setTimeout(() => this.runStartupSync(), 300);
  },
});

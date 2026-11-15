/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');
  const overviewDateHeading = document.getElementById('overviewDateHeading');
  const kpiActiveBatchTime = document.getElementById('kpiActiveBatchTime');
  const logoutBtn = document.getElementById('logoutBtn') || document.querySelector('a[href*="Login.php"]');

  /* =========   light/dark image   ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

  /* =========== 1. Theme Initialization (Light Mode Default) ================ */
  try {
    const savedTheme = localStorage.getItem('majestic_theme');
    const isDark = savedTheme === 'dark';
    body.classList.toggle('dark-mode', isDark);
    syncThemeIcon(isDark);
  } catch (err) {
    body.classList.remove('dark-mode');
    syncThemeIcon(false);
  }

  /* =========== 2. Light / Dark Mode Toggle ================ */
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const isDark = body.classList.toggle('dark-mode');
      syncThemeIcon(isDark);
      try {
        localStorage.setItem('majestic_theme', isDark ? 'dark' : 'light');
      } catch (err) {
        // Ignore storage errors
      }
    });
  }

  /* =========== Sync Today's Date & Active Batch Window by Current Time ================ */
  const syncDateAndBatchWindow = () => {
    const now = new Date();

    // Update top date heading to Today's Date
    if (overviewDateHeading) {
      const formattedDate = now.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });
      overviewDateHeading.textContent = `Station Operation Overview - Today (${formattedDate})`;
    }

    // The active batch label now comes from the server
  };

  syncDateAndBatchWindow();
  setInterval(syncDateAndBatchWindow, 60000);

  /* =========== Auto-Refresh via Fetch API  =========== */
  const fetchLiveAdminDashboard = async () => {
    try {
      const response = await fetch('Admin_Dashboard.php?ajax_dashboard=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const data = await response.json();
      if (data.status !== 'ok') return;

    
      const kpiCards = document.querySelectorAll('.kpi-card');
      const kpiUpdates = [
        [data.kpi_active_batch, data.kpi_active_batch_sub],
        [data.kpi_pending_count, data.kpi_pending_sub],
        [data.kpi_cash_sales, data.kpi_cash_sub],
        [data.kpi_gallon_output, data.kpi_gallon_sub]
      ];
      kpiUpdates.forEach(([val, sub], idx) => {
        const card = kpiCards[idx];
        if (!card) return;
        const valEl = card.querySelector('.kpi-value');
        const subEl = card.querySelector('.kpi-sub');
        if (valEl && val) valEl.textContent = val;
        if (subEl && sub) subEl.textContent = sub;
      });


      const barItems = document.querySelectorAll('.batch-bar-item');
      if (data.batch_bars) {
        for (let i = 1; i <= 5; i++) {
          const barData = data.batch_bars[i];
          const itemEl = barItems[i - 1];
          if (!barData || !itemEl) continue;

          const statusEl = itemEl.querySelector('.batch-slot-status');
          const fillEl = itemEl.querySelector('.progress-fill');
          if (statusEl && barData.status_text) statusEl.textContent = barData.status_text;
          if (fillEl && typeof barData.fill_pct !== 'undefined') {
            fillEl.style.width = `${barData.fill_pct}%`;
          }
        }
      }
    } catch (error) {
      console.warn('Unable to refresh Admin Dashboard via Fetch API:', error);
    }
  };

  fetchLiveAdminDashboard();
  setInterval(fetchLiveAdminDashboard, 20000);

})();
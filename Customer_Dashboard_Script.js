/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');
  const welcomeGreetingHeading = document.getElementById('welcomeGreetingHeading');
  const custDashboardBanner = document.getElementById('custDashboardBanner');

  // Tracker Displays
  const trackerSubInfo = document.getElementById('trackerSubInfo');
  const trackerRiderAndTotal = document.getElementById('trackerRiderAndTotal');
  const trackerItemAndInspection = document.getElementById('trackerItemAndInspection');
  const stageBtns = [
    document.getElementById('stageBtn1'),
    document.getElementById('stageBtn2'),
    document.getElementById('stageBtn3'),
    document.getElementById('stageBtn4'),
    document.getElementById('stageBtn5')
  ];

  // Bottom Batch Slot Pills & 1-Click Repeat Order Controls
  const slotPills = [
    document.getElementById('slotPill1'),
    document.getElementById('slotPill2'),
    document.getElementById('slotPill3'),
    document.getElementById('slotPill4'),
    document.getElementById('slotPill5')
  ];
  const slotPillSubs = [
    document.getElementById('slotPillSub1'),
    document.getElementById('slotPillSub2'),
    document.getElementById('slotPillSub3'),
    document.getElementById('slotPillSub4'),
    document.getElementById('slotPillSub5')
  ];
  const repeatOrderForm = document.getElementById('repeatOrderForm');
  const repeatSlotIdInput = document.getElementById('repeatSlotIdInput');
  const repeatProductIdInput = document.getElementById('repeatProductIdInput');
  const repeatBtnLabelText = document.getElementById('repeatBtnLabelText');
  const btnScheduleWaterRefill = document.getElementById('btnScheduleWaterRefill');
  const btnOrderNewContainer = document.getElementById('btnOrderNewContainer');

  // Load records Data injected by db_connect5.php
  const dashData = window.MAJESTIC_CUSTOMER_DASHBOARD_DATA || {};
  const customer = dashData.customer || null;
  const activeOrder = dashData.active_order || null;
  const slotsList = Array.isArray(dashData.slots) ? dashData.slots : [];
  const usualRefill = dashData.usual_refill || {
    product_id: 'PRD-01',
    product_label: '5-Gal Slim',
    price: '30.00'
  };

  let selectedSlotId = Number(dashData.recommended_slot) || 1;

  /* =========   light/dark image ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

  /* =========   banner function  ========== */
  
  const showStatus = (message, type) => {
    if (!custDashboardBanner) return;
    custDashboardBanner.textContent = message;
    custDashboardBanner.className = `status-banner show ${type}`;
  };

  if (dashData.flash && dashData.flash.message) {
    showStatus(dashData.flash.message, dashData.flash.type || 'success');
  }

  /* =========== Theme Toggle ================ */
  try {
    const savedTheme = localStorage.getItem('majestic_theme');
    const isDark = savedTheme === 'dark';
    body.classList.toggle('dark-mode', isDark);
    syncThemeIcon(isDark);
  } catch (err) {
    body.classList.remove('dark-mode');
    syncThemeIcon(false);
  }

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const isDark = body.classList.toggle('dark-mode');
      syncThemeIcon(isDark);
      try {
        localStorage.setItem('majestic_theme', isDark ? 'dark' : 'light');
      } catch (err) {
              }
    });
  }

  /* =========== Populate Customer Welcome Heading ================ */
  if (customer && welcomeGreetingHeading) {
    const fullName = customer.full_name || 'Micael Masta';
    const label = customer.container_label || 'Masta';
    const brgyShort = customer.barangay_short || 'Brgy 27';
    welcomeGreetingHeading.textContent = `Welcome, ${fullName}! (Container Label: ${label} - ${brgyShort})`;
  }

  /* =========== Populate Active Order Live Tracker from MySQL ================ */
  if (activeOrder) {
    const cLabel = (customer && customer.container_label) ? customer.container_label : 'Masta';
    if (trackerSubInfo) {
      trackerSubInfo.textContent = `${activeOrder.slot_label} | Container Label: ${cLabel} (${activeOrder.order_id})`;
    }
    if (trackerRiderAndTotal) {
      const payNote = activeOrder.is_utang ? 'Unpaid / Utang' : 'Cash Only';
      trackerRiderAndTotal.textContent = `Assigned Rider: ${activeOrder.rider_name} | TOTAL: P${activeOrder.total_price} (${payNote})`;
    }
    if (trackerItemAndInspection) {
      let footerStr = `Order Item: ${activeOrder.item_summary} (P${activeOrder.total_price}) | Inspection: ${activeOrder.inspection_status}`;
      if (activeOrder.status_id === 6) {
        footerStr += ' | Status: Rescheduled';
      } else if (activeOrder.status_id === 7) {
        footerStr += ' | Status: Cancelled';
      }
      trackerItemAndInspection.textContent = footerStr;
    }

    const stageIdx = Math.max(0, Math.min(4, (Number(activeOrder.status_id) || 1) - 1));
    stageBtns.forEach((btn, idx) => {
      if (!btn) return;
      if (Number(activeOrder.status_id) === 7) {
        btn.classList.remove('active');
      } else {
        btn.classList.toggle('active', idx === stageIdx);
      }
    });
// Show Damaged Container or Delay/Hold Notice Banner
    const inspRaw = String(activeOrder.inspection_status || 'Good').trim();
    const delayRaw = String(activeOrder.delay_hold_notice || '').trim();
    const isDamagedInspection = /damaged|leak/i.test(inspRaw);

    if (isDamagedInspection) {
      showStatus(
        `Container Inspection Alert (${activeOrder.order_id}): Your container inspection turned up as "${inspRaw}". Please contact Majestic Spring via majesticspring@gmail.com or call/text 098767892 for further details (or order a brand-new P250.00 replacement container below).`,
        'error'
      );
    } else if (delayRaw && !/^none/i.test(delayRaw)) {
      showStatus(
        `Notice (${activeOrder.order_id}): ${delayRaw}. For inquiries, please contact Majestic Spring via majesticspring@gmail.com or 09876789267.`,
        'warning'
      );
    }


  } else if (customer) {
    if (trackerSubInfo) {
      trackerSubInfo.textContent = `No Active Order Queued Yet | Container Label: ${customer.container_label}`;
    }
    if (trackerRiderAndTotal) {
      trackerRiderAndTotal.textContent = 'Assigned Rider: Pending | TOTAL: P0.00 (Cash Only)';
    }
    if (trackerItemAndInspection) {
      trackerItemAndInspection.textContent =
        'Order Item: No water order scheduled yet — choose a batch slot or click "1-Click Repeat Usual Refill" below.';
    }
    
  }

  

  /* =========== Sync Today's 5 Batch Slot Pills & Repeat Form ================ */
  const syncSelectedSlotUI = () => {
    for (let i = 1; i <= 5; i++) {
      const slotInfo = slotsList.find((s) => Number(s.slot_id) === i);
      const pillEl = slotPills[i - 1];
      const subEl = slotPillSubs[i - 1];

      if (subEl && slotInfo) {
        subEl.textContent = slotInfo.pill_subtext;
      } else if (subEl && pillEl) {
        const spanFallback = pillEl.querySelector('span');
        if (spanFallback && slotInfo) spanFallback.textContent = slotInfo.pill_subtext;
      }

      if (pillEl) {
        pillEl.classList.toggle('active', i === selectedSlotId);
      }
    }

    if (repeatSlotIdInput) {
      repeatSlotIdInput.value = String(selectedSlotId);
    }
    if (btnScheduleWaterRefill) {
      btnScheduleWaterRefill.href = `Submit_Water_Order.php?slot=${selectedSlotId}`;
    }
    if (btnOrderNewContainer) {
      btnOrderNewContainer.href = `Order_New_Container.php?slot=${selectedSlotId}`;
    }
  };

  slotPills.forEach((pill, idx) => {
    if (!pill) return;
    pill.addEventListener('click', () => {
      selectedSlotId = idx + 1;
      syncSelectedSlotUI();
    });
  });

  if (repeatProductIdInput) {
    repeatProductIdInput.value = usualRefill.product_id || 'PRD-01';
  }
  if (repeatBtnLabelText) {
    repeatBtnLabelText.textContent = '';
    repeatBtnLabelText.append(
      '1-Click Repeat Usual',
      document.createElement('br'),
      `Refill (${usualRefill.product_label} | P${usualRefill.price})`
    );
  }

  if (repeatOrderForm) {
    repeatOrderForm.addEventListener('submit', (e) => {
      const slotObj = slotsList.find((s) => Number(s.slot_id) === selectedSlotId);
      const slotLabel = slotObj ? slotObj.slot_label : `Batch ${selectedSlotId}`;
      const ok = window.confirm(
        `Submit 1-Click Repeat Refill Order (1x ${usualRefill.product_label} Refill — P${usualRefill.price}) for Batch ${selectedSlotId} (${slotLabel})?`
      );
      if (!ok) {
        e.preventDefault();
      }
    });
  }

  syncSelectedSlotUI();

  /* ===========  Live Customer Dashboard Refresh via Fetch API ================ */
  const fetchLiveCustomerDashboard = async () => {
    try {
      const response = await fetch('Customer_Dashboard.php?ajax_dashboard=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const freshData = await response.json();

      // Update Today's 5 Batch Slot Pills
      if (Array.isArray(freshData.slots)) {
        freshData.slots.forEach((slot) => {
          const idx = Number(slot.slot_id) - 1;
          if (slotPillSubs[idx] && slot.pill_subtext) {
            slotPillSubs[idx].textContent = slot.pill_subtext;
          }
        });
      }

      // Update Active Order 5-Stage Tracker
      const ord = freshData.active_order;
      if (ord) {
        const cLabel = (freshData.customer && freshData.customer.container_label) ? freshData.customer.container_label : 'Customer';
        if (trackerSubInfo) {
          trackerSubInfo.textContent = `${ord.slot_label} | Container Label: ${cLabel} (${ord.order_id})`;
        }
        if (trackerRiderAndTotal) {
          const payNote = ord.is_utang ? 'Unpaid / Utang' : 'Cash Only';
          trackerRiderAndTotal.textContent = `Assigned Rider: ${ord.rider_name} | TOTAL: P${ord.total_price} (${payNote})`;
        }
        const stageIdx = Math.max(0, Math.min(4, (Number(ord.status_id) || 1) - 1));
        stageBtns.forEach((btn, idx) => {
          if (!btn) return;
          btn.classList.toggle('active', Number(ord.status_id) !== 7 && idx === stageIdx);
        });
      }
    } catch (error) {
      console.warn('Unable to refresh Customer Dashboard via Fetch API:', error);
    }
  };

  fetchLiveCustomerDashboard();
  setInterval(fetchLiveCustomerDashboard, 20000);
})();
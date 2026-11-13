/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');

  // Form & Inputs
  const submitRefillForm = document.getElementById('submitRefillForm');
  const submitStatusBanner = document.getElementById('submitStatusBanner');
  const selectedBatchSlotInput = document.getElementById('selectedBatchSlotInput');
  const batchSlotBtns = document.querySelectorAll('.batch-slot-btn');
  const slotCountTexts = [
    document.getElementById('slotCountText1'),
    document.getElementById('slotCountText2'),
    document.getElementById('slotCountText3'),
    document.getElementById('slotCountText4'),
    document.getElementById('slotCountText5')
  ];
  const refillContainerType = document.getElementById('refillContainerType');
  const refillQuantity = document.getElementById('refillQuantity');
  const refillContainerLabel = document.getElementById('refillContainerLabel');
  const refillBarangay = document.getElementById('refillBarangay');
  const refillAddress = document.getElementById('refillAddress');
  const refillCellphone = document.getElementById('refillCellphone');
  const refillBackupPhone = document.getElementById('refillBackupPhone');
  const refillGateNote = document.getElementById('refillGateNote');

  // Digital Receipt Elements
  const recSlot = document.getElementById('recSlot');
  const recBrgy = document.getElementById('recBrgy');
  const recCustomerName = document.getElementById('recCustomerName');
  const recLabel = document.getElementById('recLabel');
  const recContainerSelected = document.getElementById('recContainerSelected');
  const recRate = document.getElementById('recRate');
  const recQuantity = document.getElementById('recQuantity');
  const recTotalPayable = document.getElementById('recTotalPayable');
  const submitBtnText = document.getElementById('submitBtnText');
  const btnLoadPreset = document.getElementById('btnLoadPreset');
  const btnSavePreset = document.getElementById('btnSavePreset');

  // Ensure form posts to Submit_Water_Order.php
  if (submitRefillForm) {
    submitRefillForm.setAttribute('action', 'Submit_Water_Order.php');
  }

  // Load records injected by db_connect5.php
  const refillData = window.MAJESTIC_SUBMIT_REFILL_DATA || {};
  const customer = refillData.customer || null;
  const slotsList = Array.isArray(refillData.slots) ? refillData.slots : [];
  const dbPreset = refillData.db_preset || null;
  const REFILL_RATE = Number(refillData.refill_rate) || 30.0;

  let activeSlotId = Number(refillData.selected_slot) || 1;

  /* =========   light/dark images  ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

  /*=========   banner function  ========== */

  const showStatus = (message, type) => {
    if (!submitStatusBanner) return;
    submitStatusBanner.textContent = message;
    submitStatusBanner.className = `status-banner show ${type}`;
  };

  if (refillData.flash && refillData.flash.message) {
    showStatus(refillData.flash.message, refillData.flash.type || 'success');
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

  /* =========== Populate Live Batch Slot Counts & Active Selection ================ */
  const syncBatchSlotsUI = () => {
    for (let i = 1; i <= 5; i++) {
      const slotInfo = slotsList.find((s) => Number(s.slot_id) === i);
      if (slotCountTexts[i - 1] && slotInfo) {
        slotCountTexts[i - 1].textContent = slotInfo.pill_subtext;
      }
    }

    batchSlotBtns.forEach((btn) => {
      const sNum = Number(btn.getAttribute('data-slot')) || 1;
      const isSelected = sNum === activeSlotId;
      btn.classList.toggle('active', isSelected);

      if (isSelected) {
        const slotInfo = slotsList.find((s) => Number(s.slot_id) === sNum);
        const label = slotInfo
          ? slotInfo.slot_label
          : (btn.getAttribute('data-label') || '8:00 AM - 10:00 AM');
        if (selectedBatchSlotInput) selectedBatchSlotInput.value = String(sNum);
        if (recSlot) recSlot.textContent = label;
      }
    });
  };

  /* ===========Populate Customer Profile Defaults from MySQL ================ */
  if (customer) {
    if (recCustomerName) {
      recCustomerName.textContent = customer.full_name || 'Micael Masta';
    }
    if (refillContainerLabel && !refillContainerLabel.value) {
      refillContainerLabel.value = customer.container_label || '';
    }
    if (refillBarangay && customer.barangay_short) {
      const matchOpt = Array.from(refillBarangay.options).find(
        (o) => o.value === customer.barangay_short
      );
      if (matchOpt) {
        refillBarangay.value = customer.barangay_short;
      }
    }
    if (refillAddress && !refillAddress.value) {
      refillAddress.value = customer.address || '';
    }
    if (refillCellphone && !refillCellphone.value) {
      refillCellphone.value = customer.primary_phone || '';
    }
    if (refillBackupPhone && !refillBackupPhone.value) {
      refillBackupPhone.value = customer.backup_phone || '';
    }
    if (refillGateNote && !refillGateNote.value) {
      refillGateNote.value = customer.gate_rider_note || '';
    }
  }

  /* ===========  Update Live Digital Receipt ================ */
  const updateDigitalReceipt = () => {
    const qty = Math.max(1, parseInt(refillQuantity ? refillQuantity.value : '1', 10) || 1);
    const total = qty * REFILL_RATE;
    const cType = refillContainerType ? refillContainerType.value : '5-Gal Slim';
    const label = (refillContainerLabel && refillContainerLabel.value.trim())
      ? refillContainerLabel.value.trim()
      : (customer?.container_label || 'Masta');
    const brgy = refillBarangay ? refillBarangay.value : 'Brgy 22';

    if (recBrgy) recBrgy.textContent = brgy;
    if (recLabel) recLabel.textContent = label;
    if (recContainerSelected) recContainerSelected.textContent = `${qty}x ${cType} Refill`;
    if (recRate) recRate.textContent = `P${REFILL_RATE.toFixed(2)}`;
    if (recQuantity) recQuantity.textContent = String(qty);
    if (recTotalPayable) recTotalPayable.textContent = `P${total.toFixed(2)}`;

    if (submitBtnText) {
      submitBtnText.textContent = '';
      submitBtnText.append(
        'Submit Water Refill',
        document.createElement('br'),
        `Order (P${total.toFixed(2)})`
      );
    }
  };

  /* =========== Batch Slot Selection & Form Input Listeners ================ */
  batchSlotBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      activeSlotId = Number(btn.getAttribute('data-slot')) || 1;
      syncBatchSlotsUI();
      updateDigitalReceipt();
    });
  });

  if (refillContainerType) refillContainerType.addEventListener('change', updateDigitalReceipt);
  if (refillQuantity) refillQuantity.addEventListener('input', updateDigitalReceipt);
  if (refillContainerLabel) refillContainerLabel.addEventListener('input', updateDigitalReceipt);
  if (refillBarangay) refillBarangay.addEventListener('change', updateDigitalReceipt);

  /* =========== Save & Load Usual Refill Preset ================ */
  if (btnSavePreset) {
    btnSavePreset.addEventListener('click', () => {
      const preset = {
        slot_id: String(activeSlotId),
        slot_label: recSlot ? recSlot.textContent : '8:00 AM - 10:00 AM',
        container_type: refillContainerType ? refillContainerType.value : '5-Gal Slim',
        quantity: refillQuantity ? refillQuantity.value : '1',
        container_label: refillContainerLabel ? refillContainerLabel.value.trim() : '',
        barangay: refillBarangay ? refillBarangay.value : 'Brgy 22',
        address: refillAddress ? refillAddress.value.trim() : '',
        cellphone: refillCellphone ? refillCellphone.value.trim() : '',
        backup_phone: refillBackupPhone ? refillBackupPhone.value.trim() : '',
        gate_note: refillGateNote ? refillGateNote.value.trim() : ''
      };
      try {
        localStorage.setItem('majestic_refill_preset', JSON.stringify(preset));
        showStatus('Saved as your Usual Refill Preset!', 'success');
      } catch (err) {
        showStatus('Could not save preset to browser storage.', 'error');
      }
    });
  }

  if (btnLoadPreset) {
    btnLoadPreset.addEventListener('click', () => {
      let preset = null;
      try {
        const savedPreset = localStorage.getItem('majestic_refill_preset');
        if (savedPreset) {
          preset = JSON.parse(savedPreset);
        }
      } catch (err) {
        preset = null;
      }

      if (!preset && dbPreset) {
        preset = dbPreset;
      }

      if (!preset) {
        showStatus('No saved refill preset found yet — click "Save as My Usual Refill Preset" below to save one.', 'error');
        return;
      }

      if (refillContainerType) refillContainerType.value = preset.container_type || '5-Gal Slim';
      if (refillQuantity) refillQuantity.value = String(preset.quantity || 1);
      if (refillContainerLabel) refillContainerLabel.value = preset.container_label || '';
      if (refillBarangay && preset.barangay) refillBarangay.value = preset.barangay;
      if (refillAddress) refillAddress.value = preset.address || '';
      if (refillCellphone) refillCellphone.value = preset.cellphone || '';
      if (refillBackupPhone) refillBackupPhone.value = preset.backup_phone || '';
      if (refillGateNote) refillGateNote.value = preset.gate_note || '';

      activeSlotId = Number(preset.slot_id) || 1;
      syncBatchSlotsUI();
      updateDigitalReceipt();
      showStatus('Loaded your saved Refill Preset into the booking form.', 'success');
    });
  }

  /* =========== Validate & Submit Water Refill Order to MySQL ================ */
  if (submitRefillForm) {
    submitRefillForm.addEventListener('submit', (e) => {
      const label = refillContainerLabel ? refillContainerLabel.value.trim() : '';
      const address = refillAddress ? refillAddress.value.trim() : '';
      const cellphone = refillCellphone ? refillCellphone.value.trim() : '';
      const qty = Math.max(1, parseInt(refillQuantity ? refillQuantity.value : '1', 10) || 1);

      if (!label || !address || !cellphone) {
        e.preventDefault();
        showStatus('Incomplete Booking Form: Please fill out Container Label, Address, and Cellphone #.', 'error');
        return;
      }

      const slotObj = slotsList.find((s) => Number(s.slot_id) === activeSlotId);
      if (slotObj && qty > Number(slotObj.remaining_slots)) {
        e.preventDefault();
        showStatus(
          `Batch Capacity Exceeded: Batch ${activeSlotId} (${slotObj.slot_label}) only has ${slotObj.remaining_slots} container slot(s) left.`,
          'error'
        );
      }
    });
  }

  syncBatchSlotsUI();
  updateDigitalReceipt();

/* =========== Live Batch Slot Availability via Fetch API =========== */
  const fetchLiveBatchSlots = async () => {
    try {
      const response = await fetch('Submit_Water_Order.php?ajax_slots=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const data = await response.json();
      if (data.status === 'ok' && Array.isArray(data.slots)) {
        data.slots.forEach((slot) => {
          const spanEl = document.getElementById(`slotCountText${slot.slot_id}`);
          if (spanEl && slot.pill_subtext) {
            spanEl.textContent = slot.pill_subtext;
          }
        });
      }
    } catch (error) {
      console.warn('Unable to refresh batch slots via Fetch API:', error);
    }
  };

  // Run once on load and auto-refresh every 30 seconds
  fetchLiveBatchSlots();
  setInterval(fetchLiveBatchSlots, 30000);
})();
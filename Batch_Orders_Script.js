(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');

  // Top 5 Batch Slot Buttons & Counts
  const slotButtons = [1, 2, 3, 4, 5].map((n) => document.getElementById(`slotBtn${n}`));
  const slotCounts = [1, 2, 3, 4, 5].map((n) => document.getElementById(`slotCount${n}`));

  // Left Queue Panel Elements
  const activeBatchHeaderTitle = document.getElementById('activeBatchHeaderTitle');
  const batchSearchInput = document.getElementById('batchSearchInput');
  const searchBatchBtn = document.getElementById('searchBatchBtn');
  const batchCapacityDisplay = document.getElementById('batchCapacityDisplay');
  const slotStatusDisplay = document.getElementById('slotStatusDisplay');
  const bulkStatusSelect = document.getElementById('bulkStatusSelect');
  const batchTableBody = document.getElementById('batchTableBody');

  // Right Form Elements
  const batchOrderForm = document.getElementById('batchOrderForm');
  const batchStatusBanner = document.getElementById('batchStatusBanner');
  const orderStatusStage = document.getElementById('orderStatusStage');
  const orderServiceMode = document.getElementById('orderServiceMode');
  const orderIdBadge = document.getElementById('orderIdBadge');
  const customerIdBadge = document.getElementById('customerIdBadge');
  const orderCustomerName = document.getElementById('orderCustomerName');
  const orderAddress = document.getElementById('orderAddress');
  const orderBarangay = document.getElementById('orderBarangay');
  const orderCellphone = document.getElementById('orderCellphone');
  const orderBackupPhone = document.getElementById('orderBackupPhone');
  const orderContainerLabel = document.getElementById('orderContainerLabel');
  const orderSlotSelect = document.getElementById('orderSlotSelect');
  const orderRiderSelect = document.getElementById('orderRiderSelect');
  const orderInspectionSelect = document.getElementById('orderInspectionSelect');
  const orderProductSelect = document.getElementById('orderProductSelect');
  const orderQuantity = document.getElementById('orderQuantity');
  const orderTotalPriceText = document.getElementById('orderTotalPriceText');
  const orderManualTotalInput = document.getElementById('orderManualTotalInput');
  const orderDelayNoticeSelect = document.getElementById('orderDelayNoticeSelect');
  const orderAdditionalNotes = document.getElementById('orderAdditionalNotes');
  const clearCancelBtn = document.getElementById('clearCancelBtn');

  // Concise helper to ensure hidden form inputs exist
  const ensureHiddenInput = (id, name, defaultVal = '') => {
    let el = document.getElementById(id);
    if (!el && batchOrderForm) {
      el = document.createElement('input');
      el.type = 'hidden';
      el.id = id;
      el.name = name;
      el.value = defaultVal;
      batchOrderForm.appendChild(el);
    }
    return el;
  };

  if (batchOrderForm) batchOrderForm.setAttribute('action', 'Batch_Orders.php');
  const batchFormAction = ensureHiddenInput('batchFormAction', 'form_action', 'save');
  const bulkStatusValInput = ensureHiddenInput('bulkStatusValInput', 'bulk_status_val');
  const bulkSlotIdInput = ensureHiddenInput('bulkSlotIdInput', 'bulk_slot_id');
  const newBatchCapacityInput = ensureHiddenInput('newBatchCapacityInput', 'new_batch_capacity');
  const newSlotStatusInput = ensureHiddenInput('newSlotStatusInput', 'new_slot_status');

  // Load MySQL Data injected by admin_db4.php
  const batchData = window.MAJESTIC_BATCH_DATA || {};
  const slotsList = Array.isArray(batchData.slots) ? batchData.slots : [];
  const allOrders = Array.isArray(batchData.orders) ? batchData.orders : [];
  const employeesList = Array.isArray(batchData.employees) ? batchData.employees : [];
  const customersLookup = Array.isArray(batchData.customers) ? batchData.customers : [];
  const productsList = Array.isArray(batchData.products) ? batchData.products : [];

  const populateProductOptions = () => {
    if (!orderProductSelect || productsList.length === 0) return;
    const prevVal = orderProductSelect.value;
    orderProductSelect.textContent = '';

    productsList.forEach((p) => {
      const rPrice = Number(p.refill_price) || 0;
      const nPrice = Number(p.new_container_price) || 0;
      const labelBase = p.container_name || p.container_type || p.product_id;

      [
        { val: `REFILL|${p.product_id}`, text: `${labelBase} — Refill Only (P${rPrice.toFixed(2)})`, price: rPrice },
        { val: `NEW|${p.product_id}`, text: `${labelBase} — New / Replacement Container (P${nPrice.toFixed(2)})`, price: nPrice },
        { val: `NEW-REFILL|${p.product_id}`, text: `${labelBase} — New Container + Refill (P${(nPrice + rPrice).toFixed(2)})`, price: nPrice + rPrice }
      ].forEach((o) => {
        const opt = document.createElement('option');
        opt.value = o.val;
        opt.textContent = o.text;
        opt.dataset.price = String(o.price);
        orderProductSelect.appendChild(opt);
      });
    });

    const otherOpt = document.createElement('option');
    otherOpt.value = 'OTHER';
    otherOpt.textContent = 'Other (Manual Price)';
    otherOpt.dataset.price = '0';
    orderProductSelect.appendChild(otherOpt);

    if (prevVal && [...orderProductSelect.options].some((o) => o.value === prevVal)) {
      orderProductSelect.value = prevVal;
    }
  };

  let activeSlotId = Number(batchData.selected_slot) || 1;
  let selectedOrderId = null;

  /* =========   light and dark mode images  ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

  const showStatus = (message, type = 'success') => {
    if (!batchStatusBanner) return;
    batchStatusBanner.textContent = message;
    batchStatusBanner.className = `status-banner show ${type}`;
  };

  const isPriorityWalkInOrder = (ord) => {
    if (ord.is_priority_walkin) return true;
    return String(ord.service_mode || '').toLowerCase().includes('walk');
  };

  if (batchData.flash && batchData.flash.message) {
    showStatus(batchData.flash.message, batchData.flash.type || 'success');
  }

  /* =========== Theme Toggle ================ */
  try {
    const isDark = localStorage.getItem('majestic_theme') === 'dark';
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
        // Ignore storage errors
      }
    });
  }

  /* =========== Populate Delivery Rider Dropdown & Price Calculator ================ */
  if (orderRiderSelect && employeesList.length > 0) {
    orderRiderSelect.textContent = '';
    employeesList.forEach((emp) => {
      const opt = document.createElement('option');
      opt.value = emp.employee_id;
      opt.textContent = `${emp.employee_id} (${emp.first_name} — ${emp.emp_role})`;
      orderRiderSelect.appendChild(opt);
    });
  }

  const updateComputedPrice = () => {
    if (!orderProductSelect || !orderQuantity || !orderTotalPriceText) return;
    const prodVal = orderProductSelect.value;
    const qty = Math.max(1, Math.min(100, parseInt(orderQuantity.value, 10) || 1));

    if (prodVal === 'OTHER') {
      orderTotalPriceText.style.display = 'none';
      if (orderManualTotalInput) {
        orderManualTotalInput.style.display = 'block';
        orderManualTotalInput.required = true;
      }
      return;
    }

    orderTotalPriceText.style.display = '';
    if (orderManualTotalInput) {
      orderManualTotalInput.style.display = 'none';
      orderManualTotalInput.required = false;
    }

    const selectedOpt = orderProductSelect.options[orderProductSelect.selectedIndex];
    const unitRate = selectedOpt && selectedOpt.dataset.price ? Number(selectedOpt.dataset.price) : 30;
    orderTotalPriceText.textContent = `P${(unitRate * qty).toFixed(2)}`;
  };

  if (orderProductSelect) orderProductSelect.addEventListener('change', updateComputedPrice);
  if (orderQuantity) orderQuantity.addEventListener('input', updateComputedPrice);

  if (orderStatusStage && orderDelayNoticeSelect) {
    orderStatusStage.addEventListener('change', () => {
      if (orderStatusStage.value === '5_UNPAID') {
        orderDelayNoticeSelect.value = 'Unpaid / Utang';
      } else if (orderDelayNoticeSelect.value === 'Unpaid / Utang') {
        orderDelayNoticeSelect.value = 'None';
      }
    });
  }

  /* =========== Auto-Fill Customer Details ================ */
  const tryAutoFillCustomer = (value) => {
    const q = value.trim().toLowerCase();
    if (!q || (orderIdBadge && orderIdBadge.value !== 'ORD-#')) return;

    const match = customersLookup.find(
      (c) =>
        c.container_label.toLowerCase() === q ||
        c.customer_name.toLowerCase() === q ||
        c.customer_id.toLowerCase() === q
    );
    if (!match) return;

    if (customerIdBadge) customerIdBadge.value = match.customer_id;
    if (orderCustomerName && !orderCustomerName.value) orderCustomerName.value = match.customer_name;
    if (orderContainerLabel && !orderContainerLabel.value) orderContainerLabel.value = match.container_label;
    if (orderAddress && !orderAddress.value) orderAddress.value = match.address;
    if (orderBarangay) orderBarangay.value = match.barangay_num;
    if (orderCellphone && !orderCellphone.value) orderCellphone.value = match.primary_phone;
    if (orderBackupPhone && !orderBackupPhone.value) orderBackupPhone.value = match.backup_phone;
    if (orderAdditionalNotes && !orderAdditionalNotes.value && match.gate_rider_note) {
      orderAdditionalNotes.value = match.gate_rider_note;
    }
  };

  if (orderCustomerName) orderCustomerName.addEventListener('blur', () => tryAutoFillCustomer(orderCustomerName.value));
  if (orderContainerLabel) orderContainerLabel.addEventListener('blur', () => tryAutoFillCustomer(orderContainerLabel.value));

  /* =========== Form Reset & Row Population ================ */
  const clearTableSelection = () => {
    if (!batchTableBody) return;
    batchTableBody.querySelectorAll('tr.selected-row').forEach((r) => r.classList.remove('selected-row'));
    batchTableBody.querySelectorAll('.btn-table-edit.active').forEach((b) => b.classList.remove('active'));
  };

  const resetFormToNewMode = () => {
    selectedOrderId = null;
    if (batchOrderForm) batchOrderForm.reset();
    if (batchFormAction) batchFormAction.value = 'save';
    if (orderStatusStage) orderStatusStage.value = '1';
    if (orderServiceMode) orderServiceMode.value = 'Pickup/Delivery';
    if (orderIdBadge) orderIdBadge.value = 'ORD-#';
    if (customerIdBadge) customerIdBadge.value = 'CUS-#';
    if (orderBarangay) orderBarangay.value = '22';
    if (orderSlotSelect) orderSlotSelect.value = String(activeSlotId);
    if (orderInspectionSelect) orderInspectionSelect.value = 'Good';
    if (orderProductSelect && orderProductSelect.options.length > 0) orderProductSelect.selectedIndex = 0;
    if (orderQuantity) orderQuantity.value = '1';
    if (orderDelayNoticeSelect) orderDelayNoticeSelect.value = 'None';
    if (orderManualTotalInput) orderManualTotalInput.value = '';
    updateComputedPrice();
    clearTableSelection();
  };

  const populateFormForOrder = (ord) => {
    if (!ord) return;
    selectedOrderId = ord.order_id;
    if (batchFormAction) batchFormAction.value = 'save';
    if (orderStatusStage) orderStatusStage.value = ord.status_select_val || String(ord.status_id || 1);

    if (orderServiceMode) {
      if (ord.service_mode === 'Delivery' || ord.service_mode === 'Walk-In/Delivery') {
        orderServiceMode.value = 'Walk-In/Delivery';
      } else if (ord.service_mode.toLowerCase().includes('walk')) {
        orderServiceMode.value = 'Walk-In/Pickup';
      } else {
        orderServiceMode.value = 'Pickup/Delivery';
      }
    }

    if (orderIdBadge) orderIdBadge.value = ord.order_id;
    if (customerIdBadge) customerIdBadge.value = ord.customer_id;
    if (orderCustomerName) orderCustomerName.value = ord.customer_name || '';
    if (orderAddress) orderAddress.value = ord.address_only || '';
    if (orderBarangay) orderBarangay.value = ord.barangay_num || '22';
    if (orderCellphone) orderCellphone.value = ord.primary_phone || '';
    if (orderBackupPhone) orderBackupPhone.value = ord.backup_phone || '';
    if (orderContainerLabel) orderContainerLabel.value = ord.container_label || '';
    if (orderSlotSelect) orderSlotSelect.value = String(ord.slot_id || activeSlotId);
    if (orderRiderSelect && ord.employee_id) orderRiderSelect.value = ord.employee_id;
    if (orderInspectionSelect) orderInspectionSelect.value = ord.inspection_status || 'Good';
    if (orderProductSelect) orderProductSelect.value = ord.product_select || 'REFILL|PRD-01';
    if (orderQuantity) orderQuantity.value = String(ord.quantity || 1);
    if (orderTotalPriceText) orderTotalPriceText.textContent = `P${Number(ord.total_price).toFixed(2)}`;
    if (orderManualTotalInput) orderManualTotalInput.value = Number(ord.total_price).toFixed(2);
    updateComputedPrice();
    if (orderDelayNoticeSelect) orderDelayNoticeSelect.value = ord.delay_hold_notice || 'None';
    if (orderAdditionalNotes) orderAdditionalNotes.value = ord.additional_notes || '';
  };

  /* =========== 5. Sync Top 5 Batch Slot Buttons & Slot Settings ================ */
  const slotLabelsFallback = {
    1: '8:00 AM - 10:00 AM',
    2: '10:00 AM - 12:00 PM',
    3: '12:00 PM - 2:00 PM',
    4: '2:00 PM - 4:00 PM',
    5: '4:00 PM - 6:00 PM'
  };

  const syncSlotButtonsAndHeader = () => {
    for (let i = 1; i <= 5; i++) {
      const slotInfo = slotsList.find((s) => Number(s.slot_id) === i);
      const slotOrders = allOrders.filter((o) => Number(o.slot_id) === i && Number(o.status_id) !== 7);
      const activeQueuedOrders = slotOrders.filter((o) => Number(o.status_id) !== 5);
      const queuedContainers = activeQueuedOrders.reduce((sum, o) => sum + (Number(o.quantity) || 1), 0);
      const totalSlotContainers = slotOrders.reduce((sum, o) => sum + (Number(o.quantity) || 1), 0);

      const cap = slotInfo ? Number(slotInfo.batch_capacity) : 20;
      const slotStat = slotInfo ? (slotInfo.slot_status || 'Open') : 'Open';
      const statusText = slotStat === 'Closed'
        ? 'Closed'
        : (queuedContainers >= cap || slotStat === 'Full' ? 'Full' : 'Queued');

      if (slotCounts[i - 1]) {
        slotCounts[i - 1].textContent = `${queuedContainers}/${cap} Containers | ${statusText}`;
      }
      if (slotButtons[i - 1]) {
        slotButtons[i - 1].classList.toggle('active', i === activeSlotId);
      }

      if (i === activeSlotId) {
        const label = slotInfo ? slotInfo.slot_label : slotLabelsFallback[i];
        if (activeBatchHeaderTitle) {
          activeBatchHeaderTitle.textContent =
            `Active Batch Queue: ${label} (${queuedContainers}/${cap} Queued Containers | ${slotOrders.length} Order${slotOrders.length === 1 ? '' : 's'}, ${totalSlotContainers} Total Gal)`;
        }
        if (batchCapacityDisplay) batchCapacityDisplay.value = String(cap);
        if (slotStatusDisplay) slotStatusDisplay.value = queuedContainers >= cap && slotStat === 'Open' ? 'Full' : slotStat;
      }
    }
  };

  const submitSlotSettingsUpdate = () => {
    if (!batchOrderForm || !batchFormAction || !bulkSlotIdInput) return;
    batchFormAction.value = 'update_slot_settings';
    bulkSlotIdInput.value = String(activeSlotId);
    if (newBatchCapacityInput) {
      newBatchCapacityInput.value = String(Math.max(1, parseInt(batchCapacityDisplay?.value || '20', 10) || 20));
    }
    if (newSlotStatusInput) newSlotStatusInput.value = slotStatusDisplay ? slotStatusDisplay.value : 'Open';
    batchOrderForm.submit();
  };

  if (batchCapacityDisplay) {
    batchCapacityDisplay.addEventListener('change', () => {
      const val = Math.max(1, parseInt(batchCapacityDisplay.value, 10) || 20);
      if (window.confirm(`Update Batch ${activeSlotId} Capacity to ${val} containers?`)) {
        submitSlotSettingsUpdate();
      } else {
        syncSlotButtonsAndHeader();
      }
    });
  }

  if (slotStatusDisplay) {
    slotStatusDisplay.addEventListener('change', () => {
      if (window.confirm(`Change Batch ${activeSlotId} Slot Status to "${slotStatusDisplay.value}"?`)) {
        submitSlotSettingsUpdate();
      } else {
        syncSlotButtonsAndHeader();
      }
    });
  }

  /* =========== Render Active Batch Queue Table ================ */
  const statusOptions = [
    { id: '1', label: 'Queued' },
    { id: '2', label: 'Empty Picked Up' },
    { id: '3', label: 'Refilling' },
    { id: '4', label: 'Out For Delivery' },
    { id: '5', label: 'Delivered & Paid' },
    { id: '5_UNPAID', label: 'Delivered (Unpaid / Utang)' },
    { id: '6', label: 'Rescheduled' },
    { id: '7', label: 'Cancelled' }
  ];

  const renderBatchQueueTable = () => {
    if (!batchTableBody) return;
    batchTableBody.textContent = '';

    const query = batchSearchInput ? batchSearchInput.value.trim().toLowerCase() : '';
    const filtered = allOrders.filter((ord) => {
      if (Number(ord.slot_id) !== activeSlotId) return false;
      if (!query) return true;
      return [
        ord.order_id,
        ord.customer_id,
        ord.customer_name,
        ord.container_label,
        ord.primary_phone,
        ord.backup_phone,
        ord.gallon_price_cell,
        ord.brgy_address_cell,
        ord.service_mode,
        ord.status_name
      ].join(' ').toLowerCase().includes(query);
    });

    let overdueCountInSlot = 0;

    filtered.forEach((ord) => {
      const tr = document.createElement('tr');
      const isWalkIn = isPriorityWalkInOrder(ord);

      if (selectedOrderId === ord.order_id) tr.classList.add('selected-row');
      if (isWalkIn) tr.classList.add('priority-walkin-row');
      if (ord.requires_status_change) {
        tr.classList.add('overdue-status-row');
        overdueCountInSlot++;
      }

      const makeCell = (text, cls = '') => {
        const td = document.createElement('td');
        if (cls) td.className = cls;
        td.textContent = text;
        return td;
      };

      //  Order ID Cell (Priority Walk-In Badge if Walk-In)
      const tdId = document.createElement('td');
      const idStrong = document.createElement('div');
      idStrong.textContent = ord.order_id;
      tdId.appendChild(idStrong);

      if (isWalkIn) {
        const walkinBadge = document.createElement('div');
        walkinBadge.className = 'status-badge-note walkin-priority-note';
        walkinBadge.textContent = `★ PRIORITY (${ord.service_mode})`;
        tdId.appendChild(walkinBadge);
      }

      // Tag & Phone# Cell
      const tdTagPhone = document.createElement('td');
      tdTagPhone.className = 'tag-phone-cell';
      const lineTag = document.createElement('div');
      lineTag.textContent = `${ord.container_label} -`;
      const linePhone1 = document.createElement('div');
      linePhone1.textContent = ord.primary_phone;
      tdTagPhone.append(lineTag, linePhone1);

      if (ord.backup_phone && ord.backup_phone.trim() !== '') {
        const linePhone2 = document.createElement('div');
        linePhone2.className = 'backup-phone-line';
        linePhone2.textContent = `-${ord.backup_phone}`;
        tdTagPhone.appendChild(linePhone2);
      }

      // Order Status Cell
      const tdStatus = document.createElement('td');
      const statusSelect = document.createElement('select');
      statusSelect.className = 'table-status-select';
      const currentVal = ord.status_select_val || String(ord.status_id);

      statusOptions.forEach((st) => {
        const opt = document.createElement('option');
        opt.value = st.id;
        opt.textContent = st.label;
        opt.selected = currentVal === st.id;
        statusSelect.appendChild(opt);
      });

      statusSelect.addEventListener('change', () => {
        if (!batchOrderForm || !batchFormAction) return;
        populateFormForOrder(ord);
        if (orderStatusStage) orderStatusStage.value = statusSelect.value;
        batchFormAction.value = 'quick_status';
        batchOrderForm.submit();
      });
      tdStatus.appendChild(statusSelect);

      if (ord.is_rescheduled || Number(ord.status_id) === 6) {
        const reschedBadge = document.createElement('div');
        reschedBadge.className = 'status-badge-note overdue-note';
        reschedBadge.textContent = ord.is_carried_over
          ? `RESCHEDULED (From ${ord.date_ordered})`
          : 'RESCHEDULED (Carry-Over)';
        tdStatus.appendChild(reschedBadge);
      }
      if (ord.is_utang) {
        const utangBadge = document.createElement('div');
        utangBadge.className = 'status-badge-note utang-note';
        utangBadge.textContent = 'UTANG / UNPAID (Flagged)';
        tdStatus.appendChild(utangBadge);
      }
      if (ord.requires_status_change) {
        const overdueBadge = document.createElement('div');
        overdueBadge.className = 'status-badge-note overdue-note';
        overdueBadge.textContent = ord.is_carried_over
          ? `Carried Over (${ord.date_ordered}): Update Status`
          : 'Batch Time Passed: Update Status';
        tdStatus.appendChild(overdueBadge);
      }

      // 6. Action Cell
      const tdAction = document.createElement('td');
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-table-edit';
      if (selectedOrderId === ord.order_id) editBtn.classList.add('active');
      editBtn.textContent = 'Edit';

      editBtn.addEventListener('click', () => {
        if (selectedOrderId === ord.order_id) {
          resetFormToNewMode();
          showStatus('Order unselected — ready to encode a new batch order (ORD-#).', 'success');
          return;
        }
        clearTableSelection();
        tr.classList.add('selected-row');
        editBtn.classList.add('active');
        populateFormForOrder(ord);
        showStatus(`Editing ${ord.order_id} (${ord.container_label}). Click Edit again to unselect.`, 'success');
      });

      tdAction.appendChild(editBtn);
      tr.append(
        tdId,
        tdTagPhone,
        makeCell(ord.gallon_price_cell, 'gallon-price-cell'),
        makeCell(ord.brgy_address_cell, 'address-cell'),
        tdStatus,
        tdAction
      );
      batchTableBody.appendChild(tr);
    });

    const bannerText = batchStatusBanner ? batchStatusBanner.textContent : '';
    const bannerFree = !bannerText || bannerText.startsWith('Action Required');
    if (overdueCountInSlot > 0 && bannerFree && (!batchData.flash || !batchData.flash.message)) {
      showStatus(
        `Action Required: ${overdueCountInSlot} order(s) in this batch have passed their scheduled time window without being marked Delivered, Rescheduled, or Cancelled. Please update their status.`,
        'error'
      );
    }
  };

  /* =========== Bulk Status, Slot Switching, Search & Form Validation ================ */
  if (bulkStatusSelect) {
    bulkStatusSelect.addEventListener('change', () => {
      const val = bulkStatusSelect.value;
      if (!val) return;

      const ordersInBatch = allOrders.filter((o) => Number(o.slot_id) === activeSlotId);
      if (ordersInBatch.length === 0) {
        showStatus('There are no queued orders in this batch window to update.', 'error');
        bulkStatusSelect.value = '';
        return;
      }

      const selectedLabel = bulkStatusSelect.options[bulkStatusSelect.selectedIndex]?.textContent || val;
      if (
        window.confirm(`Change Order Status for all ${ordersInBatch.length} order(s) in Batch ${activeSlotId} to "${selectedLabel}"?`) &&
        batchOrderForm &&
        batchFormAction &&
        bulkStatusValInput &&
        bulkSlotIdInput
      ) {
        batchFormAction.value = 'bulk_status';
        bulkStatusValInput.value = val;
        bulkSlotIdInput.value = String(activeSlotId);
        batchOrderForm.submit();
      } else {
        bulkStatusSelect.value = '';
      }
    });
  }

  slotButtons.forEach((btn, idx) => {
    if (!btn) return;
    btn.addEventListener('click', () => {
      activeSlotId = idx + 1;
      if (!selectedOrderId && orderSlotSelect) orderSlotSelect.value = String(activeSlotId);
      if (bulkStatusSelect) bulkStatusSelect.value = '';
      syncSlotButtonsAndHeader();
      renderBatchQueueTable();
    });
  });

  if (searchBatchBtn) searchBatchBtn.addEventListener('click', renderBatchQueueTable);
  if (batchSearchInput) batchSearchInput.addEventListener('input', renderBatchQueueTable);

  if (clearCancelBtn) {
    clearCancelBtn.addEventListener('click', () => {
      const currentOrdId = orderIdBadge ? orderIdBadge.value.trim() : 'ORD-#';
      if (currentOrdId !== '' && currentOrdId !== 'ORD-#' && selectedOrderId === currentOrdId) {
        if (window.confirm(`Cancel selected order ${currentOrdId} in the database? (Click Cancel to simply clear the form selection)`)) {
          if (batchOrderForm && batchFormAction) {
            batchFormAction.value = 'cancel';
            batchOrderForm.submit();
            return;
          }
        }
      }
      resetFormToNewMode();
      if (batchStatusBanner) {
        batchStatusBanner.textContent = '';
        batchStatusBanner.className = 'status-banner';
      }
    });
  }

  if (batchOrderForm) {
    batchOrderForm.addEventListener('submit', (e) => {
      if (
        batchFormAction &&
        ['quick_status', 'cancel', 'bulk_status', 'update_slot_settings'].includes(batchFormAction.value)
      ) {
        return;
      }

      const cName = orderCustomerName ? orderCustomerName.value.trim() : '';
      const addr = orderAddress ? orderAddress.value.trim() : '';
      const phone = orderCellphone ? recCellphoneValue() : '';

      function recCellphoneValue() {
        return orderCellphone ? orderCellphone.value.trim() : '';
      }

      if (!cName || !addr || !phone) {
        e.preventDefault();
        showStatus('Incomplete Form: Please fill out Customer Name, Address, and Cellphone #.', 'error');
      } else if (orderProductSelect && orderProductSelect.value === 'OTHER' && (!orderManualTotalInput || Number(orderManualTotalInput.value) <= 0)) {
        e.preventDefault();
        showStatus('Manual Price Required: Please enter a valid total price greater than P0.00 for Other orders.', 'error');
      }
    });
  }

  populateProductOptions();
  updateComputedPrice();
  if (orderSlotSelect) orderSlotSelect.value = String(activeSlotId);
  syncSlotButtonsAndHeader();
  renderBatchQueueTable();

  /* =========== Live Batch Queue Auto-Refresh via Fetch API =========== */
  const fetchLiveBatchQueue = async () => {
    try {
      const response = await fetch('Batch_Orders.php?ajax_batch=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const freshData = await response.json();
      if (freshData.status !== 'ok') return;

      if (Array.isArray(freshData.slots)) slotsList.splice(0, slotsList.length, ...freshData.slots);
      if (Array.isArray(freshData.orders)) allOrders.splice(0, allOrders.length, ...freshData.orders);
      if (Array.isArray(freshData.customers)) customersLookup.splice(0, customersLookup.length, ...freshData.customers);
      if (Array.isArray(freshData.products)) {
        productsList.splice(0, productsList.length, ...freshData.products);
        populateProductOptions();
        updateComputedPrice();
      }

      syncSlotButtonsAndHeader();
      renderBatchQueueTable();
    } catch (error) {
      console.warn('Unable to refresh Batch Queue via Fetch API:', error);
    }
  };

  setInterval(fetchLiveBatchQueue, 15000);
})();

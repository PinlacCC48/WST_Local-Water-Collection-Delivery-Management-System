/* =========   ELEMENT REFERENCES  ========== */

(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');

  // Left Panel Header, Filter Controls & Table
  const recordsLogHeaderTitle = document.getElementById('recordsLogHeaderTitle');
  const recordSearchInput = document.getElementById('recordSearchInput');
  const searchRecordBtn = document.getElementById('searchRecordBtn');
  const filterAllBtn = document.getElementById('filterAllBtn');
  const filterWalkinBtn = document.getElementById('filterWalkinBtn');
  const filterCallRiderBtn = document.getElementById('filterCallRiderBtn');
  const filterUnpaidBtn = document.getElementById('filterUnpaidBtn');
  const filterDateInput = document.getElementById('filterDateInput');
  const filterBatchInput = document.getElementById('filterBatchInput');
  const recordsTableBody = document.getElementById('recordsTableBody');

  // Bottom Cash Remittance Audit Summary Elements
  const auditWalkinCash = document.getElementById('auditWalkinCash');
  const auditWalkinSub = document.getElementById('auditWalkinSub');
  const auditRiderCash = document.getElementById('auditRiderCash');
  const auditRiderSub = document.getElementById('auditRiderSub');
  const auditTotalCash = document.getElementById('auditTotalCash');
  const auditTotalSub = document.getElementById('auditTotalSub');

  // Right Panel Form Elements
  const orderRecordForm = document.getElementById('orderRecordForm');
  const recordStatusBanner = document.getElementById('recordStatusBanner');
  const selectedDateFilterInput = document.getElementById('selectedDateFilterInput');
  const recOrderStatus = document.getElementById('recOrderStatus');
  const recServiceMode = document.getElementById('recServiceMode');
  const recOrderChannel = document.getElementById('recOrderChannel');
  const recOrderIdBadge = document.getElementById('recOrderIdBadge');
  const recCustomerIdBadge = document.getElementById('recCustomerIdBadge');
  const recProductOrder = document.getElementById('recProductOrder');
  const recQuantity = document.getElementById('recQuantity');
  const recOrderBatch = document.getElementById('recOrderBatch');
  const recCustomerName = document.getElementById('recCustomerName');
  const recAddress = document.getElementById('recAddress');
  const recBarangay = document.getElementById('recBarangay');
  const recCellphone = document.getElementById('recCellphone');
  const recBackupPhone = document.getElementById('recBackupPhone');
  const recContainerLabel = document.getElementById('recContainerLabel');
  const recCashStatus = document.getElementById('recCashStatus');
  const recTotalPaid = document.getElementById('recTotalPaid');
  const recDatePaid = document.getElementById('recDatePaid');
  const recRecordedBy = document.getElementById('recRecordedBy');
  const recPaymentNotes = document.getElementById('recPaymentNotes');
  const clearCancelRecordBtn = document.getElementById('clearCancelRecordBtn');
  let recordFormAction = document.getElementById('recordFormAction');

  if (orderRecordForm && !recordFormAction) {
    recordFormAction = document.createElement('input');
    recordFormAction.type = 'hidden';
    recordFormAction.name = 'form_action';
    recordFormAction.id = 'recordFormAction';
    recordFormAction.value = 'save';
    orderRecordForm.appendChild(recordFormAction);
  }

  // Load records injected by admin_db4.php
  const recordsData = window.MAJESTIC_RECORDS_DATA || {};
  const allRecords = Array.isArray(recordsData.records) ? recordsData.records : [];
  const customersLookup = Array.isArray(recordsData.customers) ? recordsData.customers : [];
  const todayIso = recordsData.today_iso || new Date().toISOString().slice(0, 10);
  const initialSelectedDate = recordsData.selected_date || todayIso;
  const currentAdminUser = recordsData.current_admin_user || 'EMP-0001 (Admin)';

  let activeTabFilter = 'ALL';
  let selectedRecordId = null;

  /* =========   light/dark mode images ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

/* =========   banner function  ========== */

  const showStatus = (message, type = 'success') => {
    if (!recordStatusBanner) return;
    recordStatusBanner.textContent = message;
    recordStatusBanner.className = `status-banner show ${type}`;
  };

  const formatIsoDateLabel = (isoDateStr) => {
    if (!isoDateStr) return 'All Dates';
    const parts = isoDateStr.split('-');
    if (parts.length !== 3) return isoDateStr;
    const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (Number.isNaN(d.getTime())) return isoDateStr;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  const isOnlinePortalChannel = (rec) => {
    const ch = (rec.order_channel || '').toLowerCase();
    return ch === 'online' || ch === 'online portal';
  };

  // Match any order completed/paid on the selected date OR ordered on the selected date
  const matchesSelectedDate = (rec, dateFilter) => {
    if (!dateFilter) return true;
    if (Number(rec.status_id) === 5 && rec.date_paid) {
      return rec.date_paid === dateFilter || rec.date_ordered === dateFilter;
    }
    return rec.date_ordered === dateFilter;
  };

  if (recordsData.flash && recordsData.flash.message) {
    showStatus(recordsData.flash.message, recordsData.flash.type || 'success');
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
      }
      window.dispatchEvent(new CustomEvent('majestic:records-updated'));
    });
  }

  /* =========== Date & Batch Filter Getters ================ */
  if (filterDateInput && !filterDateInput.value) {
    filterDateInput.value = initialSelectedDate;
  }
  if (selectedDateFilterInput && filterDateInput) {
    selectedDateFilterInput.value = filterDateInput.value;
  }

  const getRecordsForSelectedDateAndBatch = () => {
    const dateFilter = filterDateInput ? filterDateInput.value : '';
    const batchFilter = filterBatchInput ? filterBatchInput.value : 'ALL';
    return allRecords.filter((rec) => {
      if (!matchesSelectedDate(rec, dateFilter)) return false;
      if (batchFilter !== 'ALL' && rec.batch_filter_key !== batchFilter) return false;
      return true;
    });
  };

  const getRecordsForSelectedDateAllBatches = () => {
    const dateFilter = filterDateInput ? filterDateInput.value : '';
    return allRecords.filter((rec) => matchesSelectedDate(rec, dateFilter));
  };

  // Expose shared API for Order_Records_ChartsJS.js
  window.MajesticOrderRecords = {
    getDayRecords: getRecordsForSelectedDateAllBatches,
    getFilteredRecords: getRecordsForSelectedDateAndBatch,
    getSelectedDate: () => (filterDateInput ? filterDateInput.value : todayIso) || todayIso,
    formatIsoDateLabel,
    isOnlinePortalChannel,
    currentAdminUser,
    showStatus
  };

  /* =========== Dynamic Header, Tab Counters & Audit Cards ================ */
  const updateHeaderAndAuditCards = () => {
    const dateFilter = filterDateInput ? filterDateInput.value : '';
    if (recordsLogHeaderTitle) {
      recordsLogHeaderTitle.textContent = `Water Order Records & Cash Payment Log for ${formatIsoDateLabel(dateFilter)}`;
    }
    if (selectedDateFilterInput) {
      selectedDateFilterInput.value = dateFilter;
    }

    const dateBatchRecords = getRecordsForSelectedDateAndBatch();
    const totalCount = dateBatchRecords.length;
    const userAdminCount = dateBatchRecords.filter((r) => !isOnlinePortalChannel(r)).length;
    const onlinePortalCount = dateBatchRecords.filter((r) => isOnlinePortalChannel(r)).length;
    const unpaidCount = dateBatchRecords.filter((r) => r.payment_status === 'Unpaid' || r.is_utang).length;

    if (filterAllBtn) filterAllBtn.textContent = `All (${totalCount})`;
    if (filterWalkinBtn) {
      filterWalkinBtn.textContent = '';
      filterWalkinBtn.append('User/Admin', document.createElement('br'), `(${userAdminCount})`);
    }
    if (filterCallRiderBtn) {
      filterCallRiderBtn.textContent = '';
      filterCallRiderBtn.append('Online', document.createElement('br'), `Portal (${onlinePortalCount})`);
    }
    if (filterUnpaidBtn) {
      filterUnpaidBtn.textContent = '';
      filterUnpaidBtn.append('Unpaid', document.createElement('br'), `(${unpaidCount})`);
    }

    let walkinCashTotal = 0;
    let walkinPaidContainers = 0;
    let riderCashTotal = 0;
    let riderRemittedOrders = 0;
    let paidOrdersCount = 0;
    let unpaidOrdersCount = 0;

    dateBatchRecords.forEach((rec) => {
      if (Number(rec.status_id) === 7) return;
      const isPaid = (rec.payment_status === 'Paid' || rec.payment_status === 'Remitted') && !rec.is_utang;
      const amount = parseFloat(rec.total_price) || 0;
      const qty = parseInt(rec.quantity, 10) || 1;
      const isWalkin = rec.service_mode.toLowerCase().includes('walk');

      if (isPaid) {
        paidOrdersCount++;
        if (isWalkin) {
          walkinCashTotal += amount;
          walkinPaidContainers += qty;
        } else {
          riderCashTotal += amount;
          riderRemittedOrders++;
        }
      } else {
        unpaidOrdersCount++;
      }
    });

    const totalAudited = walkinCashTotal + riderCashTotal;
    if (auditWalkinCash) auditWalkinCash.textContent = `P${walkinCashTotal.toFixed(2)}`;
    if (auditWalkinSub) auditWalkinSub.textContent = `(${walkinPaidContainers}) Walk-In Container Paid`;
    if (auditRiderCash) auditRiderCash.textContent = `P${riderCashTotal.toFixed(2)}`;
    if (auditRiderSub) auditRiderSub.textContent = `${riderRemittedOrders} Delivery Order Remitted`;
    if (auditTotalCash) auditTotalCash.textContent = `P${totalAudited.toFixed(2)}`;
    if (auditTotalSub) auditTotalSub.textContent = `${paidOrdersCount} Paid | ${unpaidOrdersCount} Unpaid`;
  };

  /* =========== 4. Live Total Calculation from Order Type + Quantity ================ */
  const recalculateTotalFromTypeAndQty = () => {
    if (!recProductOrder || !recTotalPaid) return;
    const t = (recProductOrder.value || '').trim();
    if (!t) return;
    const qty = Math.max(1, parseInt(recQuantity ? recQuantity.value : '1', 10) || 1);
    const unit = /new/i.test(t) ? (/refill/i.test(t) ? 280 : 250) : 30;
    recTotalPaid.value = `P${(unit * qty).toFixed(2)}`;
  };

  if (recProductOrder) {
    recProductOrder.addEventListener('change', recalculateTotalFromTypeAndQty);
    recProductOrder.addEventListener('input', recalculateTotalFromTypeAndQty);
  }
  if (recQuantity) {
    recQuantity.addEventListener('input', recalculateTotalFromTypeAndQty);
    recQuantity.addEventListener('change', recalculateTotalFromTypeAndQty);
  }

  if (recOrderStatus && recCashStatus) {
    recOrderStatus.addEventListener('change', () => {
      if (recOrderStatus.value === '5_UNPAID') recCashStatus.value = 'Unpaid';
      else if (recOrderStatus.value === '5' && recCashStatus.value === 'Unpaid') recCashStatus.value = 'Paid';
      if (recRecordedBy) recRecordedBy.value = currentAdminUser;
    });

    recCashStatus.addEventListener('change', () => {
      if (recCashStatus.value === 'Unpaid' && recOrderStatus.value === '5') recOrderStatus.value = '5_UNPAID';
      else if ((recCashStatus.value === 'Paid' || recCashStatus.value === 'Remitted') && recOrderStatus.value === '5_UNPAID') {
        recOrderStatus.value = '5';
      }
      if (recRecordedBy) recRecordedBy.value = currentAdminUser;
    });
  }

  const tryAutoFillCustomer = (val) => {
    const q = val.trim().toLowerCase();
    if (!q || (recOrderIdBadge && recOrderIdBadge.value !== 'ORD-#')) return;

    const match = customersLookup.find(
      (c) =>
        c.container_label.toLowerCase() === q ||
        c.customer_name.toLowerCase() === q ||
        c.customer_id.toLowerCase() === q
    );
    if (!match) return;

    if (recCustomerIdBadge) recCustomerIdBadge.value = match.customer_id;
    if (recCustomerName && !recCustomerName.value) recCustomerName.value = match.customer_name;
    if (recContainerLabel && !recContainerLabel.value) recContainerLabel.value = match.container_label;
    if (recAddress && !recAddress.value) recAddress.value = match.address;
    if (recBarangay && !recBarangay.value) recBarangay.value = match.barangay_num;
    if (recCellphone && !recCellphone.value) recCellphone.value = match.primary_phone;
    if (recBackupPhone && !recBackupPhone.value) recBackupPhone.value = match.backup_phone;
  };

  if (recCustomerName) recCustomerName.addEventListener('blur', () => tryAutoFillCustomer(recCustomerName.value));
  if (recContainerLabel) recContainerLabel.addEventListener('blur', () => tryAutoFillCustomer(recContainerLabel.value));

  /* =========== Form Reset & Row Population ================ */
  const clearTableSelection = () => {
    if (!recordsTableBody) return;
    recordsTableBody.querySelectorAll('tr.selected-row').forEach((r) => r.classList.remove('selected-row'));
    recordsTableBody.querySelectorAll('.btn-table-edit.active').forEach((b) => b.classList.remove('active'));
  };

  const resetFormToNewMode = () => {
    selectedRecordId = null;
    if (orderRecordForm) orderRecordForm.reset();
    if (recordFormAction) recordFormAction.value = 'save';
    if (recOrderStatus) recOrderStatus.value = '5';
    if (recServiceMode) recServiceMode.value = 'Pickup/Delivery';
    if (recOrderChannel) recOrderChannel.value = 'User/Admin Encoded';
    if (recOrderIdBadge) recOrderIdBadge.value = 'ORD-#';
    if (recCustomerIdBadge) recCustomerIdBadge.value = 'CUS-#';
    if (recProductOrder) recProductOrder.value = '5-Gal - Slim Refill';
    if (recQuantity) recQuantity.value = '1';
    if (recOrderBatch) recOrderBatch.value = 'Batch 1: 8:00 AM - 10:00 AM';
    if (recCashStatus) recCashStatus.value = 'Paid';
    if (recTotalPaid) recTotalPaid.value = 'P30.00';
    if (recDatePaid) recDatePaid.value = (filterDateInput && filterDateInput.value) ? filterDateInput.value : todayIso;
    if (recRecordedBy) recRecordedBy.value = currentAdminUser;
    clearTableSelection();
  };

  const populateFormForRecord = (rec) => {
    if (!rec) return;
    selectedRecordId = rec.order_id;
    if (recordFormAction) recordFormAction.value = 'save';
    if (recOrderStatus) recOrderStatus.value = rec.status_select_val || String(rec.status_id || 5);

    if (recServiceMode) {
      if (rec.service_mode === 'Delivery' || rec.service_mode === 'Walk-In/Delivery') {
        recServiceMode.value = 'Walk-In/Delivery';
      } else if (rec.service_mode.toLowerCase().includes('walk')) {
        recServiceMode.value = 'Walk-In/Pickup';
      } else {
        recServiceMode.value = 'Pickup/Delivery';
      }
    }

    if (recOrderChannel) {
      recOrderChannel.value = isOnlinePortalChannel(rec) ? 'Online Portal' : 'User/Admin Encoded';
    }

    if (recOrderIdBadge) recOrderIdBadge.value = rec.order_id;
    if (recCustomerIdBadge) recCustomerIdBadge.value = rec.customer_id;

    if (recProductOrder) {
      const cleanType = rec.base_order_type || (rec.order_summary || '5-Gal - Slim Refill').replace(/^\d+x\s+/i, '');
      recProductOrder.value = cleanType;
    }
    if (recQuantity) {
      recQuantity.value = String(rec.quantity || 1);
    }
    if (recOrderBatch) {
      recOrderBatch.value = rec.slot_label || 'Batch 1: 8:00 AM - 10:00 AM';
    }

    if (recCustomerName) recCustomerName.value = rec.customer_name || '';
    if (recAddress) recAddress.value = rec.address_only || '';
    if (recBarangay) recBarangay.value = rec.barangay_num || '';
    if (recCellphone) recCellphone.value = rec.primary_phone || '';
    if (recBackupPhone) recBackupPhone.value = rec.backup_phone || '';
    if (recContainerLabel) recContainerLabel.value = rec.container_label || '';

    if (recCashStatus) {
      recCashStatus.value = (rec.is_utang || rec.payment_status === 'Unpaid')
        ? 'Unpaid'
        : (rec.payment_status === 'Remitted' ? 'Remitted' : 'Paid');
    }

    if (recTotalPaid) recTotalPaid.value = `P${Number(rec.total_price).toFixed(2)}`;
    if (recDatePaid) recDatePaid.value = rec.date_paid || rec.date_ordered || todayIso;
    if (recRecordedBy) recRecordedBy.value = rec.recorded_by || currentAdminUser;
    if (recPaymentNotes) recPaymentNotes.value = rec.payment_notes || '';
  };

  /* =========== Render Order Records Table ================ */
  const renderOrderRecordsTable = () => {
    if (!recordsTableBody) return;
    recordsTableBody.textContent = '';

    updateHeaderAndAuditCards();

    const query = recordSearchInput ? recordSearchInput.value.trim().toLowerCase() : '';
    const filtered = getRecordsForSelectedDateAndBatch().filter((rec) => {
      if (activeTabFilter === 'User/Admin Encoded' && isOnlinePortalChannel(rec)) return false;
      if (activeTabFilter === 'Online Portal' && !isOnlinePortalChannel(rec)) return false;
      if (activeTabFilter === 'Unpaid' && rec.payment_status !== 'Unpaid' && !rec.is_utang) return false;
      if (!query) return true;

      return [
        rec.order_id,
        rec.customer_tag_cell,
        rec.customer_name,
        rec.service_mode,
        rec.order_channel,
        rec.brgy_address_cell,
        rec.order_summary,
        rec.payment_status,
        rec.recorded_by,
        rec.is_utang ? 'utang unpaid' : ''
      ].join(' ').toLowerCase().includes(query);
    });

    if (filtered.length === 0) {
      const emptyTr = document.createElement('tr');
      const emptyTd = document.createElement('td');
      emptyTd.colSpan = 8;
      emptyTd.style.padding = '1.25rem';
      emptyTd.style.color = 'var(--text-muted)';
      const activeDateVal = filterDateInput ? filterDateInput.value : '';
      emptyTd.textContent = activeDateVal
        ? `No order records found for ${formatIsoDateLabel(activeDateVal)}.`
        : 'No order records match the current filter.';
      emptyTr.appendChild(emptyTd);
      recordsTableBody.appendChild(emptyTr);
    }

    filtered.forEach((rec) => {
      const tr = document.createElement('tr');
      if (selectedRecordId === rec.order_id) tr.classList.add('selected-row');
      if (rec.is_utang || rec.payment_status === 'Unpaid') tr.classList.add('unpaid-flagged-row');

      const makeCell = (text, cls = '') => {
        const td = document.createElement('td');
        if (cls) td.className = cls;
        td.textContent = text;
        return td;
      };

      const tdStatus = document.createElement('td');
      const statusMain = document.createElement('div');
      statusMain.className = 'status-pill-text';
      statusMain.textContent = rec.payment_status;
      tdStatus.appendChild(statusMain);

      if (rec.is_utang || rec.payment_status === 'Unpaid') {
        const utangBadge = document.createElement('div');
        utangBadge.className = 'utang-flag-badge';
        utangBadge.textContent = rec.is_utang ? 'UTANG / UNPAID' : 'UNPAID';
        tdStatus.appendChild(utangBadge);
      }

      const tdAction = document.createElement('td');
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-table-edit';
      if (selectedRecordId === rec.order_id) editBtn.classList.add('active');
      editBtn.textContent = 'Edit';

      editBtn.addEventListener('click', () => {
        if (selectedRecordId === rec.order_id) {
          resetFormToNewMode();
          showStatus('Record unselected — ready to encode a new order record (ORD-#).', 'success');
          return;
        }
        clearTableSelection();
        tr.classList.add('selected-row');
        editBtn.classList.add('active');
        populateFormForRecord(rec);
        showStatus(`Editing ${rec.order_id} (${rec.customer_tag_cell}). Click Edit again to unselect.`, 'success');
      });

      tdAction.appendChild(editBtn);
      tr.append(
        makeCell(rec.order_id),
        makeCell(rec.customer_tag_cell, 'cust-tag-cell'),
        makeCell(rec.service_mode),
        makeCell(rec.brgy_address_cell, 'address-cell'),
        makeCell(rec.order_summary, 'order-item-cell'),
        makeCell(`P${Number(rec.total_price).toFixed(2)}`, 'total-cell'),
        tdStatus,
        tdAction
      );
      recordsTableBody.appendChild(tr);
    });

    window.dispatchEvent(new CustomEvent('majestic:records-updated'));
  };

  /* =========== 7. Filter Tabs, Search, Date, Batch & Form Action Listeners ================ */
  const setTabFilter = (tabVal, btnEl) => {
    activeTabFilter = tabVal;
    [filterAllBtn, filterWalkinBtn, filterCallRiderBtn, filterUnpaidBtn].forEach((b) => {
      if (b) b.classList.toggle('active', b === btnEl);
    });
    renderOrderRecordsTable();
  };

  if (filterAllBtn) filterAllBtn.addEventListener('click', () => setTabFilter('ALL', filterAllBtn));
  if (filterWalkinBtn) filterWalkinBtn.addEventListener('click', () => setTabFilter('User/Admin Encoded', filterWalkinBtn));
  if (filterCallRiderBtn) filterCallRiderBtn.addEventListener('click', () => setTabFilter('Online Portal', filterCallRiderBtn));
  if (filterUnpaidBtn) filterUnpaidBtn.addEventListener('click', () => setTabFilter('Unpaid', filterUnpaidBtn));

  if (searchRecordBtn) searchRecordBtn.addEventListener('click', renderOrderRecordsTable);
  if (recordSearchInput) recordSearchInput.addEventListener('input', renderOrderRecordsTable);

  if (filterDateInput) {
    filterDateInput.addEventListener('change', () => {
      if (!selectedRecordId && recDatePaid) recDatePaid.value = filterDateInput.value || todayIso;
      renderOrderRecordsTable();
    });
  }
  if (filterBatchInput) filterBatchInput.addEventListener('change', renderOrderRecordsTable);

  if (clearCancelRecordBtn) {
    clearCancelRecordBtn.addEventListener('click', () => {
      const currentId = recOrderIdBadge ? recOrderIdBadge.value.trim() : 'ORD-#';
      if (currentId !== '' && currentId !== 'ORD-#' && selectedRecordId === currentId) {
        if (window.confirm(`Delete order record ${currentId} from the database? (Click Cancel to simply unselect and clear the form)`)) {
          if (orderRecordForm && recordFormAction) {
            recordFormAction.value = 'delete';
            orderRecordForm.submit();
            return;
          }
        }
      }
      resetFormToNewMode();
      if (recordStatusBanner) {
        recordStatusBanner.textContent = '';
        recordStatusBanner.className = 'status-banner';
      }
    });
  }

  if (orderRecordForm) {
    orderRecordForm.addEventListener('submit', (e) => {
      if (selectedDateFilterInput && filterDateInput) {
        selectedDateFilterInput.value = filterDateInput.value;
      }
      if (recordFormAction && recordFormAction.value === 'delete') return;

      const prodOrder = recProductOrder ? recProductOrder.value.trim() : '';
      const custName = recCustomerName ? recCustomerName.value.trim() : '';
      const addr = recAddress ? recAddress.value.trim() : '';
      const phone = recCellphone ? recCellphone.value.trim() : '';

      if (!prodOrder || !custName || !addr || !phone) {
        e.preventDefault();
        showStatus('Incomplete Form: Please fill out Order Type, Quantity, Customer Name, Address, and Cellphone #.', 'error');
      }
    });
  }

  if (recDatePaid && !recDatePaid.value) recDatePaid.value = initialSelectedDate;
  if (recRecordedBy && !recRecordedBy.value) recRecordedBy.value = currentAdminUser;
  renderOrderRecordsTable();


  /* =========== Live Order Records & Chart.js Auto-Refresh via Fetch API =========== */
  const fetchLiveOrderRecords = async () => {
    try {
      const response = await fetch('Order_Records.php?ajax_records=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const freshData = await response.json();
      if (freshData.status !== 'ok') return;

      if (Array.isArray(freshData.records)) {
        allRecords.splice(0, allRecords.length, ...freshData.records);
      }
      if (Array.isArray(freshData.customers)) {
        customersLookup.splice(0, customersLookup.length, ...freshData.customers);
      }

      renderOrderRecordsTable();
    } catch (error) {
      console.warn('Unable to refresh Order Records via Fetch API:', error);
    }
  };

  setInterval(fetchLiveOrderRecords, 15000);
})();
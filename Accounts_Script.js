/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');

  // Directory Filter & Table Elements
  const accountSearchInput = document.getElementById('accountSearchInput');
  const searchAccountBtn = document.getElementById('searchAccountBtn');
  const filterAllBtn = document.getElementById('filterAllBtn');
  const filterCustomersBtn = document.getElementById('filterCustomersBtn');
  const filterStaffBtn = document.getElementById('filterStaffBtn');
  const accountsTableBody = document.getElementById('accountsTableBody');

  // Right-Hand Form Elements
  const accountForm = document.getElementById('accountForm');
  const accountFormAction = document.getElementById('accountFormAction');
  const accountStatusBanner = document.getElementById('accountStatusBanner');
  const accRole = document.getElementById('accRole');
  const accIdBadge = document.getElementById('accIdBadge');
  const accFirstName = document.getElementById('accFirstName');
  const accLastName = document.getElementById('accLastName');
  const accNickname = document.getElementById('accNickname');
  const accHouseNo = document.getElementById('accHouseNo');
  const accStreet = document.getElementById('accStreet');
  const accBarangay = document.getElementById('accBarangay');
  const accEmail = document.getElementById('accEmail');
  const accPrimaryPhone = document.getElementById('accPrimaryPhone');
  const accBackupPhone = document.getElementById('accBackupPhone');
  const accGateNote = document.getElementById('accGateNote');
  const accStatus = document.getElementById('accStatus');
  const accPassword = document.getElementById('accPassword');
  const toggleAccPasswordBtn = document.getElementById('toggleAccPasswordBtn');
  const clearDeleteAccountBtn = document.getElementById('clearDeleteAccountBtn');

  // Accounts loaded from database via db_connect2.php
  const allAccounts = Array.isArray(window.MAJESTIC_ACCOUNTS_DATA)
    ? window.MAJESTIC_ACCOUNTS_DATA
    : [];

  let activeCategoryFilter = 'ALL';
  let selectedAccountId = null;

  /* =========   light/dark image  ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

  /* =========   banner function  ========== */

  const showStatus = (message, type) => {
    if (!accountStatusBanner) return;
    accountStatusBanner.textContent = message;
    accountStatusBanner.className = `status-banner show ${type}`;
  };

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

  /* ===========  Reset Form to Add New Account (ID-#) ================ */
  const resetFormToNewMode = () => {
    selectedAccountId = null;
    if (accountForm) accountForm.reset();
    if (accountFormAction) accountFormAction.value = 'save';
    if (accRole) accRole.value = 'Customer';
    if (accIdBadge) accIdBadge.value = 'ID-#';
    if (accFirstName) accFirstName.value = '';
    if (accLastName) accLastName.value = '';
    if (accNickname) {
      accNickname.value = '';
      accNickname.placeholder = '';
    }
    if (accHouseNo) accHouseNo.value = '';
    if (accStreet) accStreet.value = '';
    if (accBarangay) accBarangay.value = '';
    if (accEmail) accEmail.value = '';
    if (accPrimaryPhone) accPrimaryPhone.value = '';
    if (accBackupPhone) accBackupPhone.value = '';
    if (accGateNote) accGateNote.value = '';
    if (accStatus) accStatus.value = 'Active';
    if (accPassword) {
      accPassword.value = '';
      accPassword.placeholder = 'Enter or update password';
      }
    if (toggleAccPasswordBtn) {
      toggleAccPasswordBtn.textContent = 'Show';
    }

    // Removes highlight from all table rows and Edit buttons
    const rows = accountsTableBody ? accountsTableBody.querySelectorAll('tr') : [];
    rows.forEach((r) => r.classList.remove('selected-row'));
    const editBtns = accountsTableBody
      ? accountsTableBody.querySelectorAll('.btn-table-edit')
      : [];
    editBtns.forEach((b) => b.classList.remove('active'));
  };

  /* =========== Populates Form When Editing a Selected Row ================ */
  const populateFormForAccount = (acc) => {
    if (!acc) return;
    selectedAccountId = acc.account_id;

    if (accountFormAction) accountFormAction.value = 'save';
    if (accRole) accRole.value = acc.role || 'Customer';
    if (accIdBadge) accIdBadge.value = acc.account_id;
    if (accFirstName) accFirstName.value = acc.first_name || '';
    if (accLastName) accLastName.value = acc.last_name || '';
    if (accNickname) {
      accNickname.value = acc.category === 'Customer' ? (acc.container_label || '') : 'Staff';
    }
    if (accHouseNo) accHouseNo.value = acc.house_no || '';
    if (accStreet) accStreet.value = acc.street_name || '';
    if (accBarangay) accBarangay.value = acc.barangay || '';
    if (accEmail) accEmail.value = acc.email_address || '';
    if (accPrimaryPhone) accPrimaryPhone.value = acc.primary_phone || '';
    if (accBackupPhone) accBackupPhone.value = acc.backup_phone || '';
    if (accGateNote) accGateNote.value = acc.gate_rider_note || '';
    if (accStatus) accStatus.value = acc.status || 'Active';
    if (accPassword) {
      accPassword.value = acc.password || '';
      accPassword.placeholder = 'Enter or update password';
    }
  };

  /* =========== Render Accounts Directory Table ================ */
  const renderAccountsTable = () => {
    if (!accountsTableBody) return;
    accountsTableBody.textContent = '';

    const query = accountSearchInput ? accountSearchInput.value.trim().toLowerCase() : '';

    const filtered = allAccounts.filter((acc) => {
      if (activeCategoryFilter === 'Customer' && acc.category !== 'Customer') {
        return false;
      }
      if (activeCategoryFilter === 'Staff' && acc.category !== 'Staff') {
        return false;
      }
      if (!query) return true;

      const haystack = [
        acc.account_id,
        acc.full_name,
        acc.container_label,
        acc.role,
        acc.barangay,
        acc.address_display,
        acc.email_address,
        acc.primary_phone
      ]
        .join(' ')
        .toLowerCase();

      return haystack.includes(query);
    });

    filtered.forEach((acc) => {
      const tr = document.createElement('tr');
      if (selectedAccountId && selectedAccountId === acc.account_id) {
        tr.classList.add('selected-row');
      }

      // ID
      const tdId = document.createElement('td');
      tdId.textContent = acc.account_id;

      // Full Name
      const tdName = document.createElement('td');
      tdName.textContent = acc.full_name;

      // Role
      const tdRole = document.createElement('td');
      tdRole.textContent = acc.role;

      // Brgy
      const tdBrgy = document.createElement('td');
      tdBrgy.textContent = acc.barangay;

      // Address
      const tdAddress = document.createElement('td');
      tdAddress.className = 'address-cell';
      tdAddress.textContent = acc.address_display;

      // Status
      const tdStatus = document.createElement('td');
      tdStatus.className = 'status-cell';
      tdStatus.textContent = acc.status;

      // Edit Button and Toggle Highlight / Unselect Behavior
      const tdAction = document.createElement('td');
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-table-edit';
      if (selectedAccountId && selectedAccountId === acc.account_id) {
        editBtn.classList.add('active');
      }
      editBtn.textContent = 'Edit';

      editBtn.addEventListener('click', () => {
        // Clicking Edit again on the already-selected row unhighlights it and clears form
        if (selectedAccountId === acc.account_id) {
          resetFormToNewMode();
          showStatus('Row unselected — ready to add a new account record (ID-#).', 'success');
          return;
        }

        //highlights row and populate the right-hand form
        const allRows = accountsTableBody.querySelectorAll('tr');
        allRows.forEach((r) => r.classList.remove('selected-row'));
        const allEditBtns = accountsTableBody.querySelectorAll('.btn-table-edit');
        allEditBtns.forEach((b) => b.classList.remove('active'));

        tr.classList.add('selected-row');
        editBtn.classList.add('active');
        populateFormForAccount(acc);
        showStatus(`Editing account ${acc.account_id} (${acc.full_name}). Click Edit again to unselect.`, 'success');
      });

      tdAction.appendChild(editBtn);
      tr.append(tdId, tdName, tdRole, tdBrgy, tdAddress, tdStatus, tdAction);
      accountsTableBody.appendChild(tr);
    });
  };

  /* ===========  Filter Tabs & Search Controls ================ */
  const setCategoryFilter = (filterVal, activeBtn) => {
    activeCategoryFilter = filterVal;
    [filterAllBtn, filterCustomersBtn, filterStaffBtn].forEach((btn) => {
      if (btn) btn.classList.remove('active');
    });
    if (activeBtn) activeBtn.classList.add('active');
    renderAccountsTable();
  };

  if (filterAllBtn) {
    filterAllBtn.addEventListener('click', () => setCategoryFilter('ALL', filterAllBtn));
  }
  if (filterCustomersBtn) {
    filterCustomersBtn.addEventListener('click', () => setCategoryFilter('Customer', filterCustomersBtn));
  }
  if (filterStaffBtn) {
    filterStaffBtn.addEventListener('click', () => setCategoryFilter('Staff', filterStaffBtn));
  }

  if (searchAccountBtn) {
    searchAccountBtn.addEventListener('click', () => renderAccountsTable());
  }
  if (accountSearchInput) {
    accountSearchInput.addEventListener('input', () => renderAccountsTable());
  }

  /* ===========  Role Dropdown Change Handler ================ */
  if (accRole) {
    accRole.addEventListener('change', () => {
      if (accRole.value !== 'Customer' && accNickname && !accNickname.value) {
        accNickname.value = 'Staff';
      } else if (accRole.value === 'Customer' && accNickname && accNickname.value === 'Staff') {
        accNickname.value = '';
      }
    });
  }

  /* =========== Clear / Delete Button ================ */
  if (clearDeleteAccountBtn) {
    clearDeleteAccountBtn.addEventListener('click', () => {
      const currentId = accIdBadge ? accIdBadge.value.trim() : 'ID-#';

      // If an existing account row is selected, confirm and submit delete action
      if (currentId !== '' && currentId !== 'ID-#' && selectedAccountId === currentId) {
        const confirmed = window.confirm(
          `Are you sure you want to delete account ${currentId} from the database?`
        );
        if (confirmed && accountForm && accountFormAction) {
          accountFormAction.value = 'delete';
          accountForm.submit();
        }
        return;
      }

      // Otherwise clear the form fields
      resetFormToNewMode();
      if (accountStatusBanner) {
        accountStatusBanner.textContent = '';
        accountStatusBanner.className = 'status-banner';
      }
    });
  }

  /* =========== 8. Form Submit Validation ================ */
  if (accountForm) {
    accountForm.addEventListener('submit', (e) => {
      if (accountFormAction && accountFormAction.value === 'delete') {
        return;
      }

      const roleVal = accRole ? accRole.value : 'Customer';
      const firstVal = accFirstName ? accFirstName.value.trim() : '';
      const lastVal = accLastName ? accLastName.value.trim() : '';
      const nickVal = accNickname ? accNickname.value.trim() : '';
      const houseVal = accHouseNo ? accHouseNo.value.trim() : '';
      const streetVal = accStreet ? accStreet.value.trim() : '';
      const brgyVal = accBarangay ? accBarangay.value.trim() : '';
      const emailVal = accEmail ? accEmail.value.trim() : '';
      const phoneVal = accPrimaryPhone ? accPrimaryPhone.value.trim() : '';
      const passVal = accPassword ? accPassword.value.trim() : '';
      const isNew = !accIdBadge || accIdBadge.value === 'ID-#';

      if (
        !firstVal ||
        !lastVal ||
        (roleVal === 'Customer' && !nickVal) ||
        !houseVal ||
        !streetVal ||
        !brgyVal ||
        !emailVal ||
        !phoneVal
      ) {
        e.preventDefault();
        showStatus('Incomplete Form: Please fill out all required fields marked with (*).', 'error');
        return;
      }

      if (isNew && !passVal) {
        e.preventDefault();
        showStatus('Password Required: Please enter a password when adding a new account.', 'error');
      }
    });
  }

  /* =========== Show / Hide Password Toggle ================ */
  if (toggleAccPasswordBtn && accPassword) {
    toggleAccPasswordBtn.addEventListener('click', () => {
      const isHidden = accPassword.type === 'password';
      accPassword.type = isHidden ? 'text' : 'password';
      toggleAccPasswordBtn.textContent = isHidden ? 'Hide' : 'Show';
    });
  }
  // Initial table render of records
  renderAccountsTable();
})();
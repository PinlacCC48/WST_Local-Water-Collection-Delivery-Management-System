/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');

  // Left Catalog Controls & Table
  const productSearchInput = document.getElementById('productSearchInput');
  const searchProductBtn = document.getElementById('searchProductBtn');
  const filterAllProductsBtn = document.getElementById('filterAllProductsBtn');
  const productsTableBody = document.getElementById('productsTableBody');

  // Right Form Controls (Replacement Cost Removed)
  const productForm = document.getElementById('productForm');
  const productStatusBanner = document.getElementById('productStatusBanner');
  const prodContainerType = document.getElementById('prodContainerType');
  const prodIdBadge = document.getElementById('prodIdBadge');
  const prodContainerName = document.getElementById('prodContainerName');
  const prodCapacity = document.getElementById('prodCapacity');
  const prodRefillPrice = document.getElementById('prodRefillPrice');
  const prodNewPrice = document.getElementById('prodNewPrice');
  const prodAvailableStock = document.getElementById('prodAvailableStock');
  const prodInspectionNote = document.getElementById('prodInspectionNote');
  const prodAvailabilityStatus = document.getElementById('prodAvailabilityStatus');
  const clearDeleteProductBtn = document.getElementById('clearDeleteProductBtn');

  let productFormAction = document.getElementById('productFormAction');
  if (productForm) {
    productForm.setAttribute('action', 'Container_Products.php');
    if (!productFormAction) {
      productFormAction = document.createElement('input');
      productFormAction.type = 'hidden';
      productFormAction.name = 'form_action';
      productFormAction.id = 'productFormAction';
      productFormAction.value = 'save';
      productForm.appendChild(productFormAction);
    }
  }

  // Products loaded from records via admin_db3.php
  const allProducts = Array.isArray(window.MAJESTIC_PRODUCTS_DATA)
    ? window.MAJESTIC_PRODUCTS_DATA
    : [];

  if (filterAllProductsBtn) {
    filterAllProductsBtn.textContent = `All (${allProducts.length})`;
  }

  let selectedProductId = null;

  /* =========   light/dark image  ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

 /* =========   banner function  ========== */
  const showStatus = (message, type) => {
    if (!productStatusBanner) return;
    productStatusBanner.textContent = message;
    productStatusBanner.className = `status-banner show ${type}`;
  };

  if (
    window.MAJESTIC_PRODUCTS_FLASH &&
    window.MAJESTIC_PRODUCTS_FLASH.message
  ) {
    showStatus(
      window.MAJESTIC_PRODUCTS_FLASH.message,
      window.MAJESTIC_PRODUCTS_FLASH.type || 'success'
    );
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

  /* =========== Reset Form to Add New Product Mode ================ */
  const resetFormToNewMode = () => {
    selectedProductId = null;
    if (productForm) productForm.reset();
    if (productFormAction) productFormAction.value = 'save';
    if (prodContainerType) prodContainerType.value = '';
    if (prodIdBadge) prodIdBadge.value = 'ID-#';
    if (prodContainerName) prodContainerName.value = '';
    if (prodCapacity) prodCapacity.value = '';
    if (prodRefillPrice) prodRefillPrice.value = '';
    if (prodNewPrice) prodNewPrice.value = '';
    if (prodAvailableStock) prodAvailableStock.value = '';
    if (prodInspectionNote) prodInspectionNote.value = '';
    if (prodAvailabilityStatus) prodAvailabilityStatus.value = 'Available';

    const rows = productsTableBody ? productsTableBody.querySelectorAll('tr') : [];
    rows.forEach((r) => r.classList.remove('selected-row'));
    const editBtns = productsTableBody
      ? productsTableBody.querySelectorAll('.btn-table-edit')
      : [];
    editBtns.forEach((b) => b.classList.remove('active'));
  };

  /* ===========  Populate Form When Editing a Selected Product ================ */
  const populateFormForProduct = (prod) => {
    if (!prod) return;
    selectedProductId = prod.product_id;

    if (productFormAction) productFormAction.value = 'save';
    if (prodContainerType) prodContainerType.value = prod.container_type || '';
    if (prodIdBadge) prodIdBadge.value = prod.product_id;
    if (prodContainerName) prodContainerName.value = prod.container_name || '';
    if (prodCapacity) prodCapacity.value = prod.capacity_gallons ?? 5;
    if (prodRefillPrice) prodRefillPrice.value = prod.refill_price || '30.00';
    if (prodNewPrice) prodNewPrice.value = prod.new_container_price || '250.00';
    if (prodAvailableStock) prodAvailableStock.value = prod.available_stock ?? 20;
    if (prodInspectionNote) prodInspectionNote.value = prod.inspection_note || '';
    if (prodAvailabilityStatus) {
      prodAvailabilityStatus.value = prod.availability_status || 'Available';
    }
  };

  /* =========== Render Container Products Table ================ */
  const renderProductsTable = () => {
    if (!productsTableBody) return;
    productsTableBody.textContent = '';

    const query = productSearchInput ? productSearchInput.value.trim().toLowerCase() : '';

    const filtered = allProducts.filter((prod) => {
      if (!query) return true;
      const haystack = [
        prod.product_id,
        prod.container_name,
        prod.container_type,
        prod.refill_price,
        prod.new_container_price,
        prod.availability_status
      ]
        .join(' ')
        .toLowerCase();
      return haystack.includes(query);
    });

    filtered.forEach((prod) => {
      const tr = document.createElement('tr');
      if (selectedProductId && selectedProductId === prod.product_id) {
        tr.classList.add('selected-row');
      }

      const tdId = document.createElement('td');
      tdId.textContent = prod.product_id;

      const tdName = document.createElement('td');
      tdName.textContent = prod.container_name;

      const tdType = document.createElement('td');
      tdType.textContent = prod.container_type;

      const tdRefill = document.createElement('td');
      tdRefill.textContent = `P${Number(prod.refill_price).toFixed(2)}`;

      const tdNewPrice = document.createElement('td');
      tdNewPrice.textContent = `P${Number(prod.new_container_price).toFixed(2)}`;

      const tdStock = document.createElement('td');
      tdStock.textContent = `${prod.available_stock} (${prod.availability_status})`;

      const tdAction = document.createElement('td');
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-table-edit';
      if (selectedProductId && selectedProductId === prod.product_id) {
        editBtn.classList.add('active');
      }
      editBtn.textContent = 'Edit';

      editBtn.addEventListener('click', () => {
        if (selectedProductId === prod.product_id) {
          resetFormToNewMode();
          showStatus('Product unselected — ready to add a new container product (ID-#).', 'success');
          return;
        }

        const allRows = productsTableBody.querySelectorAll('tr');
        allRows.forEach((r) => r.classList.remove('selected-row'));
        const allBtns = productsTableBody.querySelectorAll('.btn-table-edit');
        allBtns.forEach((b) => b.classList.remove('active'));

        tr.classList.add('selected-row');
        editBtn.classList.add('active');
        populateFormForProduct(prod);
        showStatus(`Editing ${prod.product_id} (${prod.container_name}). Click Edit again to unselect.`, 'success');
      });

      tdAction.appendChild(editBtn);
      tr.append(tdId, tdName, tdType, tdRefill, tdNewPrice, tdStock, tdAction);
      productsTableBody.appendChild(tr);
    });
  };

  /* =========== Search & All Button Events ================ */
  if (searchProductBtn) {
    searchProductBtn.addEventListener('click', () => renderProductsTable());
  }
  if (productSearchInput) {
    productSearchInput.addEventListener('input', () => renderProductsTable());
  }
  if (filterAllProductsBtn) {
    filterAllProductsBtn.addEventListener('click', () => {
      if (productSearchInput) productSearchInput.value = '';
      renderProductsTable();
    });
  }

  /* =========== Clear / Delete Button ================ */
  if (clearDeleteProductBtn) {
    clearDeleteProductBtn.addEventListener('click', () => {
      const currentId = prodIdBadge ? prodIdBadge.value.trim() : 'ID-#';
      if (currentId !== '' && currentId !== 'ID-#' && selectedProductId === currentId) {
        const confirmed = window.confirm(
          `Are you sure you want to delete container product ${currentId} from the database?`
        );
        if (confirmed && productForm && productFormAction) {
          productFormAction.value = 'delete';
          productForm.submit();
        }
        return;
      }

      resetFormToNewMode();
      if (productStatusBanner) {
        productStatusBanner.textContent = '';
        productStatusBanner.className = 'status-banner';
      }
    });
  }

  /* =========== Form Submit Validation ================ */
  if (productForm) {
    productForm.addEventListener('submit', (e) => {
      if (productFormAction && productFormAction.value === 'delete') {
        return;
      }

      const typeVal = prodContainerType ? prodContainerType.value.trim() : '';
      const nameVal = prodContainerName ? prodContainerName.value.trim() : '';
      const capVal = prodCapacity ? prodCapacity.value.trim() : '';
      const refVal = prodRefillPrice ? prodRefillPrice.value.trim() : '';
      const newVal = prodNewPrice ? prodNewPrice.value.trim() : '';
      const stkVal = prodAvailableStock ? prodAvailableStock.value.trim() : '';

      if (!typeVal || !nameVal || !capVal || !refVal || !newVal || stkVal === '') {
        e.preventDefault();
        showStatus('Incomplete Form: Please fill out all required fields marked with (*).', 'error');
      }
    });
  }

  renderProductsTable();
})();
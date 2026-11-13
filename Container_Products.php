<!-- database connection -->
<?php require_once __DIR__ . '/admin_db3.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Container Products Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Container_Products_Style.css?v=3" />
  <script src="Container_Products_Script.js?v=3" defer></script>
</head>
<body>

  <!-- ======================Navigation Bar====================== -->
  <header class="top-navbar">
    <div class="brand-left">
      <button
        type="button"
        id="themeToggleBtn"
        class="theme-circle-btn"
      >
        <img
          id="themeToggleImg"
          src="Lightmode_Pic.png"
          alt="Light Mode Active"
          class="theme-icon-img"
          decoding="async"
        />
      </button>
      <div class="brand-titles">
        <h1>Majestic Spring Water Station</h1>
        <p>Admin/Staff Portal</p>
      </div>
    </div>

    <nav class="nav-right">
      <a href="Admin_Dashboard.php"    class="nav-pill-btn">Dashboard</a>
      <a href="Accounts.php"           class="nav-pill-btn">Accounts</a>
      <a href="Container_Products.php" class="nav-pill-btn active">Container Products</a>
      <a href="Batch_Orders.php"       class="nav-pill-btn">Batch Order</a>
      <a href="Order_Records.php"      class="nav-pill-btn">Order Records</a>
      <a
        href="Login.php?logout=1"
        id="logoutBtn"
        class="nav-pill-btn"
        onclick="return confirm('Are you sure you want to log out of your account?');"
      >Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="products-workspace">

    <!-- ======================Page Header & Divider====================== -->
    <section class="page-header-section">
      <div class="header-top-row">
        <div class="header-titles">
          <h2>Manage Station Container Products &amp; Pricing</h2>
          <p>Configure container types, service pricing, and new container price</p>
        </div>
        <a href="Admin_Dashboard.php" class="btn-back-dashboard">Back To Dashboard</a>
      </div>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Page Header & Divider====================== -->

    <!-- ======================Main Split Section====================== -->
    <section class="products-main-grid">

      <!--=========================Left Panel: Station Container Catalog & Service Directory=========================-->
      <div class="catalog-box">
        <div class="box-header">
          <h3>Station Container Catalog &amp; Service Directory</h3>
        </div>

        <div class="catalog-body">
          <!-- Filter & Search Controls -->
          <div class="filter-section">
            <label for="productSearchInput" class="filter-label">Filter:</label>
            <div class="filter-controls-row">
              <input
                type="text"
                id="productSearchInput"
                class="filter-search-input"
                placeholder="Search Product ID, container name, type, or status..."
              />
              <button type="button" id="searchProductBtn" class="btn-search">
                Search
              </button>
              <button type="button" id="filterAllProductsBtn" class="all-count-btn">
                All (<?php echo (int)$totalProductsCount; ?>)
              </button>
            </div>
          </div>

          <!-- Container Products Table (Populated dynamically from MySQL) -->
          <div class="table-scroll-box">
            <table class="products-table" id="productsTable">
              <thead>
                <tr>
                  <th>Prod ID</th>
                  <th>Container<br />Name</th>
                  <th>Size &amp; Type</th>
                  <th>Refill Price (Walk-<br />in/Delivery)</th>
                  <th>New<br />Container</th>
                  <th>Stock</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="productsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <!--=========================Left Panel: Station Container Catalog & Service Directory=========================-->

      <!--=========================Right Panel: Add / Edit Container Product Form=========================-->
      <div class="form-box">
        <div class="box-header">
          <h3>Add / Edit Container Product Form</h3>
        </div>

        <div class="form-body">
          <!-- Feedback Banner -->
          <div
            id="productStatusBanner"
            class="status-banner<?php echo $productStatusMessage !== '' ? ' show ' . htmlspecialchars($productStatusType) : ''; ?>"
            role="status"
          ><?php echo htmlspecialchars($productStatusMessage); ?></div>

          <form id="productForm" method="POST" action="Container_Products.php" novalidate>
            <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />
            <input type="hidden" name="form_action" id="productFormAction" value="save" />

            <!-- Top Row: Container Type & ID-# Box -->
            <div class="type-id-row">
              <div class="type-select-col">
                <label for="prodContainerType" class="form-label">Container Type:*</label>
                <select id="prodContainerType" name="container_type" class="form-control-select" required>
                  <option value="">Select Container Type...</option>
                  <option value="5-Gal - Slim">5-Gal - Slim</option>
                  <option value="5-Gal - Round">5-Gal - Round</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="id-badge-col">
                <input
                  type="text"
                  id="prodIdBadge"
                  name="product_id"
                  class="id-badge-input"
                  value="ID-#"
                  readonly
                />
              </div>
            </div>

            <!-- Row 1: Container Name & Capacity (Gallons) -->
            <div class="grid-2">
              <div class="form-group">
                <label for="prodContainerName" class="form-label">Container Name:*</label>
                <input
                  type="text"
                  id="prodContainerName"
                  name="container_name"
                  class="form-control-input"
                  required
                />
              </div>

              <div class="form-group">
                <label for="prodCapacity" class="form-label">Capacity (Gallons):*</label>
                <input
                  type="number"
                  id="prodCapacity"
                  name="capacity_gallons"
                  class="form-control-input"
                  min="1"
                  step="1"
                  required
                />
              </div>
            </div>

            <!-- Row 2: Refill Price & New Container Price -->
            <div class="grid-2">
              <div class="form-group">
                <label for="prodRefillPrice" class="form-label">Refill Price (Walk-In/Deliver):*</label>
                <input
                  type="number"
                  id="prodRefillPrice"
                  name="refill_price"
                  class="form-control-input"
                  min="0"
                  step="0.01"
                  required
                />
              </div>

              <div class="form-group">
                <label for="prodNewPrice" class="form-label">New Container Price (Buy New):*</label>
                <input
                  type="number"
                  id="prodNewPrice"
                  name="new_container_price"
                  class="form-control-input"
                  min="0"
                  step="0.01"
                  required
                />
              </div>
            </div>

            <!-- Row 3: Available Stocks (Units) & Availability Status (Replacement Cost Removed) -->
            <div class="grid-2">
              <div class="form-group">
                <label for="prodAvailableStock" class="form-label">Available Stocks (Units):*</label>
                <input
                  type="number"
                  id="prodAvailableStock"
                  name="available_stock"
                  class="form-control-input"
                  min="0"
                  step="1"
                  required
                />
              </div>

              <div class="form-group">
                <label for="prodAvailabilityStatus" class="form-label">Availability Status:*</label>
                <select
                  id="prodAvailabilityStatus"
                  name="availability_status"
                  class="form-control-select"
                  required
                >
                  <option value="Available">Available</option>
                  <option value="Out of Stock">Out of Stock</option>
                </select>
              </div>
            </div>

            <!-- Row 4: Container Inspection & Replacement Note -->
            <div class="form-group note-group">
              <label for="prodInspectionNote" class="form-label centered-label">
                Container Inspection &amp; Replacement Note:
              </label>
              <textarea
                id="prodInspectionNote"
                name="inspection_note"
                class="form-control-textarea"
              ></textarea>
            </div>

            <!-- Bottom Action Buttons -->
            <div class="form-action-row">
              <button type="submit" id="saveProductBtn" class="btn-save-product">
                Save Product Record
              </button>
              <button type="button" id="clearDeleteProductBtn" class="btn-clear-delete">
                Clear/Delete
              </button>
            </div>
          </form>
        </div>
      </div>
      <!--=========================Right Panel: Add / Edit Container Product Form=========================-->

    </section>
    <!-- ======================Main Split Section====================== -->

  </main>

</body>
</html>
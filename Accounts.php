<!-- database connection -->
<?php require_once __DIR__ . '/admin_db2.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accounts Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Accounts_Style.css" />
  <script>
    window.MAJESTIC_ACCOUNTS_DATA = <?php echo $accountsListJson; ?>;
  </script>
  <script src="Accounts_Script.js?v=2" defer></script>
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
      <a href="Accounts.php"           class="nav-pill-btn active">Accounts</a>
      <a href="Container_Products.php" class="nav-pill-btn">Container Products</a>
      <a href="Batch_Orders.php"       class="nav-pill-btn">Batch Order</a>
      <a href="Order_Records.php"      class="nav-pill-btn">Order Records</a>
        <a href="Login.php?logout=1" id="logoutBtn" class="nav-pill-btn" onclick="return confirm('Are you sure you want to log out of your account?');">Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="accounts-workspace">

    <!-- ======================Page Header & Divider====================== -->
    <section class="page-header-section">
      <div class="header-top-row">
        <div class="header-titles">
          <h2>Manage Customer &amp; Station Staff Accounts</h2>
          <p>Search, Add, Delete, Edit, or Deactivate Customer and Employee Accounts</p>
        </div>
        <a href="Admin_Dashboard.php" class="btn-back-dashboard">Back To Dashboard</a>
      </div>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Page Header & Divider====================== -->

    <!-- ======================Main Split Section====================== -->
    <section class="accounts-main-grid">

      <!--=========================Left Panel: Registered User & Staff Accounts Directory=========================-->
      <div class="directory-box">
        <div class="box-header">
          <h3>Registered User &amp; Staff Accounts Directory</h3>
        </div>

        <div class="directory-body">
          <!-- Filter & Search Controls -->
          <div class="filter-section">
            <label for="accountSearchInput" class="filter-label">Filter:</label>
            <div class="filter-controls-row">
              <input
                type="text"
                id="accountSearchInput"
                class="filter-search-input"
                placeholder="Search ID, name, role, barangay, or street..."
              />
              <button type="button" id="searchAccountBtn" class="btn-search">
                Search
              </button>
              <button type="button" id="filterAllBtn" class="role-tab-btn active" data-filter="ALL">
                All (<?php echo (int)$totalAccountsCount; ?>)
              </button>
              <button type="button" id="filterCustomersBtn" class="role-tab-btn" data-filter="Customer">
                Customers<br />(<?php echo (int)$customerAccountsCount; ?>)
              </button>
              <button type="button" id="filterStaffBtn" class="role-tab-btn" data-filter="Staff">
                Staff<br />(<?php echo (int)$staffAccountsCount; ?>)
              </button>
            </div>
          </div>

          <!-- Accounts Directory Table -->
          <div class="table-scroll-box">
            <table class="accounts-table" id="accountsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Full Name</th>
                  <th>Role</th>
                  <th>Brgy</th>
                  <th>Address</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="accountsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <!--=========================Left Panel: Registered User & Staff Accounts Directory=========================-->

      <!--=========================Right Panel: Add New/Edit Account Form=========================-->
      <div class="form-box">
        <div class="box-header">
          <h3>Add New/Edit Account Form</h3>
        </div>

        <div class="form-body">
          <!-- Feedback Banner -->
          <div
            id="accountStatusBanner"
            class="status-banner<?php echo $accountStatusMessage !== '' ? ' show ' . htmlspecialchars($accountStatusType) : ''; ?>"
            role="status"
          ><?php echo htmlspecialchars($accountStatusMessage); ?></div>

          <form id="accountForm" method="POST" action="Accounts.php" novalidate>
            <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />
            <input type="hidden" name="form_action" id="accountFormAction" value="save" />

            <!-- Top Row: Account Role & ID-# Box -->
            <div class="role-id-row">
              <div class="role-select-col">
                <label for="accRole" class="top-field-label">Account Role:</label>
                <select id="accRole" name="role" class="form-control-select">
                  <option value="Customer">Customer</option>
                  <option value="Admin">Admin</option>
                  <option value="Bookkeeper">Admin / Bookkeeper</option>
                  <option value="Delivery Rider">Staff / Delivery Rider</option>
                  </select>
              </div>

              <div class="id-badge-col">
                <input
                  type="text"
                  id="accIdBadge"
                  name="account_id"
                  class="id-badge-input"
                  value="ID-#"
                  readonly
                />
              </div>
            </div>

            <!-- Box 1: Customer Name & Gallon Tag -->
            <div class="inner-group-box">
              <h4 class="inner-group-title">1. Customer Name &amp; Gallon Tag</h4>
              <div class="grid-3">
                <div class="form-group">
                  <label for="accFirstName" class="form-label">First Name:*</label>
                  <input
                    type="text"
                    id="accFirstName"
                    name="first_name"
                    class="form-control-input"
                    required
                  />
                </div>

                <div class="form-group">
                  <label for="accLastName" class="form-label">Last Name:*</label>
                  <input
                    type="text"
                    id="accLastName"
                    name="last_name"
                    class="form-control-input"
                    required
                  />
                </div>

                <div class="form-group">
                  <label for="accNickname" class="form-label">Nickname (Gallon Tag):*</label>
                  <input
                    type="text"
                    id="accNickname"
                    name="container_label"
                    class="form-control-input"
                    required
                  />
                </div>
              </div>
            </div>

            <!-- Box 2: Home Pickup & Delivery Address -->
            <div class="inner-group-box">
              <h4 class="inner-group-title">2. Home Pickup &amp; Delivery Address</h4>
              <div class="grid-3">
                <div class="form-group">
                  <label for="accHouseNo" class="form-label">House/Unit No:*</label>
                  <input
                    type="text"
                    id="accHouseNo"
                    name="house_no"
                    class="form-control-input"
                    required
                  />
                </div>

                <div class="form-group">
                  <label for="accStreet" class="form-label">Street/Subdivision:*</label>
                  <input
                    type="text"
                    id="accStreet"
                    name="street_name"
                    class="form-control-input"
                    required
                  />
                </div>

                <div class="form-group">
                  <label for="accBarangay" class="form-label">Barangay:*</label>
                  <select id="accBarangay" name="barangay" class="form-control-select" required>
                    <option value="">Select...</option>
                    <option value="22">Brgy. 22</option>
                    <option value="24">Brgy. 24</option>
                    <option value="26">Brgy. 26</option>
                    <option value="27">Brgy. 27</option>
                    <option value="35">Brgy. 35</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Box 3: Contact Info -->
            <div class="inner-group-box">
              <div class="contact-grid">
                <div class="contact-email-col">
                  <h4 class="inner-group-title">3. Contact Info</h4>
                  <div class="form-group">
                    <label for="accEmail" class="form-label">Email Address:*</label>
                    <input
                      type="email"
                      id="accEmail"
                      name="email_address"
                      class="form-control-input"
                      required
                    />
                  </div>
                </div>

                <div class="contact-phones-col">
                  <div class="form-group">
                    <label for="accPrimaryPhone" class="form-label">Cellphone #:*</label>
                    <input
                      type="tel"
                      id="accPrimaryPhone"
                      name="primary_phone"
                      class="form-control-input compact-input"
                      maxlength="11"
                      minlength="11"
                      pattern="^09[0-9]{9}$"
                      inputmode="numeric"
                      placeholder="09XXXXXXXXX"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label for="accBackupPhone" class="form-label">Backup Phone #:</label>
                    <input
                      type="tel"
                      id="accBackupPhone"
                      name="backup_phone"
                      class="form-control-input compact-input"
                      maxlength="11"
                      minlength="11"
                      pattern="^09[0-9]{9}$"
                      inputmode="numeric"
                      placeholder="09XXXXXXXXX"
                    />
                  </div>
                </div>

                <div class="contact-note-col">
                  <div class="form-group full-height-group">
                    <label for="accGateNote" class="form-label">Gate/Rider Note:</label>
                    <textarea
                      id="accGateNote"
                      name="gate_rider_note"
                      class="form-control-textarea"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Status & Password Row -->
            <div class="status-password-row">
              <div class="status-col">
                <label for="accStatus" class="form-label">Status:*</label>
                <select id="accStatus" name="status" class="form-control-select status-pill-select">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>

              <div class="password-col">
                <label for="accPassword" class="form-label">Password:*</label>
                <div class="password-input-wrapper">
                  <input
                    type="password"
                    id="accPassword"
                    name="password"
                    class="form-control-input"
                    placeholder="Enter or update password"
                  />
                  <button type="button" id="toggleAccPasswordBtn" class="password-toggle-btn">
                    Show
                  </button>
                </div>
              </div>
            </div>

            <!-- Buttons -->
            <div class="form-action-row">
              <button type="submit" id="saveAccountBtn" class="btn-save-account">
                Save Account Record
              </button>
              <button type="button" id="clearDeleteAccountBtn" class="btn-clear-delete">
                Clear/Delete
              </button>
            </div>
          </form>
        </div>
      </div>
      <!--=========================Right Panel: Add New/Edit Account Form=========================-->

    </section>
    <!-- ======================Main Split Section====================== -->

  </main>

</body>
</html>
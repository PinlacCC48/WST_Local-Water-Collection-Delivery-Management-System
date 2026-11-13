<!-- database connection -->
<?php require_once __DIR__ . '/admin_db4.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Records Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and  Scripts -->
  <link rel="stylesheet" href="Order_Records_Style.css?v=6" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
  <script src="Order_Records_Script.js?v=6" defer></script>
  <script src="Order_Records_ChartsJS.js?v=6" defer></script>
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
      <a href="Container_Products.php" class="nav-pill-btn">Container Products</a>
      <a href="Batch_Orders.php"       class="nav-pill-btn">Batch Order</a>
      <a href="Order_Records.php"      class="nav-pill-btn active">Order Records</a>
      <a
        href="Login.php?logout=1"
        class="nav-pill-btn"
        onclick="return confirm('Are you sure you want to log out of your account?');"
      >Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="records-workspace">

    <!-- ======================Page Header & Back Button====================== -->
    <section class="page-header-section">
      <div class="header-top-row">
        <div class="header-titles">
          <h2>Manage Order Records</h2>
          <p>View and configure customer and staff encoded orders, Cash Payments, and Daily Sales Charts</p>
        </div>
        <a href="Admin_Dashboard.php" class="btn-back-dashboard">
          Back To Dashboard
        </a>
      </div>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Page Header & Back Button====================== -->

    <!-- ======================Main Split Section====================== -->
    <section class="records-main-grid">

      <!--=========================Left Panel: Water Order Records & Cash Payment Log=========================-->
      <div class="records-log-box">
        <div class="box-header">
          <h3 id="recordsLogHeaderTitle">Water Order Records &amp; Cash Payment Log for Today</h3>
        </div>

        <div class="records-body">
          <!-- Filter Bar with Search, Status Tabs -->
          <div class="records-filter-bar">
            <div class="filter-left-col">
              <label for="recordSearchInput" class="filter-label">Filter:</label>
              <div class="search-tabs-row">
                <input
                  type="text"
                  id="recordSearchInput"
                  class="filter-search-input"
                  placeholder="Search Order ID, Customer, Tag, or Address..."
                />
                <button type="button" id="searchRecordBtn" class="btn-search">
                  Search
                </button>
                <button type="button" id="filterAllBtn" class="filter-tab-btn active" data-filter="ALL">
                  All (0)
                </button>
                <button type="button" id="filterWalkinBtn" class="filter-tab-btn" data-filter="User/Admin Encoded">
                  User/Admin<br />(0)
                </button>
                <button type="button" id="filterCallRiderBtn" class="filter-tab-btn" data-filter="Online Portal">
                  Online<br />Portal (0)
                </button>
                <button type="button" id="filterUnpaidBtn" class="filter-tab-btn" data-filter="Unpaid">
                  Unpaid<br />(0)
                </button>
              </div>
            </div>

            <div class="filter-right-col">
              <div class="compact-filter-stack">
                <label for="filterDateInput" class="compact-filter-label">Date</label>
                <input
                  type="date"
                  id="filterDateInput"
                  class="compact-filter-input"
                  title="Select a date to view its order records and daily sales chart"
                />
              </div>

              <div class="compact-filter-stack">
                <label for="filterBatchInput" class="compact-filter-label">Batch</label>
                <select id="filterBatchInput" class="compact-filter-input">
                  <option value="ALL">All Batches</option>
                  <option value="Batch 1">8:00 AM - 10:00 AM</option>
                  <option value="Batch 2">10:00 AM - 12:00 PM</option>
                  <option value="Batch 3">12:00 PM - 2:00 PM</option>
                  <option value="Batch 4">2:00 PM - 4:00 PM</option>
                  <option value="Batch 5">4:00 PM - 6:00 PM</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Records Table (Populated from database records) -->
          <div class="table-scroll-box">
            <table class="records-table" id="recordsTable">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer ID &amp; Tag</th>
                  <th>Service Mode</th>
                  <th>BRGY &amp; Address</th>
                  <th>Order</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="recordsTableBody"></tbody>
            </table>
          </div>

          <!-- Bottom: End-of-Day Cash Remittance Audit & Daily Sales Summary) -->
          <div class="audit-summary-section">
            <h4 class="audit-section-title">End-of-Day Cash Remittance Audit &amp; Daily Sales Summary (Cash Only)</h4>

            <div class="audit-cards-grid">
              <div class="audit-card">
                <span class="audit-card-label">Walk-In Cash Log</span>
                <strong class="audit-card-amount" id="auditWalkinCash">P0.00</strong>
                <span class="audit-card-sub" id="auditWalkinSub">(0) Walk-In Container Paid</span>
              </div>

              <div class="audit-card">
                <span class="audit-card-label">Rider Cash Remitted</span>
                <strong class="audit-card-amount" id="auditRiderCash">P0.00</strong>
                <span class="audit-card-sub" id="auditRiderSub">0 Delivery Order Remitted</span>
              </div>

              <div class="audit-card">
                <span class="audit-card-label">Total Audited Cash</span>
                <strong class="audit-card-amount" id="auditTotalCash">P0.00</strong>
                <span class="audit-card-sub" id="auditTotalSub">0 Paid | 0 Unpaid</span>
              </div>

              <!-- Daily Sales Chart Card  -->
              <div class="audit-card chart-card" id="dailySalesChartCard">
                <div class="chart-card-header">
                  <span class="audit-card-label">Daily Sales Chart</span>
                  <span class="chart-total-badge" id="chartDayTotalText">P0.00</span>
                </div>

                <div class="chart-canvas-wrap">
                  <canvas id="dailySalesChartCanvas" aria-label="Daily Sales by Batch Chart"></canvas>
                </div>
              </div>
            </div>

            <!-- Bottom Action Button (Single Centered Order Report Button Matching Wireframe) -->
            <div class="audit-action-buttons-row">
              <button type="button" id="btnGenerateDailyReport" class="btn-generate-report">
                Generate Daily Sale Report
              </button>
            </div>
          </div>
        </div>
      </div>
      <!--=========================Left Panel: Water Order Records & Cash Payment Log=========================-->

      <!--=========================Right Panel: Encode/Edit Order Record Form=========================-->
      <div class="form-box">
        <div class="box-header">
          <h3>Encode/Edit Order Record Form</h3>
        </div>

        <div class="form-body">
          <!-- Feedback Banner -->
          <div id="recordStatusBanner" class="status-banner" role="status"></div>

          <form id="orderRecordForm" method="POST" action="Order_Records.php" novalidate>
            <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />
            <input type="hidden" name="form_action" id="recordFormAction" value="save" />
            <input type="hidden" name="selected_date_filter" id="selectedDateFilterInput" value="" />

            <!-- Top Centered ID Badges Row (ORD-# and CUS-# Matching Wireframe) -->
            <div class="id-badges-top-row">
              <input
                type="text"
                id="recOrderIdBadge"
                name="order_id"
                class="id-badge-input"
                value="ORD-#"
                readonly
              />
              <input
                type="text"
                id="recCustomerIdBadge"
                name="customer_id"
                class="id-badge-input"
                value="CUS-#"
                readonly
              />
            </div>

            <!-- Top Controls 3x2 Grid: Status/Mode/Channel (Left) & Order Type/Quantity/Order Batch (Right) -->
            <div class="top-form-split-grid">
              <div class="left-dropdowns-stack">
                <div class="form-group compact-group">
                  <label for="recOrderStatus" class="form-label">Order Status</label>
                  <select id="recOrderStatus" name="status_id" class="form-control-select">
                    <option value="1">Queued</option>
                    <option value="2">Empty Picked Up</option>
                    <option value="3">Refilling</option>
                    <option value="4">Out For Delivery</option>
                    <option value="5" selected>Delivered &amp; Paid</option>
                    <option value="5_UNPAID">Delivered (Unpaid / Utang)</option>
                    <option value="6">Rescheduled</option>
                    <option value="7">Cancelled</option>
                  </select>
                </div>

                <div class="form-group compact-group">
                  <label for="recServiceMode" class="form-label">Service Mode:*</label>
                  <select id="recServiceMode" name="service_mode" class="form-control-select">
                    <option value="Pickup/Delivery">Pickup/Delivery</option>
                    <option value="Walk-In/Pickup">Walk-In/Pickup</option>
                    <option value="Walk-In/Delivery">Walk-In/Delivery</option>
                  </select>
                </div>

                <div class="form-group compact-group">
                  <label for="recOrderChannel" class="form-label">Order Channel:*</label>
                  <select id="recOrderChannel" name="order_channel" class="form-control-select">
                    <option value="User/Admin Encoded" selected>User/Admin Encoded</option>
                    <option value="Online Portal">Online Portal</option>
                  </select>
                </div>
              </div>

              <div class="right-badges-fields-stack">
                <div class="form-group compact-group">
                  <label for="recProductOrder" class="form-label">Order Type:*</label>
                  <select
                    id="recProductOrder"
                    name="product_order"
                    class="form-control-select"
                    required
                  >
                    <option value="5-Gal - Slim Refill">5-Gal - Slim Refill</option>
                    <option value="5-Gal - Round Refill">5-Gal - Round Refill</option>
                    <option value="New 5-Gal - Slim">New 5-Gal - Slim</option>
                    <option value="New 5-Gal - Round">New 5-Gal - Round</option>
                    <option value="New 5-Gal - Slim + Refill">New 5-Gal - Slim + Refill</option>
                    <option value="New 5-Gal - Round + Refill">New 5-Gal - Round + Refill</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="form-group compact-group">
                  <label for="recQuantity" class="form-label">Quantity:*</label>
                  <input
                    type="number"
                    id="recQuantity"
                    name="quantity"
                    min="1"
                    max="100"
                    value="1"
                    class="form-control-input"
                    required
                  />
                </div>

                <div class="form-group compact-group">
                  <label for="recOrderBatch" class="form-label">Order Batch:*</label>
                  <select
                    id="recOrderBatch"
                    name="batch_slot"
                    class="form-control-select"
                    required
                  >
                    <option value="Batch 1: 8:00 AM - 10:00 AM">Batch 1: 8:00 AM - 10:00 AM</option>
                    <option value="Batch 2: 10:00 AM - 12:00 PM">Batch 2: 10:00 AM - 12:00 PM</option>
                    <option value="Batch 3: 12:00 PM - 2:00 PM">Batch 3: 12:00 PM - 2:00 PM</option>
                    <option value="Batch 4: 2:00 PM - 4:00 PM">Batch 4: 2:00 PM - 4:00 PM</option>
                    <option value="Batch 5: 4:00 PM - 6:00 PM">Batch 5: 4:00 PM - 6:00 PM</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Customer Information Box (2-Column Layout Matching Wireframe) -->
            <div class="inner-section-box customer-info-box">
              <div class="box-inner-header">
                <span class="box-inner-title">Customer Information</span>
              </div>

              <div class="customer-two-col-grid">
                <div class="cust-col-left">
                  <div class="form-group compact-group">
                    <label for="recCustomerName" class="form-label">Customer Name:*</label>
                    <input
                      type="text"
                      id="recCustomerName"
                      name="customer_name"
                      class="form-control-input"
                      required
                    />
                  </div>

                  <div class="form-group compact-group">
                    <label for="recCellphone" class="form-label">Cellphone #:*</label>
                    <input
                      type="tel"
                      id="recCellphone"
                      name="cellphone"
                      class="form-control-input compact-phone-input"
                      required
                    />
                  </div>

                  <div class="form-group compact-group">
                    <label for="recBackupPhone" class="form-label">Backup Phone #:</label>
                    <input
                      type="tel"
                      id="recBackupPhone"
                      name="backup_phone"
                      class="form-control-input compact-phone-input"
                    />
                  </div>
                </div>

                <div class="cust-col-right">
                  <div class="form-group compact-group">
                    <label for="recAddress" class="form-label">Address:*</label>
                    <input
                      type="text"
                      id="recAddress"
                      name="address"
                      class="form-control-input"
                      required
                    />
                  </div>

                  <div class="form-group compact-group">
                    <label for="recBarangay" class="form-label">Barangay:*</label>
                    <input
                      type="text"
                      id="recBarangay"
                      name="barangay"
                      class="form-control-input"
                      required
                    />
                  </div>

                  <div class="form-group compact-group">
                    <label for="recContainerLabel" class="form-label">Container Label:</label>
                    <input
                      type="text"
                      id="recContainerLabel"
                      name="container_label"
                      class="form-control-input"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Cash Payment & Remittance Log Box -->
            <div class="inner-section-box payment-remittance-box">
              <div class="box-inner-header">
                <span class="box-inner-title">Cash Payment &amp; Remittance Log</span>
              </div>

              <div class="payment-remittance-grid">
                <div class="payment-fields-col">
                  <div class="payment-two-col">
                    <div class="form-group compact-group">
                      <label for="recCashStatus" class="form-label">Cash Status:</label>
                      <select id="recCashStatus" name="payment_status" class="form-control-select">
                        <option value="Paid">Paid</option>
                        <option value="Remitted">Remitted</option>
                        <option value="Unpaid">Unpaid (Utang)</option>
                      </select>
                    </div>

                    <div class="form-group compact-group">
                      <label for="recTotalPaid" class="form-label">Total:*</label>
                      <input
                        type="text"
                        id="recTotalPaid"
                        name="total_paid"
                        class="form-control-input"
                        placeholder="P30.00"
                        value="P30.00"
                        required
                      />
                    </div>
                  </div>

                  <div class="payment-two-col">
                    <div class="form-group compact-group">
                      <label for="recDatePaid" class="form-label">Date Paid:*</label>
                      <input
                        type="date"
                        id="recDatePaid"
                        name="date_paid"
                        class="form-control-input"
                      />
                    </div>

                    <div class="form-group compact-group">
                      <label for="recRecordedBy" class="form-label">Recorded By:*</label>
                      <input
                        type="text"
                        id="recRecordedBy"
                        name="recorded_by"
                        class="form-control-input"
                        placeholder="e.g. EMP-0001 (Test Admin)"
                      />
                    </div>
                  </div>
                </div>

                <div class="payment-notes-col">
                  <div class="form-group full-height-group">
                    <label for="recPaymentNotes" class="form-label">Additional Notes:</label>
                    <textarea
                      id="recPaymentNotes"
                      name="payment_notes"
                      class="form-control-textarea"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bottom  Buttons -->
            <div class="form-action-row">
              <button type="submit" id="saveOrderRecordBtn" class="btn-save-record">
                Save Order Record
              </button>
              <button type="button" id="clearCancelRecordBtn" class="btn-clear-cancel">
                Clear/Delete
              </button>
            </div>
          </form>
        </div>
      </div>
      <!--=========================Right Panel: Encode/Edit Order Record Form=========================-->

    </section>
    <!-- ======================Main Split Section====================== -->

  </main>

</body>
</html>
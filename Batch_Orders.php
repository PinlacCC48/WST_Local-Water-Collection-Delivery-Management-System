<!-- database connection -->
<?php require_once __DIR__ . '/admin_db4.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Batch Orders Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Batch_Orders_Style.css?v=4" />
  <script src="Batch_Orders_Script.js?v=5" defer></script>
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
      <a href="Batch_Orders.php"       class="nav-pill-btn active">Batch Order</a>
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

  <main class="batch-workspace">

    <!-- ======================Page Header & Divider====================== -->
    <section class="page-header-section">
      <div class="header-top-row">
        <div class="header-titles">
          <h2>Manage Batch Orders, Rider Dispatch &amp; Order Status</h2>
          <p>Select batch window to monitor capacity, assign delivery rider, update order status</p>
        </div>
        <a href="Admin_Dashboard.php" class="btn-back-dashboard">Back To Dashboard</a>
      </div>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Page Header & Divider====================== -->

    <!-- ======================Top 5 Batch Window Selection Buttons====================== -->
    <section class="batch-buttons-grid">
      <button type="button" class="batch-window-btn active" data-slot="1" id="slotBtn1">
        <strong class="slot-time-text">8:00 AM - 10:00 AM</strong>
        <span class="slot-count-text" id="slotCount1">0/20 Containers | Queued</span>
      </button>

      <button type="button" class="batch-window-btn" data-slot="2" id="slotBtn2">
        <strong class="slot-time-text">10:00 AM - 12:00 PM</strong>
        <span class="slot-count-text" id="slotCount2">0/20 Containers | Queued</span>
      </button>

      <button type="button" class="batch-window-btn" data-slot="3" id="slotBtn3">
        <strong class="slot-time-text">12:00 PM - 2:00 PM</strong>
        <span class="slot-count-text" id="slotCount3">0/20 Containers | Queued</span>
      </button>

      <button type="button" class="batch-window-btn" data-slot="4" id="slotBtn4">
        <strong class="slot-time-text">2:00 PM - 4:00 PM</strong>
        <span class="slot-count-text" id="slotCount4">0/20 Containers | Queued</span>
      </button>

      <button type="button" class="batch-window-btn" data-slot="5" id="slotBtn5">
        <strong class="slot-time-text">4:00 PM - 6:00 PM</strong>
        <span class="slot-count-text" id="slotCount5">0/20 Containers | Queued</span>
      </button>
    </section>
    <!-- ======================Top 5 Batch Window Selection Buttons====================== -->

    <!-- ======================Main Split Section====================== -->
    <section class="batch-main-grid">

      <!--=========================Left Panel: Active Batch Queue Directory=========================-->
      <div class="queue-directory-box">
        <div class="box-header">
          <h3 id="activeBatchHeaderTitle">Active Batch Queue: 08:00 AM - 10:00 AM (0/20 Container Queued)</h3>
        </div>

        <div class="queue-body">
          <!-- Filter, Search, Editable Batch Capacity, Editable Slot Status & Change Order Status for All -->
          <div class="filter-controls-bar">
            <div class="filter-input-col">
              <label for="batchSearchInput" class="filter-label">Filter:</label>
              <div class="search-input-wrapper">
                <input
                  type="text"
                  id="batchSearchInput"
                  class="filter-search-input"
                  placeholder="Search Order ID, Tag, Gallon, or Address..."
                />
                <button type="button" id="searchBatchBtn" class="btn-search">
                  Search
                </button>
              </div>
            </div>

            <div class="capacity-badge-col">
              <label for="batchCapacityDisplay" class="stat-badge-label">Batch Capacity:</label>
              <input
                type="number"
                id="batchCapacityDisplay"
                class="stat-badge-input editable-slot-control"
                value="20"
                min="1"
                max="100"
                step="1"
                title="Change batch capacity for this time slot"
              />
            </div>

            <div class="status-badge-col">
              <label for="slotStatusDisplay" class="stat-badge-label">Slot Status:</label>
              <select
                id="slotStatusDisplay"
                class="stat-badge-input editable-slot-control"
                title="Change slot status (Open, Full, or Closed) for this time slot"
              >
                <option value="Open">Open</option>
                <option value="Full">Full</option>
                <option value="Closed">Closed</option>
              </select>
            </div>

            <div class="bulk-status-col">
              <label for="bulkStatusSelect" class="bulk-status-label">Change Order<br />Status for All:</label>
              <select id="bulkStatusSelect" class="bulk-status-select">
                <option value="">Select...</option>
                <option value="1">Queued</option>
                <option value="2">Empty Picked Up</option>
                <option value="3">Refilling</option>
                <option value="4">Out For Delivery</option>
                <option value="5">Delivered &amp; Paid</option>
                <option value="5_UNPAID">Delivered (Unpaid / Utang)</option>
                <option value="6">Rescheduled</option>
                <option value="7">Cancelled</option>
              </select>
            </div>
          </div>

          <!-- Queue Table (Populated dynamically from MySQL — Today's Orders Only) -->
          <div class="table-scroll-box">
            <table class="batch-table" id="batchTable">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Tag &amp; Phone#</th>
                  <th>Gallon - Price</th>
                  <th>Brgy &amp; Address</th>
                  <th>Order Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="batchTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <!--=========================Left Panel: Active Batch Queue Directory=========================-->

      <!--=========================Right Panel: Edit Order Daily Details Form=========================-->
      <div class="form-box">
        <div class="box-header">
          <h3>Edit Order Daily Details</h3>
        </div>

        <div class="form-body">
          <!-- Feedback Banner -->
          <div id="batchStatusBanner" class="status-banner" role="status"></div>

          <form id="batchOrderForm" method="POST" action="Batch_Orders.php" novalidate>
            <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />
            <input type="hidden" name="form_action" id="batchFormAction" value="save" />
            <input type="hidden" name="bulk_status_val" id="bulkStatusValInput" value="" />
            <input type="hidden" name="bulk_slot_id" id="bulkSlotIdInput" value="1" />
            <input type="hidden" name="new_batch_capacity" id="newBatchCapacityInput" value="20" />
            <input type="hidden" name="new_slot_status" id="newSlotStatusInput" value="Open" />

            <!-- 2-Column Split Layout Inside Edit Order Daily Details Matching Updated Wireframe -->
            <div class="edit-form-split-columns">

              <!-- Left Column: Stacked Dropdowns & Additional Notes -->
              <div class="form-left-col">
                <div class="form-group">
                  <label for="orderStatusStage" class="form-label">Order Status</label>
                  <select id="orderStatusStage" name="status_id" class="form-control-select">
                    <option value="1">Queued</option>
                    <option value="2">Empty Picked Up</option>
                    <option value="3">Refilling</option>
                    <option value="4">Out For Delivery</option>
                    <option value="5">Delivered &amp; Paid</option>
                    <option value="5_UNPAID">Delivered (Unpaid / Utang)</option>
                    <option value="6">Rescheduled</option>
                    <option value="7">Cancelled</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="orderServiceMode" class="form-label">Service Mode:*</label>
                  <select id="orderServiceMode" name="service_mode" class="form-control-select">
                    <option value="Pickup/Delivery">Pickup/Delivery</option>
                    <option value="Walk-In/Pickup">Walk-In/Pickup</option>
                    <option value="Walk-In/Delivery">Walk-In/Delivery</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="orderSlotSelect" class="form-label">Batch Slot:*</label>
                  <select id="orderSlotSelect" name="slot_id" class="form-control-select">
                    <option value="1">8:00 AM - 10:00 AM</option>
                    <option value="2">10:00 AM - 12:00 PM</option>
                    <option value="3">12:00 PM - 2:00 PM</option>
                    <option value="4">2:00 PM - 4:00 PM</option>
                    <option value="5">4:00 PM - 6:00 PM</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="orderRiderSelect" class="form-label">Delivery Rider:*</label>
                  <select id="orderRiderSelect" name="rider_id" class="form-control-select">
                    <option value="EMP-0001">EMP-0001 (Test Admin)</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="orderInspectionSelect" class="form-label">Inspection:*</label>
                  <select id="orderInspectionSelect" name="inspection_status" class="form-control-select">
                    <option value="Good">OK / Good</option>
                    <option value="Damaged">Damaged/Leak (P250)</option>
                    <option value="Pending">Pending</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="orderDelayNoticeSelect" class="form-label">Delay/Hold Notice:*</label>
                  <select id="orderDelayNoticeSelect" name="delay_hold_notice" class="form-control-select">
                    <option value="None">None</option>
                    <option value="Filter Backwash">Filter Backwash Hold</option>
                    <option value="Gate Closed">Gate Closed (Called)</option>
                    <option value="Tricycle Delay">Tricycle Problem Delay</option>
                  </select>
                </div>

                <div class="form-group notes-group">
                  <label for="orderAdditionalNotes" class="form-label">Additional Notes:</label>
                  <textarea
                    id="orderAdditionalNotes"
                    name="order_notes"
                    class="form-control-textarea"
                  ></textarea>
                </div>
              </div>

              <!-- Right Column: ORD-# & CUS-# Badges, Customer Information Box & Order Box -->
              <div class="form-right-col">
                <div class="top-id-badges-row">
                  <input
                    type="text"
                    id="orderIdBadge"
                    name="order_id"
                    class="id-badge-input"
                    value="ORD-#"
                    readonly
                  />
                  <input
                    type="text"
                    id="customerIdBadge"
                    name="customer_id"
                    class="id-badge-input"
                    value="CUS-#"
                    readonly
                  />
                </div>

                <!-- Customer Information Box -->
                <div class="inner-section-box customer-info-box">
                  <div class="box-inner-header">
                    <span class="box-inner-title">Customer Information</span>
                  </div>

                  <div class="customer-two-col-grid">
                    <div class="cust-col-left">
                      <div class="form-group">
                        <label for="orderCustomerName" class="form-label">Customer Name:*</label>
                        <input
                          type="text"
                          id="orderCustomerName"
                          name="customer_name"
                          class="form-control-input"
                          required
                        />
                      </div>

                      <div class="form-group compact-phone-group">
                        <label for="orderCellphone" class="form-label">Cellphone #:*</label>
                        <input
                          type="tel"
                          id="orderCellphone"
                          name="cellphone"
                          class="form-control-input compact-phone-input"
                          required
                        />
                      </div>

                      <div class="form-group compact-phone-group">
                        <label for="orderBackupPhone" class="form-label">Backup Phone #:</label>
                        <input
                          type="tel"
                          id="orderBackupPhone"
                          name="backup_phone"
                          class="form-control-input compact-phone-input"
                        />
                      </div>
                    </div>

                    <div class="cust-col-right">
                      <div class="form-group">
                        <label for="orderAddress" class="form-label">Address:*</label>
                        <input
                          type="text"
                          id="orderAddress"
                          name="address"
                          class="form-control-input"
                          required
                        />
                      </div>

                      <div class="form-group">
                        <label for="orderBarangay" class="form-label">Barangay:*</label>
                        <select id="orderBarangay" name="barangay" class="form-control-select" required>
                          <option value="22">Brgy. 22</option>
                          <option value="24">Brgy. 24</option>
                          <option value="26">Brgy. 26</option>
                          <option value="27">Brgy. 27</option>
                          <option value="35">Brgy. 35</option>
                        </select>
                      </div>

                      <div class="form-group">
                        <label for="orderContainerLabel" class="form-label">Container Label:</label>
                        <input
                          type="text"
                          id="orderContainerLabel"
                          name="container_label"
                          class="form-control-input"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Order Box (Order Type, Quantity & Total) -->
                <div class="inner-section-box order-summary-box">
                  <div class="order-box-split">
                    <div class="order-box-left">
                      <span class="order-box-heading">Order</span>

                      <div class="form-group">
                        <label for="orderProductSelect" class="form-label">Order Type:</label>
                        <select id="orderProductSelect" name="product_id" class="form-control-select">
                          <option value="PID-2">5-Gal - Slim Refill (P30.00)</option>
                          <option value="PID-1">5-Gal - Round Refill (P30.00)</option>
                          <option value="NEW-SLIM">New 5-Gal Slim (P250.00)</option>
                          <option value="NEW-ROUND">New 5-Gal Round (P250.00)</option>
                          <option value="NEW-SLIM-REFILL">New Slim + Refill (P280.00)</option>
                          <option value="NEW-ROUND-REFILL">New Round + Refill (P280.00)</option>
                          <option value="OTHER">Other (Manual Price)</option>
                        </select>
                      </div>

                      <div class="quantity-field-wrapper">
                        <label for="orderQuantity" class="form-label">Quantity</label>
                        <input
                          type="number"
                          id="orderQuantity"
                          name="quantity"
                          class="form-control-input quantity-input"
                          value="1"
                          min="1"
                          max="100"
                          step="1"
                          required
                        />
                      </div>
                    </div>

                    <div class="order-box-right">
                      <span class="total-heading-label">Total:</span>
                      <div class="total-price-display-box">
                        <strong class="price-amount-text" id="orderTotalPriceText">P0.00</strong>
                        <input
                          type="number"
                          id="orderManualTotalInput"
                          name="manual_total_price"
                          class="form-control-input"
                          step="0.01"
                          min="0.01"
                          placeholder="0.00"
                          style="display:none; width:100%; text-align:center; font-weight:800; font-size:1.15rem;"
                        />
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- Bottom Horizontal Action Buttons Row Matching Updated Wireframe -->
            <div class="bottom-form-action-row">
              <button type="submit" id="saveOrderDetailsBtn" class="btn-save-order">
                Save Order Details
              </button>
              <button type="button" id="clearCancelBtn" class="btn-clear-cancel">
                Clear/Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
      <!--=========================Right Panel: Edit Order Daily Details Form=========================-->

    </section>
    <!-- ======================Main Split Section====================== -->

  </main>

</body>
</html>
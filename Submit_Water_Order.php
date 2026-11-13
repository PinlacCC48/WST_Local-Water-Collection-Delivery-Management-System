<!-- database connection -->
<?php require_once __DIR__ . '/cust_db2.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit Water Order Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Submit_Water_Order_Style.css?v=2" />
  <script src="Submit_Water_Order_Script.js?v=2" defer></script>
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
        <p>Customer Portal</p>
      </div>
    </div>

    <nav class="nav-right">
      <a href="Customer_Dashboard.php"  class="nav-pill-btn">Dashboard</a>
      <a href="Submit_Water_Order.php"  class="nav-pill-btn active">Submit Water Order</a>
      <a href="Order_New_Container.php" class="nav-pill-btn">Order New Container</a>
      <a href="View_Order_Status.php"   class="nav-pill-btn">View Order Status</a>
      <a
        href="Login.php?logout=1"
        class="nav-pill-btn"
        onclick="return confirm('Are you sure you want to log out of your account?');"
      >Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="submit-order-workspace">

    <!-- ======================Page Header & Divider====================== -->
    <section class="page-header-section">
      <h2>Submit Water Refill Order (Pickup &amp; Delivery)</h2>
      <p>Schedule your 5-gallon water container refill (Flat P30.00 Rate)</p>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Page Header & Divider====================== -->

    <!-- ======================Main Split Section====================== -->
    <form id="submitRefillForm" class="submit-order-grid" method="POST" action="Submit_Water_Order.php" novalidate>
      <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />
      <input type="hidden" id="selectedBatchSlotInput" name="slot_id" value="1" />

      <!--=========================Left Panel: Water Refill Booking Form=========================-->
      <div class="booking-form-box">
        <div class="box-header">
          <h3>Water Refill Booking Form</h3>
        </div>

        <div class="booking-form-body">
        

          <div class="walkin-notice-group">
            <h4>Note! Station Walk-In Refill (P30.00)</h4>
            <p>Bring Container to Station (Prioritized first in queue)</p>
          </div>

          <div class="pickup-delivery-group">
            <h4>Pickup &amp; Delivery (P30.00)</h4>
            <h5 class="step-label">1. Select Pickup &amp; Delivery Batch Slot</h5>

            <!-- Top Row: Batch Slot Buttons -->
            <div class="batch-slots-top-row">
              <button type="button" class="batch-slot-btn active" data-slot="1" data-label="8:00 AM - 10:00 AM">
                <strong>8:00 AM - 10:00 AM</strong>
                <span id="slotCountText1">0/20 Containers | Queued</span>
              </button>

              <button type="button" class="batch-slot-btn" data-slot="2" data-label="10:00 AM - 12:00 PM">
                <strong>10:00 AM - 12:00 PM</strong>
                <span id="slotCountText2">0/20 Containers | Queued</span>
              </button>

              <button type="button" class="batch-slot-btn" data-slot="3" data-label="12:00 PM - 2:00 PM">
                <strong>12:00 PM - 2:00 PM</strong>
                <span id="slotCountText3">0/20 Containers | Queued</span>
              </button>
            </div>

            <!-- Bottom Row: Centered Batch Slot Buttons -->
            <div class="batch-slots-bottom-row">
              <button type="button" class="batch-slot-btn" data-slot="4" data-label="2:00 PM - 4:00 PM">
                <strong>2:00 PM - 4:00 PM</strong>
                <span id="slotCountText4">0/20 Containers | Queued</span>
              </button>

              <button type="button" class="batch-slot-btn" data-slot="5" data-label="4:00 PM - 6:00 PM">
                <strong>4:00 PM - 6:00 PM</strong>
                <span id="slotCountText5">0/20 Containers | Queued</span>
              </button>
            </div>
          </div>

          <!--  Gallon Container Type and Container Details -->
          <div class="container-details-section">
            <h5 class="step-label">2. Gallon Container Type and Container Details</h5>

            <div class="details-group-box">
              <!-- Column 1: Container Type, Quantity, Container Label -->
              <div class="details-col">
                <div class="form-group">
                  <label for="refillContainerType" class="form-label">Container Type:*</label>
                  <select id="refillContainerType" name="container_type" class="form-control-select" required>
                    <option value="5-Gal Slim">5-Gal Slim</option>
                    <option value="5-Gal Round">5-Gal Round</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="refillQuantity" class="form-label">Quantity</label>
                  <input
                    type="number"
                    id="refillQuantity"
                    name="quantity"
                    class="form-control-input"
                    value="1"
                    min="1"
                    max="20"
                    step="1"
                    required
                  />
                </div>

                <div class="form-group">
                  <label for="refillContainerLabel" class="form-label">Container Label (Nickname):*</label>
                  <input
                    type="text"
                    id="refillContainerLabel"
                    name="container_label"
                    class="form-control-input"
                    required
                  />
                </div>
              </div>

              <!-- Column 2: Brgy, Address, Cellphone #, Backup Phone # -->
              <div class="details-col">
                <div class="form-group">
                  <label for="refillBarangay" class="form-label">Brgy:*</label>
                  <select id="refillBarangay" name="barangay" class="form-control-select" required>
                    <option value="Brgy 22">Brgy 22</option>
                    <option value="Brgy 24">Brgy 24</option>
                    <option value="Brgy 26">Brgy 26</option>
                    <option value="Brgy 27">Brgy 27</option>
                    <option value="Brgy 35">Brgy 35</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="refillAddress" class="form-label">Address:*</label>
                  <input
                    type="text"
                    id="refillAddress"
                    name="address"
                    class="form-control-input"
                    required
                  />
                </div>

                <div class="form-group compact-phone-group">
                  <label for="refillCellphone" class="form-label">Cellphone #:*</label>
                  <input
                    type="tel"
                    id="refillCellphone"
                    name="cellphone"
                    class="form-control-input compact-input"
                    required
                  />
                </div>

                <div class="form-group compact-phone-group">
                  <label for="refillBackupPhone" class="form-label">Backup Phone #:*</label>
                  <input
                    type="tel"
                    id="refillBackupPhone"
                    name="backup_phone"
                    class="form-control-input compact-input"
                  />
                </div>
              </div>

              <!-- Column 3: Gate/Rider Note -->
              <div class="details-col note-col">
                <div class="form-group full-height-group">
                  <label for="refillGateNote" class="form-label">Gate/Rider Note:</label>
                  <textarea
                    id="refillGateNote"
                    name="gate_rider_note"
                    class="form-control-textarea"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--=========================Left Panel: Water Refill Booking Form=========================-->

      <!--=========================Right Panel: Digital Receipt=========================-->
      <div class="receipt-box">
        <div class="box-header">
          <h3>Digital Receipt</h3>
        </div>

        <div class="receipt-panel-body">
          <div class="receipt-top-branding">
            <h4>Majestic Spring Water Station</h4>
            <button type="button" id="btnLoadPreset" class="btn-load-preset">
              <span>Load Save Refill Preset</span>
              <span class="preset-arrow">&#10148;</span>
            </button>
          </div>

          <!-- White Inner Receipt Card -->
          <div class="receipt-white-card">
            <div class="receipt-lines-list">
              <div class="receipt-line-row">
                <span>Selected Batch Slot:</span>
                <strong id="recSlot">8:00 AM - 10:00 AM</strong>
              </div>
              <div class="receipt-line-row">
                <span>Service Mode:</span>
                <strong id="recMode">Pickup &amp; Delivery</strong>
              </div>
              <div class="receipt-line-row">
                <span>Barangay Route:</span>
                <strong id="recBrgy">Brgy 22</strong>
              </div>
              <div class="receipt-line-row">
                <span>Customer Name:</span>
                <strong id="recCustomerName">Customer</strong>
              </div>
              <div class="receipt-line-row">
                <span>Container Label</span>
                <strong id="recLabel">—</strong>
              </div>
              <div class="receipt-line-row">
                <span>Container Selected</span>
                <strong id="recContainerSelected">1x 5-Gal Slim Refill</strong>
              </div>
              <div class="receipt-line-row">
                <span>Refill Rate (1 Container):</span>
                <strong id="recRate">P30.00</strong>
              </div>
              <div class="receipt-line-row">
                <span>Quantity:</span>
                <strong id="recQuantity">1</strong>
              </div>
              <div class="receipt-line-row">
                <span>Payment Method:</span>
                <strong>Cash Only</strong>
              </div>
            </div>

            <div class="receipt-total-divider"></div>

            <div class="receipt-total-row">
              <strong>TOTAL CASH PAYABLE:</strong>
              <strong id="recTotalPayable">P30.00</strong>
            </div>
          </div>
            <!-- Feedback Banner -->
          <div id="submitStatusBanner" class="status-banner" role="status"></div>

          <!-- Bottom Buttons -->
          <div class="receipt-actions-row">
            <button type="submit" id="btnSubmitRefillOrder" class="btn-submit-refill">
              <span id="submitBtnText">Submit Water Refill<br />Order (P30.00)</span>
              <span class="action-arrow">&#10148;</span>
            </button>

            <button type="button" id="btnSavePreset" class="btn-save-preset">
              <span>Save as My Usual<br />Refill Preset</span>
              <span class="action-arrow">&#10148;</span>
            </button>
          </div>
        </div>
      </div>
      <!--=========================Right Panel: Digital Receipt=========================-->

    </form>
    <!-- ======================Main Split Section====================== -->

  </main>

</body>
</html>
<!-- database connection -->
<?php require_once __DIR__ . '/cust_db2.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order New Container Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Order_New_Container_Style.css?v=2" />
  <script src="Order_New_Container_Script.js?v=2" defer></script>
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
      <a href="Submit_Water_Order.php"  class="nav-pill-btn">Submit Water Order</a>
      <a href="Order_New_Container.php" class="nav-pill-btn active">Order New Container</a>
      <a href="View_Order_Status.php"   class="nav-pill-btn">View Order Status</a>
      <a
        href="Login.php?logout=1"
        class="nav-pill-btn"
        onclick="return confirm('Are you sure you want to log out of your account?');"
      >Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="order-container-workspace">

    <!-- ======================Page Title & Divider====================== -->
    <section class="page-header-section">
      <div class="page-header-titles">
        <h2>Order Brand-New 5-Gallon Water Container from the Station</h2>
        <p>Purchase a brand-new 5-Gallon Slim or Round Container (P250.00) with optional initial water refill (P30.00)</p>
      </div>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Page Title & Divider====================== -->

    <!-- ======================Main Split Grid: Booking Form & Digital Receipt====================== -->
    <form id="orderContainerForm" action="Order_New_Container.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />
      <input type="hidden" id="selectedContainerInput" name="container_type" value="5-Gal - Slim" />

      <section class="order-split-layout">

        <!-- ======================Left Panel: Water Refill Booking Form====================== -->
        <div class="panel-box booking-form-panel">
          <div class="panel-box-header">
            <h3>Water Refill Booking Form</h3>
          </div>

          <div class="panel-box-body">

            <!-- Selection of Brand-New 5-Gallon Water Container Type -->
            <div class="step-section">
              <h4 class="step-heading">1. Select Brand-New 5-Gallon Water Container Type</h4>

              <div class="container-cards-grid">

                <!-- 5-Gallon Slim Container with Faucet -->
                <div class="container-choice-card selected" id="cardSlimContainer" data-container="5-Gal - Slim">
                  <h5 class="container-card-title">
                    5-Gallon Slim<br />Container with Faucet
                  </h5>
                  <div class="container-card-price" id="priceTextSlim">P250.00 / Container</div>
                  <p class="container-card-desc" id="descTextSlim">
                    Brand-new 5-Gal (19L) Slim Gallon with<br />faucet
                  </p>
                  <button type="button" class="btn-select-container active" id="btnSelectSlim">
                    Select Slim Container
                  </button>
                </div>

                <!-- 5-Gallon Round Dispenser Bottle -->
                <div class="container-choice-card" id="cardRoundContainer" data-container="5-Gal - Round">
                  <h5 class="container-card-title">
                    5-Gallon Round<br />Dispenser Bottle
                  </h5>
                  <div class="container-card-price" id="priceTextRound">P250.00 / Container</div>
                  <p class="container-card-desc" id="descTextRound">
                    Brand-new 5-Gal (19L) Round Gallon for<br />water dispensers
                  </p>
                  <button type="button" class="btn-select-container" id="btnSelectRound">
                    Select Round Container
                  </button>
                </div>

              </div>
            </div>

            <!--  Gallon Container Type and Container Details -->
            <div class="step-section">
              <h4 class="step-heading">2. Gallon Container Type and Container Details</h4>

              <div class="container-details-box">

                <!-- Left Column: Refill Option, Batch Slot, Container Label -->
                <div class="details-col-left">
                  <div class="field-item">
                    <label for="waterRefillOptionSelect">Water Refill Option:*</label>
                    <select id="waterRefillOptionSelect" name="water_refill_option" required>
                      <option value="With Refill" selected>Include Initial Water Refill (+P30.00)</option>
                      <option value="Container Only">Empty Container Only (P0.00 Refill)</option>
                    </select>
                  </div>

                  <div class="field-item">
                    <label for="batchSlotSelect">Delivery Batch Slot (8AM- 6PM):*</label>
                    <select id="batchSlotSelect" name="slot_label" required>
                      <option value="8:00 AM - 10:00 AM">8:00 AM - 10:00 AM</option>
                      <option value="10:00 AM - 12:00 PM" selected>10:00 AM - 12:00 PM</option>
                      <option value="12:00 PM - 2:00 PM">12:00 PM - 2:00 PM</option>
                      <option value="2:00 PM - 4:00 PM">2:00 PM - 4:00 PM</option>
                      <option value="4:00 PM - 6:00 PM">4:00 PM - 6:00 PM</option>
                    </select>
                  </div>

                  <div class="field-item">
                    <label for="containerLabelInput">Container Label (Nickname):*</label>
                    <input
                      type="text"
                      id="containerLabelInput"
                      name="container_label"
                      value=""
                      required
                    />
                  </div>
                </div>

                <!-- Middle Column: Brgy, Address, Cellphone #, Backup Phone # -->
                <div class="details-col-mid">
                  <div class="field-item">
                    <label for="barangaySelect">Brgy:*</label>
                    <select id="barangaySelect" name="barangay" required>
                      <option value="22">Brgy 22</option>
                      <option value="24">Brgy 24</option>
                      <option value="26">Brgy 26</option>
                      <option value="27">Brgy 27</option>
                      <option value="35">Brgy 35</option>
                    </select>
                  </div>

                  <div class="field-item">
                    <label for="addressInput">Address:*</label>
                    <input
                      type="text"
                      id="addressInput"
                      name="address"
                      value=""
                      required
                    />
                  </div>

                  <div class="field-item compact-phone">
                    <label for="cellphoneInput">Cellphone #:*</label>
                    <input
                      type="tel"
                      id="cellphoneInput"
                      name="cp_num_primary"
                      value=""
                      required
                    />
                  </div>

                  <div class="field-item compact-phone">
                    <label for="backupPhoneInput">Backup Phone #:</label>
                    <input
                      type="tel"
                      id="backupPhoneInput"
                      name="cp_num_backup"
                      value=""
                    />
                  </div>
                </div>

                <!-- Right Column: Service Mode & Gate/Rider Note -->
                <div class="details-col-right">
                  <div class="field-item">
                    <label for="serviceModeSelect">Service Mode:*</label>
                    <select id="serviceModeSelect" name="service_mode" required>
                      <option value="Delivery" selected>Delivery</option>
                      <option value="Walk-In/Pickup">Walk-In/Pickup</option>
                    </select>
                  </div>

                  <div class="field-item note-textarea-item">
                    <label for="gateRiderNoteInput">Gate/Rider Note:</label>
                    <textarea
                      id="gateRiderNoteInput"
                      name="gate_rider_note"
                      rows="5"
                    ></textarea>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
        <!-- ======================Left Panel: Water Refill Booking Form====================== -->

        <!-- ======================Right Panel: Digital Receipt====================== -->
        <aside class="panel-box digital-receipt-panel">
          <div class="panel-box-header">
            <h3>Digital Receipt</h3>
          </div>

          <div class="receipt-shaded-body">
            <h4 class="receipt-station-title">Majestic Spring Water Station</h4>

            <!-- Order Receipt Card -->
            <div class="receipt-white-card">
              <div class="receipt-line-row">
                <span class="receipt-label">Item Selected:</span>
                <strong class="receipt-val" id="receiptItemSelected">1x New 5-Gal - Slim Container</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">New Container Price:</span>
                <strong class="receipt-val" id="receiptContainerPrice">P250.00</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Water Refill:</span>
                <strong class="receipt-val" id="receiptWaterRefill">+P30.00</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Service Mode:</span>
                <strong class="receipt-val" id="receiptServiceMode">Delivery</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Assigned Delivery Slot:</span>
                <strong class="receipt-val" id="receiptDeliverySlot">10:00 AM - 12:00 PM</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Barangay Route:</span>
                <strong class="receipt-val" id="receiptBrgy">Brgy 22</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Customer Name:</span>
                <strong class="receipt-val" id="receiptCustomerName">Customer</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Container Label:</span>
                <strong class="receipt-val" id="receiptContainerLabel">—</strong>
              </div>
              <div class="receipt-line-row">
                <span class="receipt-label">Payment Method:</span>
                <strong class="receipt-val">Cash Only</strong>
              </div>

              <div class="receipt-divider"></div>

              <div class="receipt-total-row">
                <span class="total-label">TOTAL CASH PAYABLE:</span>
                <strong class="total-amount" id="receiptTotalPayable">P280.00</strong>
              </div>
            </div>

            <!-- Feedback Message Banner -->
            <div id="orderFeedbackMsg" class="feedback-banner" hidden></div>

            <!-- Bottom Action Buttons -->
            <div class="receipt-bottom-actions">
              <button type="submit" id="btnConfirmNewContainer" class="btn-confirm-container">
                <span id="btnConfirmText">Confirm &amp; Order New<br />Container (P280.00)</span>
                <span class="btn-arrow-dark">&#10148;</span>
              </button>

              <button type="button" id="btnResetSelection" class="btn-reset-selection">
                <span>Clear/ Reset<br />Selection</span>
                <span class="btn-arrow-light">&#10148;</span>
              </button>
            </div>
          </div>
        </aside>
        <!-- ======================Right Panel: Digital Receipt====================== -->

      </section>
    </form>
    <!-- ======================Main Split Grid: Booking Form & Digital Receipt====================== -->

  </main>

</body>
</html>
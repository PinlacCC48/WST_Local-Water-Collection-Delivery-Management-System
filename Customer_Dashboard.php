<!-- database connection -->
<?php require_once __DIR__ . '/cust_db1.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Dashboard Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Customer_Dashboard_Style.css?v=2" />
  <script src="Customer_Dashboard_Script.js?v=2" defer></script>
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
      <a href="Customer_Dashboard.php"  class="nav-pill-btn active">Dashboard</a>
      <a href="Submit_Water_Order.php"  class="nav-pill-btn">Submit Water Order</a>
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

  <main class="customer-workspace">

    <!-- ======================Welcome Banner & Divider====================== -->
    <section class="welcome-banner-section">
      <h2 id="welcomeGreetingHeading">Welcome, Micael Masta! (Container Label: Masta - Brgy 27)</h2>
      <p>Schedule your water refill order (P30) and see when it will arrive or buy a new container</p>
      <div class="overview-divider"></div>
      <div id="custDashboardBanner" class="status-banner" role="status"></div>
    </section>
    <!-- ======================Welcome Banner & Divider====================== -->

    <!-- ======================Active Order Live Tracker Box====================== -->
    <section class="active-tracker-box">
      <div class="tracker-top-row">
        <div class="tracker-title-group">
          <h3 class="tracker-main-heading">Active Order Live Tracker:</h3>
          <p id="trackerSubInfo" class="tracker-sub-info">
            Batch 1: 8:00 AM - 10:00 AM | Container Label: Masta
          </p>
        </div>

        <div id="trackerRiderAndTotal" class="tracker-assigned-total">
          Assigned Rider: Masta | TOTAL: P30.00 (Cash Only)
        </div>
      </div>

      <!-- 5-Stage Stepper Grid -->
      <div class="stepper-stages-grid">
        <div id="stageBtn1" class="stepper-stage-btn active">1. Queued</div>
        <div id="stageBtn2" class="stepper-stage-btn">2. Empty Picked Up</div>
        <div id="stageBtn3" class="stepper-stage-btn">3. Refilling</div>
        <div id="stageBtn4" class="stepper-stage-btn">4. Out For Delivery</div>
        <div id="stageBtn5" class="stepper-stage-btn">5. Delivered &amp; Paid</div>
      </div>

      <div id="trackerItemAndInspection" class="tracker-footer-row">
        Order Item: 1x 5-Gal - Slim Refill (P30.00) | Inspection: Good
      </div>
    </section>
    <!-- ======================Active Order Live Tracker Box====================== -->

    <!-- ====================== Customer Service Cards====================== -->
    <section class="customer-cards-grid">

      <!--  Submit Water Refill Order -->
      <article class="service-card">
        <div>
          <div class="service-card-header">
            <h3>1. Submit Water Refill Order P30.00</h3>
          </div>
          <div class="service-card-body">
            <ul class="service-bullets-list">
              <li>Refill 5-Gal Slim or Round Container (1 Container per order)</li>
              <li>Flat P30.00 price for both Walk-in and 2-Trip (Pickup/Delivery)</li>
              <li>Walk-in refill are prioritized first at the station counter</li>
              <li>Automatic labeled with Nickname of customer</li>
              <li>Choose batch slot for delivery (8:00 AM - 6:00 PM cut-off)</li>
            </ul>
          </div>
        </div>
        <div class="service-card-footer">
          <a href="Submit_Water_Order.php" id="btnScheduleWaterRefill" class="service-action-btn">
            <span>Schedule Water Refill (P30.00)</span>
            <span class="arrow-icon">&#10148;</span>
          </a>
        </div>
      </article>

      <!-- Order New 5-Gal Container -->
      <article class="service-card">
        <div>
          <div class="service-card-header">
            <h3>2. Order New 5-Gal Container P250.00</h3>
          </div>
          <div class="service-card-body">
            <ul class="service-bullets-list">
              <li>Brand-new 5-Gal Slim or Round Water Container (P250.00)</li>
              <li>Include initial water refill (+P30.00 = P280.00 TOTAL)</li>
              <li>Recommended replacement if container damage is spotted</li>
              <li>Pre-labeled with customer Nickname</li>
              <li>Paid in Cash at Walk-In or upon Delivery</li>
            </ul>
          </div>
        </div>
        <div class="service-card-footer">
          <a href="Order_New_Container.php" id="btnOrderNewContainer" class="service-action-btn">
            <span>Order New Container (P250.00)</span>
            <span class="arrow-icon">&#10148;</span>
          </a>
        </div>
      </article>

      <!--View Order Status & Tracker -->
      <article class="service-card">
        <div>
          <div class="service-card-header">
            <h3>3. View Order Status &amp; Tracker</h3>
          </div>
          <div class="service-card-body">
            <ul class="service-bullets-list">
              <li>Track Trip 1 (Empty Pickup), Station Refill, and Trip 2 (Delivery)</li>
              <li>View Station container inspection status (Good vs Damaged)</li>
              <li>Check station notices &amp; next-batch reschedules</li>
              <li>Reschedule pending orders before rider pickup</li>
              <li>View past order history</li>
            </ul>
          </div>
        </div>
        <div class="service-card-footer">
          <a href="View_Order_Status.php" id="btnTrackOrderStatus" class="service-action-btn">
            <span>Track My Order Status</span>
            <span class="arrow-icon">&#10148;</span>
          </a>
        </div>
      </article>

    </section>
    <!-- ===================== Customer Service Cards====================== -->

    <!-- ======================Bottom: Today's Batch Slot Availability ====================== -->
    <section class="bottom-slot-availability-box">
      <div class="availability-header">
        <h3>Today’s Batch Slot Availability (08:00 AM - 6:00 PM Cut-off | 20 Containers / Slot)</h3>
      </div>

      <div class="availability-content-grid">
        <div class="slots-pill-buttons-row">
          <button type="button" id="slotPill1" class="slot-pill active">
            <strong>8:00 AM - 10:00 AM</strong>
            <span id="slotPillSub1">0/20 Containers | Queued</span>
          </button>

          <button type="button" id="slotPill2" class="slot-pill">
            <strong>10:00 AM - 12:00 PM</strong>
            <span id="slotPillSub2">0/20 Containers | Queued</span>
          </button>

          <button type="button" id="slotPill3" class="slot-pill">
            <strong>12:00 PM - 2:00 PM</strong>
            <span id="slotPillSub3">0/20 Containers | Queued</span>
          </button>

          <button type="button" id="slotPill4" class="slot-pill">
            <strong>2:00 PM - 4:00 PM</strong>
            <span id="slotPillSub4">0/20 Containers | Queued</span>
          </button>

          <button type="button" id="slotPill5" class="slot-pill">
            <strong>4:00 PM - 6:00 PM</strong>
            <span id="slotPillSub5">0/20 Containers | Queued</span>
          </button>
        </div>

        <form id="repeatOrderForm" method="POST" action="Customer_Dashboard.php">
          <input type="hidden" name="action" value="repeat_usual_refill" />
          <input type="hidden" id="repeatSlotIdInput" name="slot_id" value="1" />
          <input type="hidden" id="repeatProductIdInput" name="product_id" value="PRD-01" />
          <button type="submit" id="btnRepeatOrder" class="btn-repeat-order">
            <span id="repeatBtnLabelText">1-Click Repeat Usual<br />Refill</span>
            <span class="repeat-arrow-icon">&#10148;</span>
          </button>
        </form>
      </div>
    </section>
    <!-- ======================Bottom: Today's Batch Slot Availability & button click====================== -->

  </main>

</body>
</html>
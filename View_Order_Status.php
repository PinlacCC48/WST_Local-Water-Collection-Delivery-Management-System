<!-- database connection -->
<?php require_once __DIR__ . '/cust_db3.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>View Order Status &amp; History — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="View_Order_Status_Style.css?v=4" />
  <script src="View_Order_Status_Script.js?v=4" defer></script>
</head>
<body>

  <!-- ======================Navigation Bar====================== -->
  <header class="top-navbar">
    <div class="brand-left">
      <button
        type="button"
        id="themeToggleBtn"
        class="theme-circle-btn"
        title="Toggle Light / Dark Mode"
        aria-label="Toggle Theme"
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
      <a href="Order_New_Container.php" class="nav-pill-btn">Order New Container</a>
      <a href="View_Order_Status.php"   class="nav-pill-btn active">View Order Status</a>
      <a
        href="Login.php?logout=1"
        class="nav-pill-btn"
        onclick="return confirm('Are you sure you want to log out of your account?');"
      >Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="order-status-workspace">

    <!-- ======================Page Header & Divider====================== -->
    <section class="page-header-section">
      <div class="page-header-titles">
        <h2>View Order Status and History</h2>
        <p>Track live container inspection and 5-stage pickup and delivery progress</p>
      </div>
      <div class="overview-divider"></div>
      <div id="statusActionFeedback" class="feedback-banner" hidden role="status"></div>
    </section>
    <!-- ======================Page Header & Divider====================== -->

    <!-- ======================Active Order Live Tracker Box====================== -->
    <section class="active-tracker-box">
      <div class="tracker-top-row">
        <div class="tracker-title-group">
          <h3 class="tracker-main-heading">Active Order Live Tracker:</h3>
          <p id="trackerSubInfo" class="tracker-sub-info">
            Loading active order status...
          </p>
        </div>

        <div id="trackerRiderAndTotal" class="tracker-assigned-total">
          Assigned Rider: Pending | TOTAL: P0.00 (Cash Only)
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

      <div class="tracker-bottom-row">
        <div class="tracker-details-stack">
          <div id="trackerOrderItemLine">
            <strong>Order Item: </strong>Loading order summary...
          </div>
          <div id="trackerInspectionLine">
            <strong>Station Container Inspection: </strong>Passed (Good Condition)
          </div>
          <div id="trackerGateNoteLine">
            <strong>Gate/Rider Note: </strong>None
          </div>
        </div>

        <div class="tracker-actions-group">
          <button type="button" id="btnViewOrderReceipt" class="btn-view-receipt">
            <span>View Order Receipt</span>
            <span class="action-arrow-dark">&#10148;</span>
          </button>

          <button type="button" id="btnCancelQueuedOrder" class="btn-cancel-queued">
            <span>Cancel Queued<br />Order</span>
            <span class="action-arrow-light">&#10148;</span>
          </button>
        </div>

        <!-- Hidden Form for Cancel Queued Order POST -->
        <form id="cancelQueuedOrderForm" method="POST" action="View_Order_Status.php" style="display:none;">
          <input type="hidden" name="action" value="cancel_queued_order" />
          <input type="hidden" id="cancelTargetOrderIdInput" name="order_id" value="" />
        </form>
      </div>
    </section>
    <!-- ======================Active Order Live Tracker Box====================== -->

    <!-- ======================My Past Water Orders & Payment History Box====================== -->
    <section class="history-directory-box">
      <div class="history-top-bar">
        <h3 class="history-heading">My Past Water Orders &amp; Payment History</h3>
        <div class="history-filter-pills">
          <button type="button" id="filterAllOrdersBtn" class="filter-pill-btn active" data-filter="all">All Orders (0)</button>
          <button type="button" id="filterActiveBtn" class="filter-pill-btn" data-filter="Active">Active (0)</button>
          <button type="button" id="filterCompletedBtn" class="filter-pill-btn" data-filter="Completed">Completed (0)</button>
          <button type="button" id="filterRescheduledBtn" class="filter-pill-btn" data-filter="Rescheduled">Rescheduled (0)</button>
        </div>
      </div>

      <div class="table-scroll-container">
        <table class="history-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Date Ordered</th>
              <th>Batch Slot</th>
              <th>Container Label</th>
              <th>Service Mode</th>
              <th>Order Summary</th>
              <th>Total Price</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="orderHistoryTableBody">
            <!-- Dynamic rows populated via View_Order_Status_Script.js -->
          </tbody>
        </table>
      </div>
    </section>
    <!-- ======================My Past Water Orders & Payment History Box====================== -->

  </main>

</body>
</html>
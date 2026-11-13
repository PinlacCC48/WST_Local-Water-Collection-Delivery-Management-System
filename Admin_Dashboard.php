<?php require_once __DIR__ . '/admin_db2.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin/Staff Dashboard — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Admin_Dashboard_Style.css" />
  <script src="Admin_Dashboard_Script.js?v=2" defer></script>
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
      <a href="Admin_Dashboard.php"    class="nav-pill-btn active">Dashboard</a>
      <a href="Accounts.php"           class="nav-pill-btn">Accounts</a>
      <a href="Container_Products.php" class="nav-pill-btn">Container Products</a>
      <a href="Batch_Orders.php"       class="nav-pill-btn">Batch Order</a>
      <a href="Order_Records.php"      class="nav-pill-btn">Order Records</a>
      <a href="Login.php?logout=1" id="logoutBtn" class="nav-pill-btn" onclick="return confirm('Are you sure you want to log out of your account?');">Log Out</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="dashboard-workspace">

    <!-- ======================Overview Header & Divider====================== -->
    <section class="overview-header">
      <h2 id="overviewDateHeading"><?php echo htmlspecialchars($dashboardDateHeading); ?></h2>
      <p>Summary of active delivery batches and current gross sales</p>
      <div class="overview-divider"></div>
    </section>
    <!-- ======================Overview Header & Divider====================== -->

    <!-- ======================Top  Summary Cards====================== -->
    <section class="kpi-cards-grid">
      <div class="kpi-card">
        <span class="kpi-label">Active Batch Window</span>
        <strong class="kpi-value" id="kpiActiveBatchTime"><?php echo htmlspecialchars($kpiActiveBatchTime); ?></strong>
        <span class="kpi-sub" id="kpiActiveBatchSub"><?php echo htmlspecialchars($kpiActiveBatchSub); ?></span>
      </div>

      <div class="kpi-card">
        <span class="kpi-label">Orders Pending Action</span>
        <strong class="kpi-value" id="kpiPendingCount"><?php echo htmlspecialchars($kpiPendingCount); ?></strong>
        <span class="kpi-sub" id="kpiPendingBreakdown"><?php echo htmlspecialchars($kpiPendingBreakdown); ?></span>
      </div>

      <div class="kpi-card">
        <span class="kpi-label">Today's Cash Sales Log</span>
        <strong class="kpi-value" id="kpiCashSales"><?php echo htmlspecialchars($kpiCashSales); ?></strong>
        <span class="kpi-sub" id="kpiCashSub"><?php echo htmlspecialchars($kpiCashSub); ?></span>
      </div>

      <div class="kpi-card">
        <span class="kpi-label">Daily Gallon Output</span>
        <strong class="kpi-value" id="kpiGallonOutput"><?php echo htmlspecialchars($kpiGallonOutput); ?></strong>
        <span class="kpi-sub" id="kpiGallonSub"><?php echo htmlspecialchars($kpiGallonSub); ?></span>
      </div>
    </section>
    <!-- ======================Top 4 KPI Summary Cards====================== -->

    <!-- ======================Main Split Section====================== -->
    <section class="dashboard-main-grid">

      <!--=========================Left Panel: Live Delivery Batch Capacity Monitor=========================-->
      <div class="batch-monitor-box">
        <div class="monitor-header">
          <h3>Live Delivery Batch Capacity Monitor (20 Containers/Batch)</h3>
          <span>8:00 AM - 6:00 PM Cut-Off</span>
        </div>

        <div class="batch-bars-list">
          <a href="Batch_Orders.php?slot=1" class="batch-bar-item" style="text-decoration: none; color: inherit;">
            <div class="batch-bar-labels">
              <strong class="batch-slot-title">Batch 1: 8:00 AM - 10:00 AM</strong>
              <span class="batch-slot-status" id="batch1StatusText"><?php echo htmlspecialchars($batchBarsData[1]['status_text']); ?></span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" id="batch1Fill" style="width: <?php echo (int)$batchBarsData[1]['fill_pct']; ?>%;"></div>
            </div>
          </a>

          <a href="Batch_Orders.php?slot=2" class="batch-bar-item" style="text-decoration: none; color: inherit;">
            <div class="batch-bar-labels">
              <strong class="batch-slot-title">Batch 2: 10:00 AM - 12:00 PM</strong>
              <span class="batch-slot-status" id="batch2StatusText"><?php echo htmlspecialchars($batchBarsData[2]['status_text']); ?></span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" id="batch2Fill" style="width: <?php echo (int)$batchBarsData[2]['fill_pct']; ?>%;"></div>
            </div>
          </a>

          <a href="Batch_Orders.php?slot=3" class="batch-bar-item" style="text-decoration: none; color: inherit;">
            <div class="batch-bar-labels">
              <strong class="batch-slot-title">Batch 3: 12:00 PM - 2:00 PM</strong>
              <span class="batch-slot-status" id="batch3StatusText"><?php echo htmlspecialchars($batchBarsData[3]['status_text']); ?></span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" id="batch3Fill" style="width: <?php echo (int)$batchBarsData[3]['fill_pct']; ?>%;"></div>
            </div>
          </a>

          <a href="Batch_Orders.php?slot=4" class="batch-bar-item" style="text-decoration: none; color: inherit;">
            <div class="batch-bar-labels">
              <strong class="batch-slot-title">Batch 4: 2:00 PM - 4:00 PM</strong>
              <span class="batch-slot-status" id="batch4StatusText"><?php echo htmlspecialchars($batchBarsData[4]['status_text']); ?></span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" id="batch4Fill" style="width: <?php echo (int)$batchBarsData[4]['fill_pct']; ?>%;"></div>
            </div>
          </a>

          <a href="Batch_Orders.php?slot=5" class="batch-bar-item" style="text-decoration: none; color: inherit;">
            <div class="batch-bar-labels">
              <strong class="batch-slot-title">Batch 5: 4:00 PM - 6:00 PM</strong>
              <span class="batch-slot-status" id="batch5StatusText"><?php echo htmlspecialchars($batchBarsData[5]['status_text']); ?></span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" id="batch5Fill" style="width: <?php echo (int)$batchBarsData[5]['fill_pct']; ?>%;"></div>
            </div>
          </a>
        </div>

        <div class="monitor-action-row">
          <a href="Batch_Orders.php" class="monitor-action-btn">
            <span>Open Manage Batch Orders</span>
            <span class="arrow-icon">&#10148;</span>
          </a>
          <a href="Order_Records.php" class="monitor-action-btn">
            <span>Encode Walk-in pickup orders</span>
            <span class="arrow-icon">&#10148;</span>
          </a>
        </div>
      </div>
      <!--=========================Left Panel: Live Delivery Batch Capacity Monitor=========================-->

      <!--=========================Right Panel: Admin Management Modules=========================-->
      <div class="modules-box">
        <div class="modules-header">
          <h3>Admin Management Modules</h3>
        </div>

        <div class="modules-stack">
          <a href="Accounts.php" class="module-link-card">
            <div class="module-text">
              <strong>1. Manage Accounts</strong>
              <span>Add/edit details of Customer and Employees</span>
            </div>
            <span class="module-arrow">&#10148;</span>
          </a>

          <a href="Container_Products.php" class="module-link-card">
            <div class="module-text">
              <strong>2. Manage Container Products</strong>
              <span>Handle Container prices and refill rates</span>
            </div>
            <span class="module-arrow">&#10148;</span>
          </a>

          <a href="Batch_Orders.php" class="module-link-card">
            <div class="module-text">
              <strong>3. Manage Refill Queues &amp; Batches</strong>
              <span>Prioritize walk-ins and batch orders</span>
            </div>
            <span class="module-arrow">&#10148;</span>
          </a>

          <a href="Order_Records.php" class="module-link-card">
            <div class="module-text">
              <strong>4. Manage Order Records</strong>
              <span>Verify Payment Logs &amp; generate Reports</span>
            </div>
            <span class="module-arrow">&#10148;</span>
          </a>
        </div>
      </div>
      <!--=========================Right Panel: Admin Management Modules=========================-->

    </section>
    <!-- ======================Main Split Section====================== -->

  </main>

</body>
</html>
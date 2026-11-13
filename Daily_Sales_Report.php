<?php
/*=======================================
 Daily_Sales_Report.php
 Standalone window for printable report
======================================== */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

$DB_HOST = '127.0.0.1';
$DB_NAME = 'majestic_spring_wcdms';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database Connection Error: ' . htmlspecialchars($e->getMessage()));
}
/* =========   ADMIN / STAFF AUTHENTICATION GUARD   ========= */
$loggedInEmpId = (string)($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? '');
if ($loggedInEmpId === '' && !empty($_SESSION['user_id']) && stripos((string)$_SESSION['user_id'], 'EMP-') === 0) {
    $loggedInEmpId = (string)$_SESSION['user_id'];
}

$empAuthRow = null;
if ($loggedInEmpId !== '') {
    $empAuthStmt = $pdo->prepare("SELECT employee_id, emp_status FROM employee WHERE employee_id = :eid LIMIT 1");
    $empAuthStmt->execute([':eid' => $loggedInEmpId]);
    $empAuthRow = $empAuthStmt->fetch();
}

if (!$empAuthRow) {
    header('Location: Login.php');
    exit;
}

if (strcasecmp((string)$empAuthRow['emp_status'], 'Inactive') === 0) {
    $_SESSION = [];
    session_destroy();
    header('Location: Login.php?inactive=1');
    exit;
}

$selectedDate = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['date'])
    ? (string)$_GET['date']
    : date('Y-m-d');

$dateLabel = date('M j, Y', strtotime($selectedDate));

// Resolve Current Admin/Staff Actor
$currentAdminUser = 'EMP-0001 (Admin)';
if (!empty($_SESSION['user']['employee_id'])) {
    $eid   = (string)$_SESSION['user']['employee_id'];
    $fname = trim((string)($_SESSION['user']['first_name'] ?? ''));
    $currentAdminUser = $fname !== '' ? "{$eid} ({$fname})" : $eid;
} else {
    $emp = $pdo->query("SELECT employee_id, first_name FROM employee WHERE emp_status = 'Active' ORDER BY employee_id ASC LIMIT 1")->fetch();
    if ($emp) {
        $currentAdminUser = $emp['employee_id'] . ' (' . $emp['first_name'] . ')';
    }
}

// Fetch all non-cancelled orders matching the selected report date 
$stmt = $pdo->prepare("
    SELECT o.*,
           os.status_name,
           ts.slot_label,
           c.first_name AS cust_first_name,
           c.last_name AS cust_last_name,
           c.container_label,
           c.barangay,
           oi.order_type,
           oi.quantity,
           cp.container_type,
           p.total_paid,
           p.recorded_by,
           p.date_paid,
           p.payment_status
    FROM order_information o
    INNER JOIN customer c ON c.customer_id = o.customer_id
    INNER JOIN time_slot ts ON ts.slot_id = o.slot_id
    INNER JOIN order_status os ON os.status_id = o.status_id
    LEFT JOIN order_item oi ON oi.order_id = o.order_id
    LEFT JOIN container_product cp ON cp.product_id = oi.product_id
    LEFT JOIN payment p ON p.order_id = o.order_id
    WHERE o.status_id != 7
      AND (
          DATE(o.date_ordered) = :d1
          OR (o.status_id = 5 AND DATE(p.date_paid) = :d2)
      )
    ORDER BY COALESCE(p.date_paid, o.date_ordered) DESC, o.order_id DESC
");
$stmt->execute([':d1' => $selectedDate, ':d2' => $selectedDate]);
$rows = $stmt->fetchAll();

$totalAuditedCash   = 0.0;
$walkInCash         = 0.0;
$walkInContainers   = 0;
$riderCash          = 0.0;
$riderOrders        = 0;
$unpaidReceivables  = 0.0;
$unpaidCount        = 0;
$paidCount          = 0;
$refillGallons      = 0;
$newContainersCount = 0;

$batchStats = [
    1 => ['label' => '8-10 AM',  'sales' => 0.0, 'unpaid' => 0.0, 'containers' => 0, 'orders' => 0],
    2 => ['label' => '10-12 PM', 'sales' => 0.0, 'unpaid' => 0.0, 'containers' => 0, 'orders' => 0],
    3 => ['label' => '12-2 PM',  'sales' => 0.0, 'unpaid' => 0.0, 'containers' => 0, 'orders' => 0],
    4 => ['label' => '2-4 PM',   'sales' => 0.0, 'unpaid' => 0.0, 'containers' => 0, 'orders' => 0],
    5 => ['label' => '4-6 PM',   'sales' => 0.0, 'unpaid' => 0.0, 'containers' => 0, 'orders' => 0],
];

$brgyStats = [
    '22' => ['label' => 'Barangay 22', 'orders' => 0, 'sales' => 0.0, 'color' => '#0284c7'],
    '24' => ['label' => 'Barangay 24', 'orders' => 0, 'sales' => 0.0, 'color' => '#0d9488'],
    '26' => ['label' => 'Barangay 26', 'orders' => 0, 'sales' => 0.0, 'color' => '#f59e0b'],
    '27' => ['label' => 'Barangay 27', 'orders' => 0, 'sales' => 0.0, 'color' => '#7c3aed'],
    '35' => ['label' => 'Barangay 35', 'orders' => 0, 'sales' => 0.0, 'color' => '#e11d48'],
];

$modeStats = [
    'Pickup/Delivery'  => ['count' => 0, 'amount' => 0.0],
    'Walk-In/Pickup'   => ['count' => 0, 'amount' => 0.0],
    'Walk-In/Delivery' => ['count' => 0, 'amount' => 0.0],
];

$channelStats = [
    'Online Portal'      => ['count' => 0, 'amount' => 0.0],
    'User/Admin Encoded' => ['count' => 0, 'amount' => 0.0],
];

$reportRecords = [];

foreach ($rows as $r) {
    $amt       = (float)$r['total_price'];
    $qty       = max(1, (int)($r['quantity'] ?? 1));
    $stId      = (int)$r['status_id'];
    $delayHold = (string)($r['delay_hold_notice'] ?? 'None');
    $payStat   = (string)($r['payment_status'] ?? '');

    $isUtang = (stripos($delayHold, 'Utang') !== false || ($stId === 5 && $payStat === 'Unpaid'));
    if ($isUtang) {
        $payStat = 'Unpaid';
    } elseif ($payStat === '') {
        $payStat = ($stId === 5) ? 'Paid' : 'Unpaid';
    }

    $isPaid = ($payStat === 'Paid' || $payStat === 'Remitted') && !$isUtang;

    $rawMode = trim((string)$r['service_mode']);
    if (strcasecmp($rawMode, 'Walk-In/Delivery') === 0 || strcasecmp($rawMode, 'Delivery') === 0) {
        $sMode = 'Walk-In/Delivery';
    } elseif (stripos($rawMode, 'Walk') !== false) {
        $sMode = 'Walk-In/Pickup';
    } else {
        $sMode = 'Pickup/Delivery';
    }

    $oChan = (stripos((string)$r['order_channel'], 'Online') !== false)
        ? 'Online Portal'
        : 'User/Admin Encoded';

    $cType = (string)($r['container_type'] ?? '5-Gal - Slim');
    $oType = (string)($r['order_type'] ?? 'Refill Only');
    if ($oType === 'New Container + Refill') {
        $baseItem = "New {$cType} + Refill";
        $newContainersCount += $qty;
        $refillGallons      += $qty;
    } elseif ($oType === 'New Container Only') {
        $baseItem = "New {$cType}";
        $newContainersCount += $qty;
    } else {
        $baseItem = "{$cType} Refill";
        $refillGallons += $qty;
    }
    $orderSummary = ($qty > 1) ? "{$qty}x {$baseItem}" : $baseItem;

    if ($isPaid) {
        $paidCount++;
        $totalAuditedCash += $amt;
        if (stripos($sMode, 'Walk') !== false) {
            $walkInCash       += $amt;
            $walkInContainers += $qty;
        } else {
            $riderCash   += $amt;
            $riderOrders++;
        }
    } else {
        $unpaidCount++;
        $unpaidReceivables += $amt;
    }

    $slotId = max(1, min(5, (int)$r['slot_id']));
    $batchStats[$slotId]['orders']++;
    $batchStats[$slotId]['containers'] += $qty;
    if ($isPaid) {
        $batchStats[$slotId]['sales'] += $amt;
    } else {
        $batchStats[$slotId]['unpaid'] += $amt;
    }

    $brgyNum = preg_replace('/[^0-9]/', '', (string)$r['barangay']) ?: '22';
    if (!isset($brgyStats[$brgyNum])) {
        $brgyStats[$brgyNum] = [
            'label'  => "Barangay {$brgyNum}",
            'orders' => 0,
            'sales'  => 0.0,
            'color'  => '#64748b'
        ];
    }
    $brgyStats[$brgyNum]['orders']++;
    $brgyStats[$brgyNum]['sales'] += $amt;

    $modeStats[$sMode]['count']++;
    $modeStats[$sMode]['amount'] += $amt;

    $channelStats[$oChan]['count']++;
    $channelStats[$oChan]['amount'] += $amt;

    $reportRecords[] = [
        'order_id'       => (string)$r['order_id'],
        'customer_tag'   => $r['customer_id'] . ' - ' . $r['container_label'],
        'customer_name'  => $r['cust_first_name'] . ' ' . $r['cust_last_name'],
        'barangay_num'   => $brgyNum,
        'slot_label'     => 'Batch ' . $slotId . ': ' . (string)$r['slot_label'],
        'service_mode'   => $sMode,
        'order_channel'  => $oChan,
        'order_summary'  => $orderSummary,
        'total_price'    => $amt,
        'is_utang'       => $isUtang,
        'payment_status' => $payStat,
        'recorded_by'    => (string)($r['recorded_by'] ?? $currentAdminUser)
    ];
}

$totalDayOrders = max(count($reportRecords), 1);
$brgyList       = array_values($brgyStats);
$totalBrgyOrds  = array_sum(array_column($brgyList, 'orders'));

$chartConfigJson = json_encode([
    'batchLabels' => array_values(array_column($batchStats, 'label')),
    'paidData'    => array_values(array_column($batchStats, 'sales')),
    'unpaidData'  => array_values(array_column($batchStats, 'unpaid')),
    'brgyLabels'  => array_column($brgyList, 'label'),
    'brgyOrders'  => array_column($brgyList, 'orders'),
    'brgyColors'  => array_column($brgyList, 'color'),
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daily Sales &amp; Cash Audit Report — <?php echo htmlspecialchars($dateLabel); ?></title>
  <link rel="stylesheet" href="Daily_Sales_Report_Style.css?v=1" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

  <div class="top-actions-bar">
    <button type="button" class="btn-close" onclick="window.close()">&#8592; Back to Order Records</button>
    <button type="button" class="btn-print" onclick="window.print()">Print / Save Report as PDF</button>
  </div>

  <div class="report-sheet">
    <div class="report-header">
      <div>
        <h1>Majestic Spring Water Refilling Station</h1>
        <p>Daily Sales, Barangay Order Distribution &amp; End-of-Day Cash Remittance Audit Report</p>
        <p><strong>Covered Area:</strong> Barangays 22, 24, 26, 27 &amp; 35, Caloocan City</p>
      </div>
      <div class="meta-box">
        <div><strong>Report Date:</strong> <?php echo htmlspecialchars($dateLabel); ?></div>
        <div><strong>Generated By:</strong> <?php echo htmlspecialchars($currentAdminUser); ?></div>
        <div><strong>Generated At:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A')); ?></div>
      </div>
    </div>

    <!-- 5 Top KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <span>Total Audited Cash</span>
        <strong>P<?php echo number_format($totalAuditedCash, 2); ?></strong>
        <small><?php echo $paidCount; ?> Paid / Remitted Order(s)</small>
      </div>
      <div class="kpi-card">
        <span>Walk-In Cash Log</span>
        <strong>P<?php echo number_format($walkInCash, 2); ?></strong>
        <small><?php echo $walkInContainers; ?> Walk-In Container(s)</small>
      </div>
      <div class="kpi-card">
        <span>Rider Cash Remitted</span>
        <strong>P<?php echo number_format($riderCash, 2); ?></strong>
        <small><?php echo $riderOrders; ?> Delivery Order(s)</small>
      </div>
      <div class="kpi-card">
        <span>Unpaid / Utang Flagged</span>
        <strong class="unpaid-amount-text">P<?php echo number_format($unpaidReceivables, 2); ?></strong>
        <small><?php echo $unpaidCount; ?> Unpaid Order(s)</small>
      </div>
      <div class="kpi-card">
        <span>Gallon Output</span>
        <strong><?php echo ($refillGallons + $newContainersCount); ?> Gal</strong>
        <small><?php echo $refillGallons; ?> Refill | <?php echo $newContainersCount; ?> New Container</small>
      </div>
    </div>

    <!--Primary Visualizations -->
    <div class="charts-grid">
      <div class="chart-box">
        <h3>1. Daily Sales Chart by 2-Hour Time Batch (Chart.js — Paid Cash vs. Unpaid/Utang)</h3>
        <div class="report-canvas-wrap">
          <canvas id="reportBatchBarChart"></canvas>
        </div>
      </div>

      <div class="chart-box">
        <h3>2. Orders &amp; Revenue Distribution by Barangay (Chart.js)</h3>
        <div class="pie-layout">
          <div class="pie-canvas-wrap">
            <canvas id="reportBrgyPieChart"></canvas>
          </div>
          <div class="legend-list">
            <?php foreach ($brgyList as $b):
                $pct = $totalBrgyOrds > 0 ? (int)round(($b['orders'] / $totalBrgyOrds) * 100) : 0;
            ?>
              <div class="legend-row">
                <span class="legend-dot" style="background:<?php echo htmlspecialchars($b['color']); ?>;"></span>
                <span class="legend-name"><?php echo htmlspecialchars($b['label']); ?></span>
                <strong class="legend-val">
                  <?php echo $b['orders']; ?> ord (<?php echo $pct; ?>%) — P<?php echo number_format($b['sales'], 2); ?>
                </strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Service Mode & Order Channel Breakdowns -->
    <div class="secondary-charts-grid">
      <div class="chart-box">
        <h3>3. Breakdown by Service Mode (Pickup/Delivery, Walk-In/Pickup, Walk-In/Delivery)</h3>
        <?php foreach ($modeStats as $mName => $mVal):
            $mPct = (int)round(($mVal['count'] / $totalDayOrders) * 100);
        ?>
          <div class="dist-item">
            <div class="dist-top">
              <span><strong><?php echo htmlspecialchars($mName); ?></strong> (<?php echo $mVal['count']; ?> order<?php echo $mVal['count'] === 1 ? '' : 's'; ?>)</span>
              <strong>P<?php echo number_format($mVal['amount'], 2); ?> (<?php echo $mPct; ?>%)</strong>
            </div>
            <div class="dist-track">
              <div class="dist-fill fill-blue" style="width:<?php echo $mPct; ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="chart-box">
        <h3>4. Breakdown by Order Channel (Online Portal vs. User/Admin Encoded)</h3>
        <?php foreach ($channelStats as $cName => $cVal):
            $cPct = (int)round(($cVal['count'] / $totalDayOrders) * 100);
        ?>
          <div class="dist-item">
            <div class="dist-top">
              <span><strong><?php echo htmlspecialchars($cName); ?></strong> (<?php echo $cVal['count']; ?> order<?php echo $cVal['count'] === 1 ? '' : 's'; ?>)</span>
              <strong>P<?php echo number_format($cVal['amount'], 2); ?> (<?php echo $cPct; ?>%)</strong>
            </div>
            <div class="dist-track">
              <div class="dist-fill fill-teal" style="width:<?php echo $cPct; ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Itemized Order & Cash Payment Audit Table -->
    <div class="records-section">
      <h3>5. End-of-Day Itemized Order &amp; Cash Payment Audit Log (<?php echo count($reportRecords); ?> Record<?php echo count($reportRecords) === 1 ? '' : 's'; ?>)</h3>
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer &amp; Tag</th>
            <th>Brgy</th>
            <th>Batch Slot</th>
            <th>Service Mode</th>
            <th>Order Channel</th>
            <th>Order Item</th>
            <th>Total</th>
            <th>Payment Status &amp; Recorded By</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($reportRecords)): ?>
            <tr>
              <td colspan="9" class="empty-table-cell">
                No orders recorded for <?php echo htmlspecialchars($dateLabel); ?>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($reportRecords as $rec):
                $isUnpaidRow = ($rec['is_utang'] || $rec['payment_status'] === 'Unpaid');
            ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($rec['order_id']); ?></strong></td>
                <td>
                  <?php echo htmlspecialchars($rec['customer_tag']); ?><br />
                  <small><?php echo htmlspecialchars($rec['customer_name']); ?></small>
                </td>
                <td>Brgy <?php echo htmlspecialchars($rec['barangay_num']); ?></td>
                <td><?php echo htmlspecialchars($rec['slot_label']); ?></td>
                <td><?php echo htmlspecialchars($rec['service_mode']); ?></td>
                <td><?php echo htmlspecialchars($rec['order_channel']); ?></td>
                <td><?php echo htmlspecialchars($rec['order_summary']); ?></td>
                <td><strong>P<?php echo number_format($rec['total_price'], 2); ?></strong></td>
                <td>
                  <span class="badge <?php echo $isUnpaidRow ? 'badge-unpaid' : 'badge-paid'; ?>">
                    <?php echo htmlspecialchars($rec['is_utang'] ? 'Unpaid (Utang)' : $rec['payment_status']); ?>
                  </span>
                  <div class="recorded-by-sub">By: <?php echo htmlspecialchars($rec['recorded_by']); ?></div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="signoff-row">
      <div class="sign-line">Prepared &amp; Audited By: <?php echo htmlspecialchars($currentAdminUser); ?></div>
      <div class="sign-line">Verified &amp; Approved By: Station Manager / Owner</div>
    </div>
  </div>

  <script>
    (function () {
      const cfg = <?php echo $chartConfigJson; ?>;
      if (!window.Chart) return;

      new Chart(document.getElementById('reportBatchBarChart'), {
        type: 'bar',
        data: {
          labels: cfg.batchLabels,
          datasets: [
            {
              label: 'Paid Cash (P)',
              data: cfg.paidData,
              backgroundColor: '#0284c7',
              borderColor: '#0369a1',
              borderWidth: 1.5,
              borderRadius: 4
            },
            {
              label: 'Unpaid / Utang (P)',
              data: cfg.unpaidData,
              backgroundColor: '#f59e0b',
              borderColor: '#d97706',
              borderWidth: 1.5,
              borderRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: false,
          plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11, weight: '700' } } }
          },
          scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, beginAtZero: true, ticks: { callback: function (v) { return 'P' + v; } } }
          }
        }
      });

      const hasBrgyOrders = cfg.brgyOrders.some(function (v) { return v > 0; });
      new Chart(document.getElementById('reportBrgyPieChart'), {
        type: 'doughnut',
        data: {
          labels: hasBrgyOrders ? cfg.brgyLabels : ['No Orders'],
          datasets: [{
            data: hasBrgyOrders ? cfg.brgyOrders : [1],
            backgroundColor: hasBrgyOrders ? cfg.brgyColors : ['#e2e8f0'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: false,
          plugins: { legend: { display: false } },
          cutout: '52%'
        }
      });
    })();
  </script>
</body>
</html>
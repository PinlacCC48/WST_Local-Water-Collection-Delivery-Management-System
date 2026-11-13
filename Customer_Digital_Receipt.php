<?php
/*=================================================
 * Customer_Digital_Receipt.php
 * Standalone  Window for Customer Digital Receipt
 ==================================================*/
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
    die('Database Connection Failed: ' . htmlspecialchars($e->getMessage()));
}
if (empty($_SESSION['customer_id']) && empty($_SESSION['employee_id'])) {
    header('Location: Login.php');
    exit;
}
$customerId = (string)($_SESSION['user']['customer_id'] ?? $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? '');
$orderIdReq = trim((string)($_GET['order_id'] ?? ''));

$sql = "
    SELECT
        o.order_id,
        o.customer_id,
        o.slot_id,
        o.status_id,
        o.service_mode,
        o.inspection_status,
        o.delay_hold_notice,
        o.additional_notes,
        o.total_price,
        o.date_ordered,
        os.status_name,
        ts.slot_label,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
        c.container_label,
        c.barangay,
        CONCAT(e.first_name, ' ', e.last_name) AS rider_name,
        oi.product_id,
        oi.order_type,
        oi.unit_price,
        oi.quantity,
        oi.subtotal,
        cp.container_type,
        cp.refill_price,
        cp.new_container_price,
        COALESCE(p.payment_method, 'Cash Only') AS payment_method,
        COALESCE(p.payment_status, 'Unpaid') AS payment_status
    FROM order_information o
    INNER JOIN customer c ON c.customer_id = o.customer_id
    LEFT JOIN order_status os ON os.status_id = o.status_id
    LEFT JOIN time_slot ts ON ts.slot_id = o.slot_id
    LEFT JOIN employee e ON e.employee_id = o.employee_id
    LEFT JOIN order_item oi ON oi.order_id = o.order_id
    LEFT JOIN container_product cp ON cp.product_id = oi.product_id
    LEFT JOIN payment p ON p.order_id = o.order_id
";

$ord = null;
if ($orderIdReq !== '') {
    if ($customerId !== '' && strpos($customerId, 'CUS-') === 0) {
        $stmt = $pdo->prepare($sql . " WHERE o.order_id = :oid AND o.customer_id = :cid LIMIT 1");
        $stmt->execute([':oid' => $orderIdReq, ':cid' => $customerId]);
        $ord = $stmt->fetch();
    }
    if (!$ord) {
        $stmt = $pdo->prepare($sql . " WHERE o.order_id = :oid LIMIT 1");
        $stmt->execute([':oid' => $orderIdReq]);
        $ord = $stmt->fetch();
    }
}

if (!$ord && $customerId !== '') {
    $stmt = $pdo->prepare($sql . " WHERE o.customer_id = :cid ORDER BY o.date_ordered DESC, o.order_id DESC LIMIT 1");
    $stmt->execute([':cid' => $customerId]);
    $ord = $stmt->fetch();
}

if (!$ord) {
    $ord = $pdo->query($sql . " ORDER BY o.date_ordered DESC, o.order_id DESC LIMIT 1")->fetch();
}

$orderId       = (string)($ord['order_id'] ?? 'ORD-0000');
$statusName    = (string)($ord['status_name'] ?? 'Queued');
$slotLabel     = (string)($ord['slot_label'] ?? '8:00 AM - 10:00 AM');
$serviceMode   = (string)($ord['service_mode'] ?? 'Pickup/Delivery');
$customerName  = (string)($ord['customer_name'] ?? 'Customer');
$containerLbl  = (string)($ord['container_label'] ?? 'Household');
$brgyNum       = preg_replace('/[^0-9]/', '', (string)($ord['barangay'] ?? '22')) ?: '22';
$brgyRoute     = 'Brgy ' . $brgyNum;
$orderType     = (string)($ord['order_type'] ?? 'Refill Only');
$containerType = (string)($ord['container_type'] ?? '5-Gal - Slim');
$qty           = max(1, (int)($ord['quantity'] ?? 1));
$totalPrice    = (float)($ord['total_price'] ?? 30.00);
$refillRate    = (float)($ord['refill_price'] ?? 30.00);
$newContRate   = (float)($ord['new_container_price'] ?? 250.00);
$paymentMethod = (string)($ord['payment_method'] ?? 'Cash Only');
$paymentStatus = (string)($ord['payment_status'] ?? 'Unpaid');
$dateOrdered   = !empty($ord['date_ordered']) ? date('M j, Y g:i A', strtotime((string)$ord['date_ordered'])) : date('M j, Y');

$isNewContainerReceipt = (stripos($orderType, 'New Container') !== false);
$includesInitialRefill = (stripos($orderType, 'Refill') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital Receipt — <?php echo htmlspecialchars($orderId); ?></title>
  <link rel="stylesheet" href="Customer_Digital_Receipt_Style.css?v=2" />
</head>
<body>

  <main class="receipt-popup-shell">
    <section class="digital-receipt-card">
      <div class="receipt-card-header">
        <h2>Digital Receipt</h2>
        <span class="receipt-type-tag">
          <?php echo $isNewContainerReceipt ? 'New Container Order' : 'Water Refill Order'; ?>
        </span>
      </div>

      <div class="receipt-card-body">
        <div class="station-title-block">
          <h3>Majestic Spring Water Station</h3>
          <p class="receipt-meta-sub">
            Order <strong><?php echo htmlspecialchars($orderId); ?></strong> &bull;
            <?php echo htmlspecialchars($dateOrdered); ?> &bull;
            <span class="receipt-status-pill"><?php echo htmlspecialchars($statusName); ?></span>
          </p>
        </div>

        <div class="receipt-inner-box">
          <?php if ($isNewContainerReceipt): ?>
            <!--NEW CONTAINER DIGITAL RECEIPT LAYOUT  -->
            <div class="receipt-line">
              <span class="receipt-label">Item Selected:</span>
              <strong class="receipt-value">
                <?php echo $qty . 'x New ' . htmlspecialchars($containerType) . ' Container'; ?>
              </strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">New Container Price:</span>
              <strong class="receipt-value">
                P<?php echo number_format($newContRate * $qty, 2); ?>
              </strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Water Refill:</span>
              <strong class="receipt-value">
                <?php echo $includesInitialRefill ? '+P' . number_format($refillRate * $qty, 2) : 'P0.00 (Container Only)'; ?>
              </strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Service Mode:</span>
              <strong class="receipt-value">
                <?php echo htmlspecialchars($serviceMode === 'Walk-In/Delivery' ? 'Delivery' : $serviceMode); ?>
              </strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Assigned Delivery Slot:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($slotLabel); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Barangay Route:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($brgyRoute); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Customer Name:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($customerName); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Container Label:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($containerLbl); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Payment Method:</span>
              <strong class="receipt-value">
                <?php echo htmlspecialchars($paymentMethod); ?> (<?php echo htmlspecialchars($paymentStatus); ?>)
              </strong>
            </div>

          <?php else: ?>
            <!-- WATER REFILL DIGITAL RECEIPT LAYOUT -->
            <div class="receipt-line">
              <span class="receipt-label">Selected Batch Slot:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($slotLabel); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Service Mode:</span>
              <strong class="receipt-value">
                <?php echo htmlspecialchars($serviceMode === 'Pickup/Delivery' ? 'Pickup & Delivery' : $serviceMode); ?>
              </strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Barangay Route:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($brgyRoute); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Customer Name:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($customerName); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Container Label:</span>
              <strong class="receipt-value"><?php echo htmlspecialchars($containerLbl); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Container Selected:</span>
              <strong class="receipt-value">
                <?php echo $qty . 'x ' . htmlspecialchars(str_replace(' - ', ' ', $containerType)) . ' Refill'; ?>
              </strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Refill Rate (1 Container):</span>
              <strong class="receipt-value">P<?php echo number_format($refillRate, 2); ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Quantity:</span>
              <strong class="receipt-value"><?php echo $qty; ?></strong>
            </div>

            <div class="receipt-line">
              <span class="receipt-label">Payment Method:</span>
              <strong class="receipt-value">
                <?php echo htmlspecialchars($paymentMethod); ?> (<?php echo htmlspecialchars($paymentStatus); ?>)
              </strong>
            </div>
          <?php endif; ?>

          <div class="receipt-divider"></div>

          <div class="receipt-total-row">
            <span class="receipt-total-label">TOTAL CASH PAYABLE:</span>
            <strong class="receipt-total-amount">P<?php echo number_format($totalPrice, 2); ?></strong>
          </div>
        </div>

        <div class="receipt-actions-row">
          <button type="button" class="btn-receipt-close" onclick="window.close();">
            <span>Close Receipt</span>
            <span class="btn-arrow">&#10148;</span>
          </button>
        </div>
      </div>
    </section>
  </main>

  <script>
    try {
      if (localStorage.getItem('majestic_theme') === 'dark') {
        document.body.classList.add('dark-mode');
      }
    } catch (e) {}
  </script>
</body>
</html>
<?php
/*======================
 Database Connectionfor 
 Customer_Dashboard.php
 ========================*/
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
    die("Database Connection Failed: " . htmlspecialchars($e->getMessage()));
}

//  Logged-In Customer Resolution & Active Status Guard
$customer_id = $_SESSION['user']['customer_id'] ?? $_SESSION['customer_id'] ?? null;
if (!$customer_id && !empty($_SESSION['user_id']) && strpos((string)$_SESSION['user_id'], 'CUS-') === 0) {
    $customer_id = (string)$_SESSION['user_id'];
}

$custRow = null;
if ($customer_id) {
    $stmtCust = $pdo->prepare("SELECT * FROM customer WHERE customer_id = ? LIMIT 1");
    $stmtCust->execute([$customer_id]);
    $custRow = $stmtCust->fetch();
}


if (!$custRow) {
    header("Location: Login.php");
    exit;
}

if (strcasecmp(trim((string)($custRow['cust_status'] ?? 'Active')), 'Inactive') === 0) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=inactive");
    exit;
}

$customer_id = (string)$custRow['customer_id'];
$_SESSION['customer_id'] = $customer_id;
$_SESSION['user_id']     = $customer_id;
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['user'] = [];
}
$_SESSION['user']['customer_id'] = $customer_id;

$flashData = $_SESSION['cust_dashboard_flash'] ?? null;
unset($_SESSION['cust_dashboard_flash']);

// 1-Click Repeat Usual Refill Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'repeat_usual_refill') {
    $slotInput = max(1, min(5, (int)($_POST['slot_id'] ?? 1)));
    $prodInput = trim((string)($_POST['product_id'] ?? 'PRD-01'));

    $stmtSlot = $pdo->prepare("SELECT * FROM time_slot WHERE slot_id = ? LIMIT 1");
    $stmtSlot->execute([$slotInput]);
    $chosenSlot = $stmtSlot->fetch() ?: $pdo->query("SELECT * FROM time_slot ORDER BY slot_id ASC LIMIT 1")->fetch();

    if ($chosenSlot) {
        $actualSlotId = (int)$chosenSlot['slot_id'];
        $cap          = (int)$chosenSlot['batch_capacity'];
        $stmtUsed     = $pdo->prepare("
            SELECT COALESCE(SUM(oi.quantity), 0)
            FROM order_information o
            JOIN order_item oi ON o.order_id = oi.order_id
            WHERE o.slot_id = ? AND DATE(o.date_ordered) = CURDATE() AND o.status_id != 7
        ");
        $stmtUsed->execute([$actualSlotId]);
        $usedCount = (int)$stmtUsed->fetchColumn();

        if (strcasecmp((string)$chosenSlot['slot_status'], 'Closed') === 0 || $usedCount + 1 > $cap) {
            $_SESSION['cust_dashboard_flash'] = [
                'type'    => 'error',
                'message' => "Selected batch slot ({$chosenSlot['slot_label']}) is full or closed. Please choose another batch window."
            ];
        } else {
            $stmtProd = $pdo->prepare("SELECT * FROM container_product WHERE product_id = ? LIMIT 1");
            $stmtProd->execute([$prodInput]);
            $prodRow = $stmtProd->fetch() ?: $pdo->query("SELECT * FROM container_product ORDER BY product_id ASC LIMIT 1")->fetch();

            $actualProdId = (string)$prodRow['product_id'];
            $refillPrice  = (float)($prodRow['refill_price'] ?? 30.00);

            $maxOrd = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(order_id, 5) AS UNSIGNED)), 0) FROM order_information WHERE order_id LIKE 'ORD-%'")->fetchColumn();
            $nextOrdId = sprintf('ORD-%04d', $maxOrd + 1);

            $maxPay = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(payment_id, 5) AS UNSIGNED)), 0) FROM payment WHERE payment_id LIKE 'PAY-%'")->fetchColumn();
            $nextPayId = sprintf('PAY-%04d', $maxPay + 1);

            $defaultRider = $pdo->query("
                SELECT employee_id FROM employee
                WHERE emp_status = 'Active'
                ORDER BY CASE WHEN emp_role LIKE '%Rider%' THEN 0 ELSE 1 END ASC, employee_id ASC
                LIMIT 1
            ")->fetchColumn() ?: null;

            $pdo->beginTransaction();
            $stmtInsOrd = $pdo->prepare("
                INSERT INTO order_information (
                    order_id, customer_id, employee_id, slot_id, status_id,
                    order_channel, service_mode, inspection_status, delay_hold_notice,
                    additional_notes, total_price, date_ordered
                ) VALUES (?, ?, ?, ?, 1, 'Online Portal', 'Pickup/Delivery', 'Good', 'None', ?, ?, NOW())
            ");
            $stmtInsOrd->execute([
                $nextOrdId,
                $customer_id,
                $defaultRider,
                $actualSlotId,
                '1-Click Repeat Usual Refill submitted from Customer Dashboard.',
                $refillPrice
            ]);

            $stmtInsItem = $pdo->prepare("
                INSERT INTO order_item (order_id, product_id, order_type, unit_price, quantity, subtotal)
                VALUES (?, ?, 'Refill Only', ?, 1, ?)
            ");
            $stmtInsItem->execute([$nextOrdId, $actualProdId, $refillPrice, $refillPrice]);

            $stmtInsPay = $pdo->prepare("
                INSERT INTO payment (payment_id, order_id, payment_method, total_paid, recorded_by, payment_notes, date_paid, payment_status)
                VALUES (?, ?, 'Cash Only', 0.00, 'Online Portal', 'Cash on delivery', NULL, 'Unpaid')
            ");
            $stmtInsPay->execute([$nextPayId, $nextOrdId]);
            $pdo->commit();

            $_SESSION['cust_dashboard_flash'] = [
                'type'    => 'success',
                'message' => "Order {$nextOrdId} (1x {$prodRow['container_type']} Refill — P" . number_format($refillPrice, 2) . ") queued for {$chosenSlot['slot_label']}!"
            ];
        }
    }
    header("Location: Customer_Dashboard.php");
    exit;
}

// Dashboard Payload for Customer_Dashboard_Script.js
$brgyNum   = preg_replace('/[^0-9]/', '', (string)($custRow['barangay'] ?? '22')) ?: '22';
$brgyShort = "Brgy {$brgyNum}";
$fullName  = trim(($custRow['first_name'] ?? '') . ' ' . ($custRow['last_name'] ?? ''));

$rawSlots = $pdo->query("
    SELECT 
        ts.*,
        COALESCE(SUM(CASE WHEN DATE(o.date_ordered) = CURDATE() AND o.status_id != 7 THEN oi.quantity ELSE 0 END), 0) AS used_qty
    FROM time_slot ts
    LEFT JOIN order_information o ON ts.slot_id = o.slot_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    GROUP BY ts.slot_id
    ORDER BY ts.time_from ASC, ts.slot_id ASC
")->fetchAll();

$slotsPayload = [];
$recommendedSlotIdx = 1;
$nowTime = date('H:i:s');

foreach ($rawSlots as $idx => $s) {
    $num  = $idx + 1;
    $used = (int)$s['used_qty'];
    $cap  = (int)$s['batch_capacity'];
    $st   = (string)($s['slot_status'] ?: 'Open');
    if (strcasecmp($st, 'Closed') === 0 || $nowTime >= $s['time_to']) {
        $st = 'Closed';
    } elseif ($used >= $cap || strcasecmp($st, 'Full') === 0) {
        $st = 'Full';
    } elseif ($st === 'Open') {
        $st = 'Queued';
    }
    $slotsPayload[] = [
        'slot_id'      => $num,
        'db_slot_id'   => $s['slot_id'],
        'slot_label'   => $s['slot_label'],
        'used'         => $used,
        'capacity'     => $cap,
        'slot_status'  => $st,
        'pill_subtext' => "{$used}/{$cap} Containers | {$st}"
    ];
    if ($nowTime >= $s['time_from'] && $nowTime < $s['time_to']) {
        $recommendedSlotIdx = $num;
    }
}

$stmtActive = $pdo->prepare("
    SELECT 
        o.*,
        os.status_name,
        ts.slot_label,
        CONCAT(e.first_name, ' ', e.last_name) AS rider_name,
        oi.product_id,
        oi.order_type,
        oi.quantity,
        cp.container_type,
        COALESCE(p.payment_status, 'Unpaid') AS payment_status
    FROM order_information o
    LEFT JOIN order_status os ON o.status_id = os.status_id
    LEFT JOIN time_slot ts ON o.slot_id = ts.slot_id
    LEFT JOIN employee e ON o.employee_id = e.employee_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    LEFT JOIN container_product cp ON oi.product_id = cp.product_id
    LEFT JOIN payment p ON o.order_id = p.order_id
    WHERE o.customer_id = ?
    ORDER BY CASE WHEN o.status_id NOT IN (5, 7) THEN 0 ELSE 1 END ASC, o.date_ordered DESC, o.order_id DESC
    LIMIT 1
");
$stmtActive->execute([$customer_id]);
$actRow = $stmtActive->fetch();

$activeOrderPayload = null;
$usualRefillPayload = [
    'product_id'    => 'PRD-01',
    'product_label' => '5-Gal Slim',
    'price'         => '30.00'
];

if ($actRow) {
    $qty   = (int)($actRow['quantity'] ?: 1);
    $cType = (string)($actRow['container_type'] ?: '5-Gal Slim');
    if (stripos($cType, '5-Gal') === false) {
        $cType = "5-Gal - {$cType}";
    }
    $oType = (string)($actRow['order_type'] ?: 'Refill');
    $activeOrderPayload = [
        'order_id'          => $actRow['order_id'],
        'status_id'         => (int)$actRow['status_id'],
        'status_name'       => $actRow['status_name'] ?: 'Queued',
        'slot_label'        => $actRow['slot_label'] ?: '8:00 AM - 10:00 AM',
        'rider_name'        => $actRow['rider_name'] ?: 'Pending Assignment',
        'total_price'       => number_format((float)$actRow['total_price'], 2, '.', ''),
        'is_utang'          => (strcasecmp((string)$actRow['payment_status'], 'Unpaid') === 0),
        'item_summary'      => "{$qty}x {$cType} ({$oType})",
        'inspection_status' => $actRow['inspection_status'] ?: 'Good',
        'delay_hold_notice' => $actRow['delay_hold_notice'] ?: 'None'
    ];
    if (!empty($actRow['product_id'])) {
        $usualRefillPayload['product_id']    = (string)$actRow['product_id'];
        $usualRefillPayload['product_label'] = (stripos($cType, 'Round') !== false) ? '5-Gal Round' : '5-Gal Slim';
    }
}

// damaged container inspection alert banner
if (!$flashData && $activeOrderPayload) {
    $inspCheck = (string)($activeOrderPayload['inspection_status'] ?? '');
    if (preg_match('/damaged|leak|crack|replace|broken|fail/i', $inspCheck)) {
        $flashData = [
            'type'    => 'error',
            'message' => "Container Inspection Alert ({$activeOrderPayload['order_id']}): Your container inspection turned up as \"{$inspCheck}\". Please contact Majestic Spring via majesticspring@gmail.com or call/text 098767892 for further details (or order a brand-new P250.00 replacement container below)."
        ];
    }
}

$dashboardPayloadJson = json_encode([
    'customer' => [
        'customer_id'     => $customer_id,
        'full_name'       => $fullName,
        'container_label' => $custRow['container_label'] ?: $custRow['first_name'],
        'barangay_short'  => $brgyShort
    ],
    'active_order'     => $activeOrderPayload,
    'slots'            => $slotsPayload,
    'usual_refill'     => $usualRefillPayload,
    'recommended_slot' => $recommendedSlotIdx,
    'flash'            => $flashData
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// FETCH API for Live Customer Dashboard Data
if (isset($_GET['ajax_dashboard']) && $_GET['ajax_dashboard'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo $dashboardPayloadJson;
    exit;
}

ob_start(function ($html) use ($dashboardPayloadJson) {
    $html = preg_replace('/^\s*\?>\s*/', '', $html);
    $scriptTag = "<script>window.MAJESTIC_CUSTOMER_DASHBOARD_DATA = {$dashboardPayloadJson};</script>\n";
    if (stripos($html, '</head>') !== false) {
        return str_ireplace('</head>', $scriptTag . '</head>', $html);
    }
    return $scriptTag . $html;
});
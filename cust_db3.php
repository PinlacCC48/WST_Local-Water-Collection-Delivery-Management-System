<?php
/*==========================
 * Database Connection for 
 * iew_Order_Status.php
 ============================*/
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

// Logged-In Customer Account Resolution & Active Account Guard
$customer_id = $_SESSION['user']['customer_id'] ?? $_SESSION['customer_id'] ?? null;
if (!$customer_id && !empty($_SESSION['user_id']) && strpos((string)$_SESSION['user_id'], 'CUS-') === 0) {
    $customer_id = (string)$_SESSION['user_id'];
}

$customerProfile = null;
if ($customer_id) {
    $stmtCust = $pdo->prepare("SELECT * FROM customer WHERE customer_id = ? LIMIT 1");
    $stmtCust->execute([$customer_id]);
    $customerProfile = $stmtCust->fetch();
}



if (!$customerProfile) {
    header("Location: Login.php");
    exit;
}

if (strcasecmp(trim((string)($customerProfile['cust_status'] ?? 'Active')), 'Inactive') === 0) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=inactive");
    exit;
}

$customer_id = (string)$customerProfile['customer_id'];
$_SESSION['customer_id'] = $customer_id;
$_SESSION['user_id']     = $customer_id;
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['user'] = [];
}
$_SESSION['user']['customer_id'] = $customer_id;

$flashData = $_SESSION['view_status_flash'] ?? null;
unset($_SESSION['view_status_flash']);

// Cancel Queued Order Action 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_queued_order') {
    $orderToCancel = trim((string)($_POST['order_id'] ?? ''));
    if ($orderToCancel !== '') {
        $stmtChk = $pdo->prepare("
            SELECT o.status_id, oi.product_id, oi.order_type, oi.quantity
            FROM order_information o
            LEFT JOIN order_item oi ON oi.order_id = o.order_id
            WHERE o.order_id = ? AND o.customer_id = ?
            LIMIT 1
        ");
        $stmtChk->execute([$orderToCancel, $customer_id]);
        $ordRow = $stmtChk->fetch();

        if (!$ordRow) {
            $_SESSION['view_status_flash'] = [
                'type'    => 'error',
                'message' => "Order {$orderToCancel} not found or does not belong to your account."
            ];
        } elseif ((int)$ordRow['status_id'] !== 1) {
            $_SESSION['view_status_flash'] = [
                'type'    => 'error',
                'message' => "Order {$orderToCancel} cannot be cancelled because processing/rider pickup has already started."
            ];
        } else {
            $cancelledStatusId = (int)($pdo->query("SELECT status_id FROM order_status WHERE status_name LIKE '%Cancel%' LIMIT 1")->fetchColumn() ?: 7);

            $pdo->beginTransaction();
            $stmtCancel = $pdo->prepare("UPDATE order_information SET status_id = ? WHERE order_id = ? AND customer_id = ?");
            $stmtCancel->execute([$cancelledStatusId, $orderToCancel, $customer_id]);

            // Restore available_stock if cancelled order included a brand-new container
            if (
                stripos((string)($ordRow['order_type'] ?? ''), 'New Container') !== false &&
                !empty($ordRow['product_id'])
            ) {
                $restoreQty = max(1, (int)$ordRow['quantity']);
                $updStock   = $pdo->prepare("
                    UPDATE container_product
                    SET available_stock = available_stock + ?,
                        availability_status = 'Available'
                    WHERE product_id = ?
                ");
                $updStock->execute([$restoreQty, (string)$ordRow['product_id']]);
            }
            $pdo->commit();

            $_SESSION['view_status_flash'] = [
                'type'    => 'success',
                'message' => "Order {$orderToCancel} has been successfully cancelled."
            ];
        }
    }
    header("Location: View_Order_Status.php");
    exit;
}

// Load Customer Orders History
$fullName = trim(($customerProfile['first_name'] ?? '') . ' ' . ($customerProfile['last_name'] ?? ''));
$cLabel   = (string)($customerProfile['container_label'] ?: $customerProfile['first_name']);
$gateNote = (string)($customerProfile['gate_rider_note'] ?: 'None');

$stmtOrders = $pdo->prepare("
    SELECT 
        o.order_id,
        o.customer_id,
        o.employee_id,
        o.slot_id,
        o.status_id,
        o.order_channel,
        o.service_mode,
        o.inspection_status,
        o.delay_hold_notice,
        o.additional_notes,
        o.total_price,
        o.date_ordered,
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
    ORDER BY o.date_ordered DESC, o.order_id DESC
");
$stmtOrders->execute([$customer_id]);
$allOrders = $stmtOrders->fetchAll();

$historyPayload     = [];
$activeOrderPayload = null;

foreach ($allOrders as $ord) {
    $stId   = (int)$ord['status_id'];
    $stName = (string)($ord['status_name'] ?: 'Queued');
    $qty    = (int)($ord['quantity'] ?: 1);
    $cType  = (string)($ord['container_type'] ?: '5-Gal Slim');
    if (stripos($cType, '5-Gal') === false) {
        $cType = "5-Gal - {$cType}";
    }
    $oType   = (string)($ord['order_type'] ?: 'Refill');
    $summary = "{$qty}x {$cType} ({$oType})";

    $category = 'Active';
    if ($stId === 5) {
        $category = 'Completed';
    } elseif ($stId === 6) {
        $category = 'Rescheduled';
    } elseif ($stId === 7) {
        $category = 'Cancelled';
    }

    $itemPayload = [
        'order_id'          => $ord['order_id'],
        'status_id'         => $stId,
        'status'            => $stName,
        'filter_category'   => $category,
        'date_ordered'      => date('M j, Y', strtotime((string)$ord['date_ordered'])),
        'batch_slot'        => $ord['slot_label'] ?: '8:00 AM - 10:00 AM',
        'slot_full_label'   => $ord['slot_label'] ?: '8:00 AM - 10:00 AM',
        'container_label'   => $cLabel,
        'service_mode'      => $ord['service_mode'] ?: 'Pickup/Delivery',
        'order_summary'     => $summary,
        'total_price'       => number_format((float)$ord['total_price'], 2, '.', ''),
        'rider_name'        => $ord['rider_name'] ?: 'Pending Assignment',
        'is_utang'          => (strcasecmp((string)$ord['payment_status'], 'Unpaid') === 0),
        'inspection_status' => $ord['inspection_status'] ?: 'Good',
        'delay_hold_notice' => $ord['delay_hold_notice'] ?: 'None',
        'gate_rider_note'   => $gateNote
    ];

    $historyPayload[] = $itemPayload;

    if ($activeOrderPayload === null && !in_array($stId, [5, 7], true)) {
        $activeOrderPayload = $itemPayload;
    }
}

if ($activeOrderPayload === null && !empty($historyPayload)) {
    $activeOrderPayload = $historyPayload[0];
}

// Surface damaged container inspection alert in flash banner 
if (!$flashData && $activeOrderPayload) {
    $inspCheck = (string)($activeOrderPayload['inspection_status'] ?? '');
    if (preg_match('/damaged|leak|crack|replace|broken|fail/i', $inspCheck)) {
        $flashData = [
            'type'    => 'error',
            'message' => "Container Inspection Alert ({$activeOrderPayload['order_id']}): Your container inspection turned up as \"{$inspCheck}\". Please contact Majestic Spring via majesticspring@gmail.com or call/text 098767892 for further details."
        ];
    }
}

$viewStatusJson = json_encode([
    'customer' => [
        'customer_id'     => $customer_id,
        'full_name'       => $fullName,
        'container_label' => $cLabel,
        'gate_rider_note' => $gateNote
    ],
    'active_order' => $activeOrderPayload,
    'history'      => $historyPayload,
    'flash'        => $flashData
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// FETCH API ForLive Order Status & History 
if (isset($_GET['ajax_status']) && $_GET['ajax_status'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo $viewStatusJson;
    exit;
}

ob_start(function ($html) use ($viewStatusJson) {
    $html = preg_replace('/^\s*\?>\s*/', '', $html);
    $scriptTag = "<script>window.MAJESTIC_VIEW_STATUS_DATA = {$viewStatusJson};</script>\n";
    if (stripos($html, '</head>') !== false) {
        return str_ireplace('</head>', $scriptTag . '</head>', $html);
    }
    return $scriptTag . $html;
});
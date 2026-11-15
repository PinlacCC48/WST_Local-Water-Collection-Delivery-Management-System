<?php
/* =========================================================================
   Database Connection for:
   1. Submit_Water_Order.php
   2. Order_New_Container.php
   ========================================================================= */
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'majestic_spring_wcdms';
$dbUser = 'root';
$dbPass = '';

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die('Database Connection Error: ' . htmlspecialchars($e->getMessage()));
}

$currentPageName = strtolower(basename($_SERVER['PHP_SELF'] ?? ''));

/* =========   CSRF PROTECTION (all POST forms on these pages)   ========= */
require_once __DIR__ . '/csrf_helper.php';
majesticCsrfCheck();
$majesticCsrf = majesticCsrfToken();
if (!isset($_GET['ajax_slots'])) {
    ob_start(static fn(string $html): string => majesticCsrfInject($html, $majesticCsrf));
}

/* ========Generator for new Primary key ORD-0000 ============ */
function getNextCustomerRecordId(PDO $pdo, string $table, string $column, string $prefix): string {
    $startPos = strlen($prefix) + 1;
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING({$column}, {$startPos}) AS UNSIGNED)), 0)
        FROM {$table}
        WHERE {$column} LIKE :pfx
    ");
    $stmt->execute([':pfx' => $prefix . '%']);
    $maxNum = (int)$stmt->fetchColumn();
    return sprintf('%s%04d', $prefix, $maxNum + 1);
}

/* ========GET active logged in customer profile =========== */
function getActiveCustomerProfile(PDO $pdo): array {
    $sessionCustId = (string)($_SESSION['user']['customer_id'] ?? $_SESSION['customer_id'] ?? '');
    if ($sessionCustId === '' && !empty($_SESSION['user_id']) && strpos((string)$_SESSION['user_id'], 'CUS-') === 0) {
        $sessionCustId = (string)$_SESSION['user_id'];
    }

    $custRow = null;
    if ($sessionCustId !== '') {
        $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $sessionCustId]);
        $custRow = $stmt->fetch();
        if ($custRow && strcasecmp((string)$custRow['cust_status'], 'Inactive') === 0) {
            $_SESSION = [];
            session_destroy();
            header('Location: Login.php?inactive=1');
            exit;
        }
    }

    if (!$custRow) {
        header('Location: Login.php');
        exit;
    }

    $cid = (string)$custRow['customer_id'];
    $_SESSION['customer_id'] = $cid;
    $_SESSION['user_id']     = $cid;

    $phStmt = $pdo->prepare("SELECT cp_type, cp_num FROM customer_cp_num WHERE customer_id = :cid");
    $phStmt->execute([':cid' => $cid]);
    $phRows = $phStmt->fetchAll();

    $primaryPhone = '';
    $backupPhone  = '';
    foreach ($phRows as $pr) {
        if (($pr['cp_type'] ?? '') === 'Primary') {
            $primaryPhone = (string)$pr['cp_num'];
        } elseif (($pr['cp_type'] ?? '') === 'Backup') {
            $backupPhone = (string)$pr['cp_num'];
        }
    }

    $brgyRaw   = (string)$custRow['barangay'];
    $brgyNum   = preg_replace('/[^0-9]/', '', $brgyRaw) ?: '22';
    $brgyShort = 'Brgy ' . $brgyNum;

    return [
        'customer_id'     => $cid,
        'first_name'      => (string)$custRow['first_name'],
        'last_name'       => (string)$custRow['last_name'],
        'full_name'       => $custRow['first_name'] . ' ' . $custRow['last_name'],
        'container_label' => (string)$custRow['container_label'],
        'house_no'        => (string)$custRow['house_no'],
        'street_name'     => (string)$custRow['street_name'],
        'barangay'        => $brgyRaw,
        'barangay_short'  => $brgyShort,
        'barangay_num'    => $brgyNum,
        'city'            => (string)$custRow['city'],
        'address'         => $custRow['house_no'] . ' ' . $custRow['street_name'] . ', ' . $custRow['city'],
        'email_address'   => (string)$custRow['email_address'],
        'primary_phone'   => $primaryPhone,
        'backup_phone'    => $backupPhone,
        'gate_rider_note' => (string)($custRow['gate_rider_note'] ?? '')
    ];
}

/* =========Current Batch Slots Query and available queue counts========= */
function getTodayBatchSlots(PDO $pdo): array {
    $nowTime = date('H:i:s');
    $rows    = $pdo->query("
        SELECT ts.slot_id,
               ts.slot_label,
               ts.time_from,
               ts.time_to,
               ts.batch_capacity,
               ts.slot_status,
               COALESCE(SUM(
                   CASE
                       WHEN DATE(o.date_ordered) >= CURDATE() AND o.status_id != 7
                       THEN oi.quantity
                       ELSE 0
                   END
               ), 0) AS queued_containers
        FROM time_slot ts
        LEFT JOIN order_information o ON o.slot_id = ts.slot_id
        LEFT JOIN order_item oi ON oi.order_id = o.order_id
        GROUP BY ts.slot_id, ts.slot_label, ts.time_from, ts.time_to, ts.batch_capacity, ts.slot_status
        ORDER BY ts.slot_id ASC
    ")->fetchAll();

    $slots = [];
    foreach ($rows as $r) {
        $queued = (int)$r['queued_containers'];
        $cap    = (int)$r['batch_capacity'];
        $tFrom  = (string)$r['time_from'];
        $tTo    = (string)$r['time_to'];

        $isPast    = ($nowTime >= $tTo);
        $isCurrent = ($nowTime >= $tFrom && $nowTime < $tTo);
        $isFull    = ($queued >= $cap) || ((string)$r['slot_status'] === 'Full');

        if ($isFull) {
            $badgeText = 'Full';
        } elseif ($isPast) {
            $badgeText = 'Cut-Off Passed';
        } else {
            $badgeText = 'Queued';
        }

        $slots[] = [
            'slot_id'           => (int)$r['slot_id'],
            'slot_label'        => (string)$r['slot_label'],
            'time_from'         => $tFrom,
            'time_to'           => $tTo,
            'batch_capacity'    => $cap,
            'queued_containers' => $queued,
            'remaining_slots'   => max(0, $cap - $queued),
            'is_past'           => $isPast,
            'is_current'        => $isCurrent,
            'is_full'           => $isFull,
            'pill_subtext'      => "{$queued}/{$cap} Containers | {$badgeText}"
        ];
    }
    return $slots;
}

/* ======= Resolve Selected Container Product from ID, Name, or Type ======= */
function resolveSelectedContainerProduct(PDO $pdo, string $selection): array {
    $sel = trim($selection);
    if ($sel !== '') {
        $stmt = $pdo->prepare("
            SELECT * FROM container_product
            WHERE product_id = :id
               OR LOWER(container_name) = LOWER(:name)
               OR LOWER(container_type) = LOWER(:type)
            ORDER BY product_id ASC
            LIMIT 1
        ");
        $stmt->execute([
            ':id'   => $sel,
            ':name' => $sel,
            ':type' => $sel
        ]);
        $found = $stmt->fetch();
        if ($found) {
            return $found;
        }

        if (stripos($sel, 'Round') !== false) {
            $rStmt = $pdo->query("SELECT * FROM container_product WHERE product_id = 'PRD-02' OR container_type LIKE '%Round%' ORDER BY product_id ASC LIMIT 1");
            $rRow  = $rStmt->fetch();
            if ($rRow) {
                return $rRow;
            }
        }
    }

    $fallback = $pdo->query("SELECT * FROM container_product ORDER BY product_id ASC LIMIT 1")->fetch();
    return $fallback ?: [
        'product_id'          => 'PRD-01',
        'container_name'      => '5-Gallon Slim Container with Faucet',
        'container_type'      => '5-Gal - Slim',
        'capacity_gallons'    => 5,
        'refill_price'        => 30.00,
        'new_container_price' => 250.00,
        'available_stock'     => 20,
        'availability_status' => 'Available'
    ];
}

/* =======Update Customer Delivery Information ======= */
function updateCustomerDeliveryInfo(
    PDO $pdo,
    string $customerId,
    string $containerLabel,
    string $barangayRaw,
    string $addressRaw,
    string $cellphone,
    string $backupPhone,
    string $gateNote
): void {
    $brgyNum           = preg_replace('/[^0-9]/', '', $barangayRaw) ?: '22';
    $barangayFormatted = 'Barangay ' . $brgyNum;

    $cleanAddr = trim(str_ireplace(', Caloocan City', '', $addressRaw));
    if (preg_match('/^(\S+)\s+(.+)$/', $cleanAddr, $addrMatch)) {
        $hNo    = $addrMatch[1];
        $stName = $addrMatch[2];
    } else {
        $hNo    = '#1';
        $stName = $cleanAddr !== '' ? $cleanAddr : 'Sample Street';
    }

    $upd = $pdo->prepare("
        UPDATE customer
        SET container_label = :label,
            house_no = :house,
            street_name = :street,
            barangay = :brgy,
            gate_rider_note = :gate
        WHERE customer_id = :cid
    ");
    $upd->execute([
        ':label'  => $containerLabel,
        ':house'  => $hNo,
        ':street' => $stName,
        ':brgy'   => $barangayFormatted,
        ':gate'   => $gateNote !== '' ? $gateNote : null,
        ':cid'    => $customerId
    ]);

    if ($cellphone !== '') {
        $pdo->prepare("DELETE FROM customer_cp_num WHERE customer_id = :cid")->execute([':cid' => $customerId]);
        $insPh = $pdo->prepare("INSERT INTO customer_cp_num (customer_id, cp_num, cp_type) VALUES (:cid, :cp, :ctype)");
        $insPh->execute([':cid' => $customerId, ':cp' => $cellphone, ':ctype' => 'Primary']);
        if ($backupPhone !== '' && $backupPhone !== $cellphone) {
            $insPh->execute([':cid' => $customerId, ':cp' => $backupPhone, ':ctype' => 'Backup']);
        }
    }

    if (isset($_SESSION['user']) && ($_SESSION['user']['customer_id'] ?? '') === $customerId) {
        $_SESSION['user']['container_label'] = $containerLabel;
        $_SESSION['user']['house_no']        = $hNo;
        $_SESSION['user']['street_name']     = $stName;
        $_SESSION['user']['barangay']        = $barangayFormatted;
        $_SESSION['user']['address']         = $hNo . ' ' . $stName . ', Caloocan City';
        $_SESSION['user']['primary_phone']   = $cellphone;
        $_SESSION['user']['backup_phone']    = $backupPhone;
        $_SESSION['user']['gate_rider_note'] = $gateNote;
    }
}

/* =========================================================================
   PAGE 2: SUBMIT WATER REFILL ORDER (Submit_Water_Order.php)
   ========================================================================= */
$submitRefillMessage = '';
$submitRefillType    = '';

if ($currentPageName === 'submit_water_order.php') {
    $customerProfile = getActiveCustomerProfile($pdo);
    $customerId      = $customerProfile['customer_id'];

    if (isset($_GET['submitted']) && $_GET['submitted'] !== '') {
        $submitRefillMessage = 'Water Refill Order ' . htmlspecialchars((string)$_GET['submitted']) . ' submitted and queued! You can track its status on Dashboard or View Order Status.';
        $submitRefillType    = 'success';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $slotId         = max(1, min(5, (int)($_POST['slot_id'] ?? 1)));
        $containerType  = trim((string)($_POST['container_type'] ?? 'PRD-01'));
        $quantity       = max(1, (int)($_POST['quantity'] ?? 1));
        $containerLabel = trim((string)($_POST['container_label'] ?? ''));
        $barangayRaw    = trim((string)($_POST['barangay'] ?? 'Brgy 22'));
        $addressRaw     = trim((string)($_POST['address'] ?? ''));
        $cellphone      = trim((string)($_POST['cellphone'] ?? ''));
        $backupPhone    = trim((string)($_POST['backup_phone'] ?? ''));
        $gateNote       = trim((string)($_POST['gate_rider_note'] ?? ''));

        if ($containerLabel === '' || $addressRaw === '' || $cellphone === '') {
            $submitRefillMessage = 'Incomplete Booking Form: Please fill out Container Label, Address, and Cellphone #.';
            $submitRefillType    = 'error';
        } elseif (!preg_match('/^09\d{9}$/', $cellphone)) {
            $submitRefillMessage = 'Invalid Cellphone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
            $submitRefillType    = 'error';
        } elseif ($backupPhone !== '' && !preg_match('/^09\d{9}$/', $backupPhone)) {
            $submitRefillMessage = 'Invalid Backup Phone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
            $submitRefillType    = 'error';
        } else {
            $dupTagStmt = $pdo->prepare("
                SELECT customer_id FROM customer
                WHERE LOWER(container_label) = LOWER(:lbl) AND customer_id != :cid
                LIMIT 1
            ");
            $dupTagStmt->execute([':lbl' => $containerLabel, ':cid' => $customerId]);
            if ($dupTagStmt->fetch()) {
                $submitRefillMessage = 'Tag Name Taken: The Container Label "' . htmlspecialchars($containerLabel) . '" is already in use by another customer. Please choose a different tag name.';
                $submitRefillType    = 'error';
            } else {
                $capStmt = $pdo->prepare("
                    SELECT ts.batch_capacity,
                           ts.slot_label,
                           ts.time_to,
                           ts.slot_status,
                           COALESCE(SUM(CASE WHEN DATE(o.date_ordered) >= CURDATE() AND o.status_id != 7 THEN oi.quantity ELSE 0 END), 0) AS queued
                    FROM time_slot ts
                    LEFT JOIN order_information o ON o.slot_id = ts.slot_id
                    LEFT JOIN order_item oi ON oi.order_id = o.order_id
                    WHERE ts.slot_id = :sid
                    GROUP BY ts.slot_id, ts.batch_capacity, ts.slot_label, ts.time_to, ts.slot_status
                ");
                $capStmt->execute([':sid' => $slotId]);
                $capRow    = $capStmt->fetch();
                $queuedNow = $capRow ? (int)$capRow['queued'] : 0;
                $maxCap    = $capRow ? (int)$capRow['batch_capacity'] : 20;
                $isClosed  = $capRow && (strcasecmp((string)$capRow['slot_status'], 'Closed') === 0 || date('H:i:s') >= (string)$capRow['time_to']);

                // Prevents customer from reordering multiple times
                $actChkStmt = $pdo->prepare("
                    SELECT order_id FROM order_information
                    WHERE customer_id = :cid AND status_id NOT IN (5, 7)
                    LIMIT 1
                ");
                $actChkStmt->execute([':cid' => $customerId]);
                $existingActiveOrd = $actChkStmt->fetchColumn();

                if ($existingActiveOrd) {
                    $submitRefillMessage = "Active Order in Progress: You already have an active order ({$existingActiveOrd}). Please wait until it is delivered or cancel it on View Order Status before placing another order.";
                    $submitRefillType    = 'error';
                } elseif ($isClosed) {
                    $submitRefillMessage = "Batch Slot Closed: {$capRow['slot_label']} has already passed its cut-off or is closed. Please select an open batch window.";
                    $submitRefillType    = 'error';
                } elseif ($queuedNow + $quantity > $maxCap) {
                    $remaining           = max(0, $maxCap - $queuedNow);
                    $submitRefillMessage = "Batch Capacity Exceeded: Batch {$slotId} only has {$remaining} container slot(s) remaining (Max {$maxCap}). Please choose another batch window or reduce quantity.";
                    $submitRefillType    = 'error';
                } else {
                    $prodRow    = resolveSelectedContainerProduct($pdo, $containerType);
                    $productId  = (string)$prodRow['product_id'];
                    $refillRate = (float)($prodRow['refill_price'] ?? 30.00);

                    $subtotal   = $refillRate * $quantity;
                    $totalPrice = $subtotal;

                    try {
                        $pdo->beginTransaction();

                        updateCustomerDeliveryInfo(
                            $pdo,
                            $customerId,
                            $containerLabel,
                            $barangayRaw,
                            $addressRaw,
                            $cellphone,
                            $backupPhone,
                            $gateNote
                        );

                        $empId = $pdo->query("
                            SELECT employee_id FROM employee
                            WHERE emp_status = 'Active'
                            ORDER BY CASE WHEN emp_role LIKE '%Rider%' THEN 0 ELSE 1 END ASC, employee_id ASC
                            LIMIT 1
                        ")->fetchColumn() ?: null;

                        $newOrderId = getNextCustomerRecordId($pdo, 'order_information', 'order_id', 'ORD-');
                        $newPayId   = getNextCustomerRecordId($pdo, 'payment', 'payment_id', 'PAY-');

                        $insOrd = $pdo->prepare("
                            INSERT INTO order_information (
                                order_id, customer_id, employee_id, slot_id, status_id,
                                order_channel, service_mode, inspection_status,
                                delay_hold_notice, additional_notes, total_price, date_ordered
                            ) VALUES (
                                :oid, :cid, :eid, :sid, 1,
                                'Online Portal', 'Pickup/Delivery', 'Good',
                                'None', :notes, :tprice, CURRENT_TIMESTAMP
                            )
                        ");
                        $insOrd->execute([
                            ':oid'    => $newOrderId,
                            ':cid'    => $customerId,
                            ':eid'    => $empId,
                            ':sid'    => $slotId,
                            ':notes'  => $gateNote !== '' ? $gateNote : null,
                            ':tprice' => $totalPrice
                        ]);

                        $insItem = $pdo->prepare("
                            INSERT INTO order_item (
                                order_id, product_id, order_type,
                                unit_price, quantity, subtotal
                            ) VALUES (
                                :oid, :pid, 'Refill Only',
                                :uprice, :qty, :sub
                            )
                        ");
                        $insItem->execute([
                            ':oid'    => $newOrderId,
                            ':pid'    => $productId,
                            ':uprice' => $refillRate,
                            ':qty'    => $quantity,
                            ':sub'    => $subtotal
                        ]);

                        $insPay = $pdo->prepare("
                            INSERT INTO payment (
                                payment_id, order_id, payment_method,
                                total_paid, recorded_by, payment_notes,
                                date_paid, payment_status
                            ) VALUES (
                                :pid, :oid, 'Cash Only',
                                0.00, 'Online Portal', 'Cash on delivery',
                                NULL, 'Unpaid'
                            )
                        ");
                        $insPay->execute([
                            ':pid' => $newPayId,
                            ':oid' => $newOrderId
                        ]);

                        $pdo->commit();
                        header('Location: Submit_Water_Order.php?slot=' . $slotId . '&submitted=' . urlencode($newOrderId));
                        exit;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('WCDMS submit refill error: ' . $e->getMessage());
                        $submitRefillMessage = 'Database Error: Unable to submit your order right now. Please try again.';
                        $submitRefillType    = 'error';
                    }
                }
            }
        }
    }

    $customerProfile = getActiveCustomerProfile($pdo);
    $todaySlots      = getTodayBatchSlots($pdo);
    $productsList    = $pdo->query("
        SELECT product_id, container_name, container_type, capacity_gallons,
               refill_price, new_container_price, available_stock, availability_status
        FROM container_product
        ORDER BY product_id ASC
    ")->fetchAll();

    // FETCH API for Live Batch Slots & Product Prices
    if (isset($_GET['ajax_slots']) && $_GET['ajax_slots'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'   => 'ok',
            'slots'    => $todaySlots,
            'products' => $productsList
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $selectedSlotParam = isset($_GET['slot']) ? max(1, min(5, (int)$_GET['slot'])) : 1;
    if (!isset($_GET['slot'])) {
        foreach ($todaySlots as $s) {
            if ($s['is_current'] && !$s['is_full']) {
                $selectedSlotParam = (int)$s['slot_id'];
                break;
            }
        }
    }

    $refillRate = !empty($productsList) ? (float)$productsList[0]['refill_price'] : 30.00;

    $lastRefillStmt = $pdo->prepare("
        SELECT o.slot_id,
               ts.slot_label,
               oi.product_id,
               oi.quantity
        FROM order_information o
        INNER JOIN time_slot ts ON ts.slot_id = o.slot_id
        INNER JOIN order_item oi ON oi.order_id = o.order_id
        WHERE o.customer_id = :cid
          AND oi.order_type = 'Refill Only'
        ORDER BY o.date_ordered DESC, o.order_id DESC
        LIMIT 1
    ");
    $lastRefillStmt->execute([':cid' => $customerProfile['customer_id']]);
    $lastRefillRow = $lastRefillStmt->fetch();

    $dbPreset = null;
    if ($lastRefillRow) {
        $dbPreset = [
            'slot_id'         => (int)$lastRefillRow['slot_id'],
            'slot_label'      => (string)$lastRefillRow['slot_label'],
            'container_type'  => (string)$lastRefillRow['product_id'],
            'quantity'        => (int)$lastRefillRow['quantity'],
            'container_label' => $customerProfile['container_label'],
            'barangay'        => $customerProfile['barangay_short'],
            'address'         => $customerProfile['address'],
            'cellphone'       => $customerProfile['primary_phone'],
            'backup_phone'    => $customerProfile['backup_phone'],
            'gate_note'       => $customerProfile['gate_rider_note']
        ];
    }

    echo "<script>\n";
    echo "  window.MAJESTIC_SUBMIT_REFILL_DATA = " . json_encode([
        'customer'      => $customerProfile,
        'products'      => $productsList,
        'slots'         => $todaySlots,
        'selected_slot' => $selectedSlotParam,
        'refill_rate'   => $refillRate,
        'db_preset'     => $dbPreset,
        'flash'         => [
            'message' => $submitRefillMessage,
            'type'    => $submitRefillType
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ";\n";
    echo "</script>\n";
}

/* =========================================================================
   PAGE 3: ORDER NEW CONTAINER PAGE HANDLER (Order_New_Container.php)
   ========================================================================= */
$orderContainerMessage = '';
$orderContainerType    = '';

if ($currentPageName === 'order_new_container.php') {
    $customerProfile = getActiveCustomerProfile($pdo);
    $customerId      = $customerProfile['customer_id'];

    if (isset($_GET['ordered']) && $_GET['ordered'] !== '') {
        $orderContainerMessage = 'New Container Order ' . htmlspecialchars((string)$_GET['ordered']) . ' has been placed and queued! You can track its status on View Order Status.';
        $orderContainerType    = 'success';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectedContainer = trim((string)($_POST['container_type'] ?? 'PRD-01'));
        $refillOption      = trim((string)($_POST['water_refill_option'] ?? 'With Refill'));
        $slotLabelRaw      = trim((string)($_POST['slot_label'] ?? '10:00 AM - 12:00 PM'));
        $containerLabel    = trim((string)($_POST['container_label'] ?? ''));
        $barangayRaw       = trim((string)($_POST['barangay'] ?? '22'));
        $addressRaw        = trim((string)($_POST['address'] ?? ''));
        $cellphone         = trim((string)($_POST['cp_num_primary'] ?? $_POST['cellphone'] ?? ''));
        $backupPhone       = trim((string)($_POST['cp_num_backup'] ?? $_POST['backup_phone'] ?? ''));
        $serviceModeRaw    = trim((string)($_POST['service_mode'] ?? 'Delivery'));
        $gateNote          = trim((string)($_POST['gate_rider_note'] ?? ''));

        if ($containerLabel === '' || $addressRaw === '' || $cellphone === '') {
            $orderContainerMessage = 'Incomplete Booking Form: Please fill out Container Label, Address, and Cellphone #.';
            $orderContainerType    = 'error';
        } elseif (!preg_match('/^09\d{9}$/', $cellphone)) {
            $orderContainerMessage = 'Invalid Cellphone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
            $orderContainerType    = 'error';
        } elseif ($backupPhone !== '' && !preg_match('/^09\d{9}$/', $backupPhone)) {
            $orderContainerMessage = 'Invalid Backup Phone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
            $orderContainerType    = 'error';
        } else {
            $dupTagStmt = $pdo->prepare("
                SELECT customer_id FROM customer
                WHERE LOWER(container_label) = LOWER(:lbl) AND customer_id != :cid
                LIMIT 1
            ");
            $dupTagStmt->execute([':lbl' => $containerLabel, ':cid' => $customerId]);
            if ($dupTagStmt->fetch()) {
                $orderContainerMessage = 'Tag Name Taken: The Container Label "' . htmlspecialchars($containerLabel) . '" is already in use by another customer. Please choose a different tag name.';
                $orderContainerType    = 'error';
            } else {
                $prodRow   = resolveSelectedContainerProduct($pdo, $selectedContainer);
                $productId = (string)$prodRow['product_id'];

                $slotId = 2;
                if (preg_match('/^[1-5]$/', $slotLabelRaw)) {
                    $slotId = (int)$slotLabelRaw;
                } elseif (stripos($slotLabelRaw, '8:00') !== false) {
                    $slotId = 1;
                } elseif (stripos($slotLabelRaw, '10:00') !== false) {
                    $slotId = 2;
                } elseif (stripos($slotLabelRaw, '12:00') !== false) {
                    $slotId = 3;
                } elseif (stripos($slotLabelRaw, '2:00') !== false) {
                    $slotId = 4;
                } elseif (stripos($slotLabelRaw, '4:00') !== false) {
                    $slotId = 5;
                }

                $currentStock = (int)($prodRow['available_stock'] ?? 20);
                $availStat    = (string)($prodRow['availability_status'] ?? 'Available');
                $newPriceRate = (float)($prodRow['new_container_price'] ?? 250.00);
                $refillRate   = (float)($prodRow['refill_price'] ?? 30.00);

                $capStmt = $pdo->prepare("
                    SELECT ts.batch_capacity,
                           ts.slot_label,
                           ts.time_to,
                           ts.slot_status,
                           COALESCE(SUM(CASE WHEN DATE(o.date_ordered) >= CURDATE() AND o.status_id != 7 THEN oi.quantity ELSE 0 END), 0) AS queued
                    FROM time_slot ts
                    LEFT JOIN order_information o ON o.slot_id = ts.slot_id
                    LEFT JOIN order_item oi ON oi.order_id = o.order_id
                    WHERE ts.slot_id = :sid
                    GROUP BY ts.slot_id, ts.batch_capacity, ts.slot_label, ts.time_to, ts.slot_status
                ");
                $capStmt->execute([':sid' => $slotId]);
                $capRow    = $capStmt->fetch();
                $queuedNow = $capRow ? (int)$capRow['queued'] : 0;
                $maxCap    = $capRow ? (int)$capRow['batch_capacity'] : 20;
                $isClosed  = $capRow && (strcasecmp((string)$capRow['slot_status'], 'Closed') === 0 || date('H:i:s') >= (string)$capRow['time_to']);

                // Prevents customer from reordering multiple times
                $actChkStmt = $pdo->prepare("
                    SELECT order_id FROM order_information
                    WHERE customer_id = :cid AND status_id NOT IN (5, 7)
                    LIMIT 1
                ");
                $actChkStmt->execute([':cid' => $customerId]);
                $existingActiveOrd = $actChkStmt->fetchColumn();

                if ($existingActiveOrd) {
                    $orderContainerMessage = "Active Order in Progress: You already have an active order ({$existingActiveOrd}). Please wait until it is delivered or cancel it on View Order Status before placing another order.";
                    $orderContainerType    = 'error';
                } elseif ($currentStock <= 0 || $availStat === 'Out of Stock') {
                    $orderContainerMessage = 'Out of Stock: The selected container (' . htmlspecialchars((string)$prodRow['container_name']) . ') is currently out of stock.';
                    $orderContainerType    = 'error';
                } elseif ($isClosed) {
                    $orderContainerMessage = "Batch Slot Closed: {$capRow['slot_label']} has already passed its cut-off or is closed. Please select an open delivery batch slot.";
                    $orderContainerType    = 'error';
                } elseif ($queuedNow + 1 > $maxCap) {
                    $orderContainerMessage = "Batch Slot Full: Batch {$slotId} has reached its {$maxCap}-container capacity. Please select another delivery batch slot.";
                    $orderContainerType    = 'error';
                } else {
                    $withRefill = ($refillOption === 'With Refill' || stripos($refillOption, 'Include') !== false);
                    $orderType  = $withRefill ? 'New Container + Refill' : 'New Container Only';
                    $unitPrice  = $withRefill ? ($newPriceRate + $refillRate) : $newPriceRate;
                    $totalPrice = $unitPrice;

                    if (strcasecmp($serviceModeRaw, 'Walk-In/Delivery') === 0 || strcasecmp($serviceModeRaw, 'Delivery') === 0) {
                        $serviceMode = 'Walk-In/Delivery';
                    } elseif (stripos($serviceModeRaw, 'Walk') !== false) {
                        $serviceMode = 'Walk-In/Pickup';
                    } else {
                        $serviceMode = 'Pickup/Delivery';
                    }

                    try {
                        $pdo->beginTransaction();

                        updateCustomerDeliveryInfo(
                            $pdo,
                            $customerId,
                            $containerLabel,
                            $barangayRaw,
                            $addressRaw,
                            $cellphone,
                            $backupPhone,
                            $gateNote
                        );

                        // stock deduction: fails if another customer just took the last unit
                        $updStock = $pdo->prepare("
                            UPDATE container_product
                            SET available_stock = available_stock - 1,
                                availability_status = CASE WHEN available_stock <= 0 THEN 'Out of Stock' ELSE 'Available' END
                            WHERE product_id = :pid AND available_stock > 0
                        ");
                        $updStock->execute([':pid' => $productId]);
                        if ($updStock->rowCount() === 0) {
                            throw new RuntimeException('OUT_OF_STOCK');
                        }

                        $empId = $pdo->query("
                            SELECT employee_id FROM employee
                            WHERE emp_status = 'Active'
                            ORDER BY CASE WHEN emp_role LIKE '%Rider%' THEN 0 ELSE 1 END ASC, employee_id ASC
                            LIMIT 1
                        ")->fetchColumn() ?: null;

                        $newOrderId = getNextCustomerRecordId($pdo, 'order_information', 'order_id', 'ORD-');
                        $newPayId   = getNextCustomerRecordId($pdo, 'payment', 'payment_id', 'PAY-');

                        $insOrd = $pdo->prepare("
                            INSERT INTO order_information (
                                order_id, customer_id, employee_id, slot_id, status_id,
                                order_channel, service_mode, inspection_status,
                                delay_hold_notice, additional_notes, total_price, date_ordered
                            ) VALUES (
                                :oid, :cid, :eid, :sid, 1,
                                'Online Portal', :smode, 'Good',
                                'None', :notes, :tprice, CURRENT_TIMESTAMP
                            )
                        ");
                        $insOrd->execute([
                            ':oid'    => $newOrderId,
                            ':cid'    => $customerId,
                            ':eid'    => $empId,
                            ':sid'    => $slotId,
                            ':smode'  => $serviceMode,
                            ':notes'  => $gateNote !== '' ? $gateNote : null,
                            ':tprice' => $totalPrice
                        ]);

                        $insItem = $pdo->prepare("
                            INSERT INTO order_item (
                                order_id, product_id, order_type,
                                unit_price, quantity, subtotal
                            ) VALUES (
                                :oid, :pid, :otype,
                                :uprice, 1, :sub
                            )
                        ");
                        $insItem->execute([
                            ':oid'    => $newOrderId,
                            ':pid'    => $productId,
                            ':otype'  => $orderType,
                            ':uprice' => $unitPrice,
                            ':sub'    => $totalPrice
                        ]);

                        $insPay = $pdo->prepare("
                            INSERT INTO payment (
                                payment_id, order_id, payment_method,
                                total_paid, recorded_by, payment_notes,
                                date_paid, payment_status
                            ) VALUES (
                                :pid, :oid, 'Cash Only',
                                0.00, 'Online Portal', 'Cash on delivery',
                                NULL, 'Unpaid'
                            )
                        ");
                        $insPay->execute([
                            ':pid' => $newPayId,
                            ':oid' => $newOrderId
                        ]);

                        $pdo->commit();
                        header('Location: Order_New_Container.php?ordered=' . urlencode($newOrderId));
                        exit;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('WCDMS new container error: ' . $e->getMessage());
                        $orderContainerMessage = ($e->getMessage() === 'OUT_OF_STOCK')
                            ? 'Out of Stock: The selected container was just taken by another customer. Please choose another container.'
                            : 'Database Error: Unable to place your order right now. Please try again.';
                        $orderContainerType    = 'error';
                    }
                }
            }
        }
    }

    $customerProfile = getActiveCustomerProfile($pdo);
    $todaySlots      = getTodayBatchSlots($pdo);
    $products        = $pdo->query("SELECT * FROM container_product ORDER BY product_id ASC")->fetchAll();

    $selectedSlotParam = isset($_GET['slot']) ? max(1, min(5, (int)$_GET['slot'])) : 2;

    echo "<script>\n";
    echo "  window.MAJESTIC_NEW_CONTAINER_DATA = " . json_encode([
        'customer'      => $customerProfile,
        'products'      => $products,
        'slots'         => $todaySlots,
        'selected_slot' => $selectedSlotParam,
        'flash'         => [
            'message' => $orderContainerMessage,
            'type'    => $orderContainerType
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ";\n";
    echo "</script>\n";
}
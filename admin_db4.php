<?php
/* =============================
    Database Connection for
   1. Batch_Orders.php
   2. Order_Records.php
   =========================== */
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
} catch (PDOException $e) {
    die('Database Connection Error: ' . htmlspecialchars($e->getMessage()));
}

$currentPageName = strtolower(basename($_SERVER['PHP_SELF'] ?? ''));

/* ====================================================================
   Generate next PRIMARY KEY ID (CUS-000, ORD-000, PAY-0000)
   =================================================================== */
function getNextFormattedId(PDO $pdo, string $table, string $column, string $prefix, int $pad = 4): string {
    $startPos = strlen($prefix) + 1;
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING({$column}, {$startPos}) AS UNSIGNED)), 0)
        FROM {$table}
        WHERE {$column} LIKE :pfx
    ");
    $stmt->execute([':pfx' => $prefix . '%']);
    $maxNum = (int)$stmt->fetchColumn();
    return sprintf('%s%0' . $pad . 'd', $prefix, $maxNum + 1);
}

/* =============================================================
  PRE-FETCH CUSTOMER PHONE NUMBERS MAP (ELIMINATES N+1 QUERIES)
   ============================================================== */
function getCustomerPhonesMap(PDO $pdo): array {
    $map = [];
    $rows = $pdo->query("SELECT customer_id, cp_type, cp_num FROM customer_cp_num")->fetchAll();
    foreach ($rows as $r) {
        $cid = (string)$r['customer_id'];
        if (($r['cp_type'] ?? '') === 'Primary') {
            $map[$cid]['primary'] = (string)$r['cp_num'];
        } elseif (($r['cp_type'] ?? '') === 'Backup') {
            $map[$cid]['backup'] = (string)$r['cp_num'];
        }
    }
    return $map;
}

/* ======================================================
   BUILD ACTIVE CUSTOMERS LOOKUP ARRAY FOR FORM AUTO-FILL
   ======================================================= */
function getActiveCustomersLookup(PDO $pdo, array $phonesMap): array {
    $lookup = [];
    $custRows = $pdo->query("SELECT * FROM customer WHERE cust_status = 'Active' ORDER BY customer_id ASC")->fetchAll();
    foreach ($custRows as $c) {
        $cid  = (string)$c['customer_id'];
        $bNum = preg_replace('/[^0-9]/', '', (string)$c['barangay']) ?: '22';
        $lookup[] = [
            'customer_id'     => $cid,
            'customer_name'   => $c['first_name'] . ' ' . $c['last_name'],
            'container_label' => (string)$c['container_label'],
            'address'         => $c['house_no'] . ' ' . $c['street_name'] . ', ' . $c['city'],
            'barangay_num'    => $bNum,
            'primary_phone'   => $phonesMap[$cid]['primary'] ?? '',
            'backup_phone'    => $phonesMap[$cid]['backup'] ?? '',
            'gate_rider_note' => (string)($c['gate_rider_note'] ?? '')
        ];
    }
    return $lookup;
}

/* ===================================================================
   GET CURRENT LOGGED-IN ADMIN/STAFF EMPLOYEE WHO RECORDED THE CHANGE
   =================================================================== */
function getCurrentAdminActorLabel(PDO $pdo): string {
    if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['employee_id'])) {
        $u     = $_SESSION['user'];
        $eid   = (string)$u['employee_id'];
        $fname = trim((string)($u['first_name'] ?? ''));
        return $fname !== '' ? "{$eid} ({$fname})" : $eid;
    }

    if (!empty($_SESSION['employee_id'])) {
        $eid = (string)$_SESSION['employee_id'];
        $stmt = $pdo->prepare("SELECT employee_id, first_name FROM employee WHERE employee_id = :eid LIMIT 1");
        $stmt->execute([':eid' => $eid]);
        $empRow = $stmt->fetch();
        if ($empRow) {
            return $empRow['employee_id'] . ' (' . $empRow['first_name'] . ')';
        }
    }

    $emp = $pdo->query("SELECT employee_id, first_name FROM employee WHERE emp_status = 'Active' ORDER BY employee_id ASC LIMIT 1")->fetch();
    if ($emp) {
        return $emp['employee_id'] . ' (' . $emp['first_name'] . ')';
    }
    return 'EMP-0001 (Admin)';
}

/* ========================================
   NORMALIZE SERVICE MODE & ORDER CHANNEL
   ========================================*/
function normalizeStationServiceMode(string $raw): string {
    $r = trim($raw);
    if (strcasecmp($r, 'Walk-In/Delivery') === 0 || strcasecmp($r, 'Walkin-In/Delivery') === 0 || strcasecmp($r, 'Delivery') === 0) {
        return 'Walk-In/Delivery';
    }
    if (strcasecmp($r, 'Walk-In/Pickup') === 0 || strcasecmp($r, 'Walk-In') === 0 || strcasecmp($r, 'Station Walk-In') === 0) {
        return 'Walk-In/Pickup';
    }
    return 'Pickup/Delivery';
}

function normalizeStationOrderChannel(string $raw): string {
    return (stripos(trim($raw), 'Online') !== false) ? 'Online Portal' : 'User/Admin Encoded';
}

/* ===========================================================
   RESOLVE OR CREATE CUSTOMER RECORD FOR ADMIN-ENCODED ORDERS
   =========================================================== */
function resolveOrCreateBatchCustomer(
    PDO $pdo,
    string $customerIdInput,
    string $customerName,
    string $containerLabel,
    string $address,
    string $barangayRaw,
    string $cellphone,
    string $backupPhone,
    string $notes = ''
): string {
    $barangayFormatted = preg_match('/^\d+$/', trim($barangayRaw))
        ? 'Barangay ' . trim($barangayRaw)
        : (trim($barangayRaw) ?: 'Barangay 22');

    $resolvedCustId = '';
    if ($customerIdInput !== '' && $customerIdInput !== 'CUS-#') {
        $cChk = $pdo->prepare("SELECT customer_id FROM customer WHERE customer_id = :cid LIMIT 1");
        $cChk->execute([':cid' => $customerIdInput]);
        if ($cChk->fetch()) {
            $resolvedCustId = $customerIdInput;
        }
    }

    if ($resolvedCustId === '') {
        $findCust = $pdo->prepare("
            SELECT c.customer_id
            FROM customer c
            LEFT JOIN customer_cp_num cp ON cp.customer_id = c.customer_id
            WHERE LOWER(CONCAT(c.first_name, ' ', c.last_name)) = LOWER(:fullname)
               OR (LOWER(c.container_label) = LOWER(:label) AND :label2 != '')
               OR cp.cp_num = :phone
            LIMIT 1
        ");
        $findCust->execute([
            ':fullname' => $customerName,
            ':label'    => $containerLabel,
            ':label2'   => $containerLabel,
            ':phone'    => $cellphone
        ]);
        $foundRow = $findCust->fetch();
        if ($foundRow) {
            $resolvedCustId = (string)$foundRow['customer_id'];
        }
    }

    $nameParts  = preg_split('/\s+/', trim($customerName), 2);
    $fName      = $nameParts[0] ?? 'Customer';
    $lName      = $nameParts[1] ?? 'Household';
    $labelFinal = $containerLabel !== '' ? $containerLabel : $fName;

    $cleanAddr = trim(str_ireplace(', Caloocan City', '', $address));
    if (preg_match('/^(\S+)\s+(.+)$/', $cleanAddr, $addrMatch)) {
        $hNo    = $addrMatch[1];
        $stName = $addrMatch[2];
    } else {
        $hNo    = '#1';
        $stName = $cleanAddr !== '' ? $cleanAddr : 'Sample Street';
    }

    if ($resolvedCustId === '') {
        $resolvedCustId = getNextFormattedId($pdo, 'customer', 'customer_id', 'CUS-');
        $autoEmail      = strtolower($resolvedCustId) . '.' . time() . '@majesticspring.local';

        $insCust = $pdo->prepare("
            INSERT INTO customer (
                customer_id, first_name, last_name, container_label,
                house_no, street_name, barangay, city,
                email_address, password_hash, gate_rider_note,
                date_registered, cust_status
            ) VALUES (
                :cid, :fname, :lname, :label,
                :house, :street, :brgy, 'Caloocan City',
                :email, :phash, :gate,
                CURRENT_DATE, 'Active'
            )
        ");
        $insCust->execute([
            ':cid'    => $resolvedCustId,
            ':fname'  => $fName,
            ':lname'  => $lName,
            ':label'  => $labelFinal,
            ':house'  => $hNo,
            ':street' => $stName,
            ':brgy'   => $barangayFormatted,
            ':email'  => $autoEmail,
            ':phash'  => 'customer123',
            ':gate'   => $notes !== '' ? $notes : null
        ]);
    } else {
        $updCust = $pdo->prepare("
            UPDATE customer
            SET first_name = :fname,
                last_name = :lname,
                container_label = :label,
                house_no = :house,
                street_name = :street,
                barangay = :brgy
            WHERE customer_id = :cid
        ");
        $updCust->execute([
            ':fname'  => $fName,
            ':lname'  => $lName,
            ':label'  => $labelFinal,
            ':house'  => $hNo,
            ':street' => $stName,
            ':brgy'   => $barangayFormatted,
            ':cid'    => $resolvedCustId
        ]);
    }

    if ($cellphone !== '') {
        $pdo->prepare("DELETE FROM customer_cp_num WHERE customer_id = :cid")->execute([':cid' => $resolvedCustId]);
        $insPh = $pdo->prepare("INSERT INTO customer_cp_num (customer_id, cp_num, cp_type) VALUES (:cid, :cp, :ctype)");
        $insPh->execute([':cid' => $resolvedCustId, ':cp' => $cellphone, ':ctype' => 'Primary']);
        if ($backupPhone !== '' && $backupPhone !== $cellphone) {
            $insPh->execute([':cid' => $resolvedCustId, ':cp' => $backupPhone, ':ctype' => 'Backup']);
        }
    }

    return $resolvedCustId;
}

/* ===================================================
  PAYMENT RECORD SYNC FOR PAID OR UNPAID/UTANG ORDERS
   ===================================================*/
function syncOrderPaymentRecord(
    PDO $pdo,
    string $orderId,
    int $statusId,
    bool $isUnpaidUtang,
    float $totalPrice,
    string $recordedBy,
    string $notes = ''
): void {
    if (!$isUnpaidUtang && $statusId !== 5) {
        return;
    }

    $payStatus  = $isUnpaidUtang ? 'Unpaid' : 'Paid';
    $paidAmount = $isUnpaidUtang ? 0.00 : $totalPrice;
    $payNote    = $notes !== ''
        ? $notes
        : ($isUnpaidUtang ? 'Flagged as Unpaid / Utang in Batch Orders' : 'Paid in cash upon delivery');

    if ($statusId === 5) {
        $pdo->prepare("
            UPDATE order_information
            SET date_ordered = CASE
                WHEN DATE(date_ordered) < CURDATE() THEN CURRENT_TIMESTAMP
                ELSE date_ordered
            END
            WHERE order_id = :oid
        ")->execute([':oid' => $orderId]);
    }

    $payChk = $pdo->prepare("SELECT payment_id FROM payment WHERE order_id = :oid LIMIT 1");
    $payChk->execute([':oid' => $orderId]);

    if ($payChk->fetch()) {
        $updPay = $pdo->prepare("
            UPDATE payment
            SET total_paid = :tpaid,
                payment_status = :pstat,
                recorded_by = :rec,
                payment_notes = :pnotes,
                date_paid = CURRENT_TIMESTAMP
            WHERE order_id = :oid
        ");
        $updPay->execute([
            ':tpaid'  => $paidAmount,
            ':pstat'  => $payStatus,
            ':rec'    => $recordedBy,
            ':pnotes' => $payNote,
            ':oid'    => $orderId
        ]);
    } else {
        $newPayId = getNextFormattedId($pdo, 'payment', 'payment_id', 'PAY-');
        $insPay   = $pdo->prepare("
            INSERT INTO payment (
                payment_id, order_id, payment_method,
                total_paid, recorded_by, payment_notes,
                date_paid, payment_status
            ) VALUES (
                :pid, :oid, 'Cash Only',
                :tpaid, :rec, :pnotes,
                CURRENT_TIMESTAMP, :pstat
            )
        ");
        $insPay->execute([
            ':pid'    => $newPayId,
            ':oid'    => $orderId,
            ':tpaid'  => $paidAmount,
            ':rec'    => $recordedBy,
            ':pnotes' => $payNote,
            ':pstat'  => $payStatus
        ]);
    }
}

/* =============================================
   PAGE 4: BATCH ORDERS PAGE (Batch_Orders.php)
   ============================================ */
$batchStatusMessage = '';
$batchStatusType    = '';

if ($currentPageName === 'batch_orders.php') {
    $currentActor = getCurrentAdminActorLabel($pdo);

    if (isset($_GET['saved']) && $_GET['saved'] !== '') {
        $batchStatusMessage = 'Order ' . htmlspecialchars((string)$_GET['saved']) . ' saved successfully.';
        $batchStatusType    = 'success';
    } elseif (isset($_GET['bulk']) && $_GET['bulk'] !== '') {
        $batchStatusMessage = 'Updated status for all active orders in Batch ' . htmlspecialchars((string)$_GET['bulk']) . '.';
        $batchStatusType    = 'success';
    } elseif (isset($_GET['slot_updated']) && $_GET['slot_updated'] !== '') {
        $batchStatusMessage = 'Batch ' . htmlspecialchars((string)$_GET['slot_updated']) . ' capacity and slot status updated successfully.';
        $batchStatusType    = 'success';
    } elseif (isset($_GET['cancelled']) && $_GET['cancelled'] !== '') {
        $batchStatusMessage = 'Order ' . htmlspecialchars((string)$_GET['cancelled']) . ' has been marked as Cancelled.';
        $batchStatusType    = 'success';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formAction      = trim((string)($_POST['form_action'] ?? 'save'));
        $orderId         = trim((string)($_POST['order_id'] ?? 'ORD-#'));
        $customerId      = trim((string)($_POST['customer_id'] ?? 'CUS-#'));
        $statusRaw       = trim((string)($_POST['status_id'] ?? '1'));
        $serviceModeRaw  = trim((string)($_POST['service_mode'] ?? 'Pickup/Delivery'));
        $customerName    = trim((string)($_POST['customer_name'] ?? ''));
        $address         = trim((string)($_POST['address'] ?? ''));
        $barangayRaw     = trim((string)($_POST['barangay'] ?? '22'));
        $cellphone       = trim((string)($_POST['cellphone'] ?? ''));
        $backupPhone     = trim((string)($_POST['backup_phone'] ?? ''));
        $containerLabel  = trim((string)($_POST['container_label'] ?? ''));
        $slotId          = max(1, min(5, (int)($_POST['slot_id'] ?? 1)));
        $riderId         = trim((string)($_POST['rider_id'] ?? ''));
        $inspectionRaw   = trim((string)($_POST['inspection_status'] ?? 'Good'));
        $productChoice   = trim((string)($_POST['product_id'] ?? 'PID-2'));
        $quantity        = max(1, min(100, (int)($_POST['quantity'] ?? 1)));
        $manualTotalRaw  = trim((string)($_POST['manual_total_price'] ?? ''));
        $delayNotice     = trim((string)($_POST['delay_hold_notice'] ?? 'None'));
        $additionalNotes = trim((string)($_POST['order_notes'] ?? $_POST['additional_notes'] ?? ''));

        $isUnpaidUtang = ($statusRaw === '5_UNPAID' || stripos($delayNotice, 'Utang') !== false);
        $statusId      = ($statusRaw === '5_UNPAID') ? 5 : max(1, min(7, (int)$statusRaw));
        if ($isUnpaidUtang) {
            $delayNotice = 'Unpaid / Utang';
        } elseif ($delayNotice === 'Unpaid / Utang' && $statusRaw !== '5_UNPAID') {
            $delayNotice = 'None';
        }

        // UPDATE BATCH CAPACITY & SLOT STATUS FOR ACTIVE BATCH SLOT
        if ($formAction === 'update_slot_settings') {
            $targetSlotId = max(1, min(5, (int)($_POST['bulk_slot_id'] ?? $slotId)));
            $newCapacity  = max(1, min(100, (int)($_POST['new_batch_capacity'] ?? 20)));
            $newSlotStat  = trim((string)($_POST['new_slot_status'] ?? 'Open'));
            if (!in_array($newSlotStat, ['Open', 'Full', 'Closed'], true)) {
                $newSlotStat = 'Open';
            }

            try {
                $updSlot = $pdo->prepare("
                    UPDATE time_slot
                    SET batch_capacity = :cap,
                        slot_status = :stat
                    WHERE slot_id = :sid
                ");
                $updSlot->execute([
                    ':cap'  => $newCapacity,
                    ':stat' => $newSlotStat,
                    ':sid'  => $targetSlotId
                ]);
                header('Location: Batch_Orders.php?slot=' . $targetSlotId . '&slot_updated=' . $targetSlotId);
                exit;
            } catch (Throwable $e) {
                $batchStatusMessage = 'Database Error: ' . $e->getMessage();
                $batchStatusType    = 'error';
            }
        }

        // BULK CHANGE ORDER STATUS FOR ALL ACTIVE/CARRIED-OVER ORDERS IN CURRENT BATCH
        if ($formAction === 'bulk_status') {
            $bulkRaw    = trim((string)($_POST['bulk_status_val'] ?? ''));
            $bulkSlotId = max(1, min(5, (int)($_POST['bulk_slot_id'] ?? $slotId)));

            if ($bulkRaw !== '') {
                $bulkIsUtang  = ($bulkRaw === '5_UNPAID');
                $bulkStatusId = $bulkIsUtang ? 5 : max(1, min(7, (int)$bulkRaw));

                try {
                    $pdo->beginTransaction();

                    $batchOrdsStmt = $pdo->prepare("
                        SELECT order_id, total_price
                        FROM order_information
                        WHERE slot_id = :sid
                          AND (DATE(date_ordered) >= CURDATE() OR status_id NOT IN (5, 7))
                    ");
                    $batchOrdsStmt->execute([':sid' => $bulkSlotId]);
                    $batchOrds = $batchOrdsStmt->fetchAll();

                    $updBatchStmt = $pdo->prepare("
                        UPDATE order_information
                        SET status_id = :stid,
                            delay_hold_notice = :delay
                        WHERE order_id = :oid
                    ");

                    foreach ($batchOrds as $bo) {
                        $bOid = (string)$bo['order_id'];
                        $updBatchStmt->execute([
                            ':stid'  => $bulkStatusId,
                            ':delay' => $bulkIsUtang ? 'Unpaid / Utang' : 'None',
                            ':oid'   => $bOid
                        ]);

                        syncOrderPaymentRecord(
                            $pdo,
                            $bOid,
                            $bulkStatusId,
                            $bulkIsUtang,
                            (float)$bo['total_price'],
                            $currentActor
                        );
                    }

                    $pdo->commit();
                    header('Location: Batch_Orders.php?slot=' . $bulkSlotId . '&bulk=' . $bulkSlotId);
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $batchStatusMessage = 'Database Error: ' . $e->getMessage();
                    $batchStatusType    = 'error';
                }
            }
        }

        // QUICK STATUS CHANGE FROM INDIVIDUAL TABLE ROW DROPDOWN
        if ($formAction === 'quick_status' && $orderId !== '' && $orderId !== 'ORD-#') {
            try {
                $pdo->beginTransaction();

                $updSt = $pdo->prepare("
                    UPDATE order_information
                    SET status_id = :sid,
                        delay_hold_notice = :delay
                    WHERE order_id = :oid
                ");
                $updSt->execute([
                    ':sid'   => $statusId,
                    ':delay' => $isUnpaidUtang ? 'Unpaid / Utang' : 'None',
                    ':oid'   => $orderId
                ]);

                $ordStmt = $pdo->prepare("SELECT total_price FROM order_information WHERE order_id = :oid LIMIT 1");
                $ordStmt->execute([':oid' => $orderId]);
                $ordRow = $ordStmt->fetch();
                if ($ordRow) {
                    syncOrderPaymentRecord(
                        $pdo,
                        $orderId,
                        $statusId,
                        $isUnpaidUtang,
                        (float)$ordRow['total_price'],
                        $currentActor
                    );
                }

                $pdo->commit();
                header('Location: Batch_Orders.php?slot=' . $slotId . '&saved=' . urlencode($orderId));
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $batchStatusMessage = 'Database Error: ' . $e->getMessage();
                $batchStatusType    = 'error';
            }
        }

        // CANCEL SELECTED ORDER (AND RESTORE STOCK IF NEW CONTAINER ORDER)
        if ($formAction === 'cancel' && $orderId !== '' && $orderId !== 'ORD-#') {
            try {
                $pdo->beginTransaction();

                $chkOrd = $pdo->prepare("
                    SELECT o.status_id, oi.product_id, oi.order_type, oi.quantity
                    FROM order_information o
                    LEFT JOIN order_item oi ON oi.order_id = o.order_id
                    WHERE o.order_id = :oid
                    LIMIT 1
                ");
                $chkOrd->execute([':oid' => $orderId]);
                $ordItemRow = $chkOrd->fetch();

                $pdo->prepare("UPDATE order_information SET status_id = 7 WHERE order_id = :oid")
                    ->execute([':oid' => $orderId]);

                if (
                    $ordItemRow &&
                    (int)$ordItemRow['status_id'] !== 7 &&
                    stripos((string)($ordItemRow['order_type'] ?? ''), 'New Container') !== false &&
                    !empty($ordItemRow['product_id'])
                ) {
                    $restoreQty = max(1, (int)$ordItemRow['quantity']);
                    $pdo->prepare("
                        UPDATE container_product
                        SET available_stock = available_stock + :qty,
                            availability_status = 'Available'
                        WHERE product_id = :pid
                    ")->execute([
                        ':qty' => $restoreQty,
                        ':pid' => (string)$ordItemRow['product_id']
                    ]);
                }

                $pdo->commit();
                header('Location: Batch_Orders.php?slot=' . $slotId . '&cancelled=' . urlencode($orderId));
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $batchStatusMessage = 'Database Error: ' . $e->getMessage();
                $batchStatusType    = 'error';
            }
        }

        // SAVE (ADD NEW OR UPDATE EXISTING) BATCH ORDER
        if ($formAction === 'save') {
            if ($customerName === '' || $address === '' || $cellphone === '') {
                $batchStatusMessage = 'Incomplete Form: Please fill out Customer Name, Address, and Cellphone #.';
                $batchStatusType    = 'error';
            } elseif (!preg_match('/^09\d{9}$/', $cellphone)) {
                $batchStatusMessage = 'Invalid Cellphone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
                $batchStatusType    = 'error';
            } elseif ($backupPhone !== '' && !preg_match('/^09\d{9}$/', $backupPhone)) {
                $batchStatusMessage = 'Invalid Backup Phone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
                $batchStatusType    = 'error';
            } else {
                $serviceMode = normalizeStationServiceMode($serviceModeRaw);

                if (stripos($inspectionRaw, 'Damaged') !== false) {
                    $inspectionStatus = 'Damaged';
                } elseif ($inspectionRaw === 'Pending') {
                    $inspectionStatus = 'Pending';
                } else {
                    $inspectionStatus = 'Good';
                }

                $isManualOther = ($productChoice === 'OTHER');
                if ($isManualOther) {
                    $firstProd = $pdo->query("SELECT product_id FROM container_product ORDER BY product_id ASC LIMIT 1")->fetchColumn();
                    $productId = $firstProd ? (string)$firstProd : 'PRD-01';
                    $orderType = 'Other';
                    $manualVal = (float)preg_replace('/[^0-9.]/', '', $manualTotalRaw);
                    $totalPrice = round($manualVal, 2);
                    $subtotal   = $totalPrice;
                    $unitPrice  = round($totalPrice / $quantity, 2);
                } elseif (strpos($productChoice, '|') !== false) {
                    [$modeCode, $productId] = explode('|', $productChoice, 2);
                    $orderType = ($modeCode === 'NEW')
                        ? 'New Container Only'
                        : (($modeCode === 'NEW-REFILL') ? 'New Container + Refill' : 'Refill Only');
                } else {
                    $productId = in_array($productChoice, ['PID-1', 'PRD-02', 'NEW-ROUND', 'NEW-ROUND-REFILL'], true)
                        ? 'PRD-02'
                        : 'PRD-01';
                    if (in_array($productChoice, ['NEW-SLIM', 'NEW-ROUND'], true)) {
                        $orderType = 'New Container Only';
                    } elseif (in_array($productChoice, ['NEW-SLIM-REFILL', 'NEW-ROUND-REFILL'], true)) {
                        $orderType = 'New Container + Refill';
                    } else {
                        $orderType = 'Refill Only';
                    }
                }

                if (!$isManualOther) {
                    $pStmt = $pdo->prepare("SELECT refill_price, new_container_price FROM container_product WHERE product_id = :pid LIMIT 1");
                    $pStmt->execute([':pid' => $productId]);
                    $prodRow    = $pStmt->fetch();
                    $refillRate = $prodRow ? (float)$prodRow['refill_price'] : 30.00;
                    $newRate    = $prodRow ? (float)$prodRow['new_container_price'] : 250.00;

                    if ($orderType === 'New Container Only') {
                        $unitPrice = $newRate;
                    } elseif ($orderType === 'New Container + Refill') {
                        $unitPrice = $newRate + $refillRate;
                    } else {
                        $unitPrice = $refillRate;
                    }
                    $subtotal   = $unitPrice * $quantity;
                    $totalPrice = $subtotal;
                }

                $isNewOrder = ($orderId === '' || $orderId === 'ORD-#');
                $capChk = $pdo->prepare("
                    SELECT ts.batch_capacity,
                           COALESCE(SUM(
                               CASE
                                   WHEN o.status_id NOT IN (5, 7) AND (:editOid = '' OR o.order_id != :editOid2)
                                   THEN oi.quantity
                                   ELSE 0
                               END
                           ), 0) AS queued_now
                    FROM time_slot ts
                    LEFT JOIN order_information o ON o.slot_id = ts.slot_id
                    LEFT JOIN order_item oi ON oi.order_id = o.order_id
                    WHERE ts.slot_id = :sid
                    GROUP BY ts.slot_id, ts.batch_capacity
                ");
                $editIdParam = $isNewOrder ? '' : $orderId;
                $capChk->execute([':editOid' => $editIdParam, ':editOid2' => $editIdParam, ':sid' => $slotId]);
                $capRow    = $capChk->fetch();
                $maxCap    = $capRow ? (int)$capRow['batch_capacity'] : 20;
                $queuedNow = $capRow ? (int)$capRow['queued_now'] : 0;

                if ($isManualOther && $totalPrice <= 0) {
                    $batchStatusMessage = 'Manual Price Required: Please enter a valid total price greater than P0.00 for Other orders.';
                    $batchStatusType    = 'error';
                } elseif (!in_array($statusId, [5, 7], true) && ($queuedNow + $quantity > $maxCap)) {
                    $remaining          = max(0, $maxCap - $queuedNow);
                    $batchStatusMessage = "Batch Capacity Exceeded: Batch {$slotId} only has {$remaining} container slot(s) remaining (Max {$maxCap}).";
                    $batchStatusType    = 'error';
                } else {
                try {
                    $pdo->beginTransaction();

                    $resolvedCustId = resolveOrCreateBatchCustomer(
                        $pdo,
                        $customerId,
                        $customerName,
                        $containerLabel,
                        $address,
                        $barangayRaw,
                        $cellphone,
                        $backupPhone,
                        $additionalNotes
                    );

                    $resolvedEmpId = null;
                    if ($riderId !== '') {
                        $eChk = $pdo->prepare("SELECT employee_id FROM employee WHERE employee_id = :eid LIMIT 1");
                        $eChk->execute([':eid' => $riderId]);
                        if ($eChk->fetch()) {
                            $resolvedEmpId = $riderId;
                        }
                    }
                    if ($resolvedEmpId === null) {
                        $firstEmp = $pdo->query("SELECT employee_id FROM employee ORDER BY employee_id ASC LIMIT 1")->fetch();
                        if ($firstEmp) {
                            $resolvedEmpId = (string)$firstEmp['employee_id'];
                        }
                    }

                    if ($isNewOrder) {
                        $targetOrderId = getNextFormattedId($pdo, 'order_information', 'order_id', 'ORD-');

                        $insOrd = $pdo->prepare("
                            INSERT INTO order_information (
                                order_id, customer_id, employee_id, slot_id, status_id,
                                order_channel, service_mode, inspection_status,
                                delay_hold_notice, additional_notes, total_price, date_ordered
                            ) VALUES (
                                :oid, :cid, :eid, :sid, :stid,
                                'User/Admin Encoded', :smode, :insp,
                                :delay, :notes, :tprice, CURRENT_TIMESTAMP
                            )
                        ");
                        $insOrd->execute([
                            ':oid'    => $targetOrderId,
                            ':cid'    => $resolvedCustId,
                            ':eid'    => $resolvedEmpId,
                            ':sid'    => $slotId,
                            ':stid'   => $statusId,
                            ':smode'  => $serviceMode,
                            ':insp'   => $inspectionStatus,
                            ':delay'  => $delayNotice ?: 'None',
                            ':notes'  => $additionalNotes !== '' ? $additionalNotes : null,
                            ':tprice' => $totalPrice
                        ]);
                    } else {
                        $targetOrderId = $orderId;

                        $updOrd = $pdo->prepare("
                            UPDATE order_information
                            SET customer_id = :cid,
                                employee_id = :eid,
                                slot_id = :sid,
                                status_id = :stid,
                                service_mode = :smode,
                                inspection_status = :insp,
                                delay_hold_notice = :delay,
                                additional_notes = :notes,
                                total_price = :tprice
                            WHERE order_id = :oid
                        ");
                        $updOrd->execute([
                            ':cid'    => $resolvedCustId,
                            ':eid'    => $resolvedEmpId,
                            ':sid'    => $slotId,
                            ':stid'   => $statusId,
                            ':smode'  => $serviceMode,
                            ':insp'   => $inspectionStatus,
                            ':delay'  => $delayNotice ?: 'None',
                            ':notes'  => $additionalNotes !== '' ? $additionalNotes : null,
                            ':tprice' => $totalPrice,
                            ':oid'    => $targetOrderId
                        ]);

                        $pdo->prepare("DELETE FROM order_item WHERE order_id = :oid")->execute([':oid' => $targetOrderId]);
                    }

                    //order_item insertion for both new and updated orders
                    $insItem = $pdo->prepare("
                        INSERT INTO order_item (
                            order_id, product_id, order_type,
                            unit_price, quantity, subtotal
                        ) VALUES (
                            :oid, :pid, :otype,
                            :uprice, :qty, :sub
                        )
                    ");
                    $insItem->execute([
                        ':oid'    => $targetOrderId,
                        ':pid'    => $productId,
                        ':otype'  => $orderType,
                        ':uprice' => $unitPrice,
                        ':qty'    => $quantity,
                        ':sub'    => $subtotal
                    ]);

                    syncOrderPaymentRecord(
                        $pdo,
                        $targetOrderId,
                        $statusId,
                        $isUnpaidUtang,
                        $totalPrice,
                        $currentActor,
                        $additionalNotes
                    );

                    $pdo->commit();
                    header('Location: Batch_Orders.php?slot=' . $slotId . '&saved=' . urlencode($targetOrderId));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $batchStatusMessage = 'Database Error: ' . $e->getMessage();
                    $batchStatusType    = 'error';
                }
                }
            }
        }
    }

    // Query Slots (Counting Active Queued/In-Progress Containers where status_id NOT IN (5 = delivered/paid, 7 = cancelled))
    // & Prioritized Batch Orders (Priority Walk-In first, then Rescheduled, then Standard)
    $nowTime   = date('H:i:s');
    $phonesMap = getCustomerPhonesMap($pdo);

// Auto-sync slot_status 
    $syncSlotsStmt = $pdo->prepare("
        UPDATE time_slot ts
        LEFT JOIN (
            SELECT o.slot_id,
                   COALESCE(SUM(CASE WHEN o.status_id NOT IN (5, 7) THEN oi.quantity ELSE 0 END), 0) AS active_qty
            FROM order_information o
            LEFT JOIN order_item oi ON oi.order_id = o.order_id
            GROUP BY o.slot_id
        ) q ON q.slot_id = ts.slot_id
        SET ts.slot_status = CASE
            WHEN :nowTime >= ts.time_to THEN 'Closed'
            WHEN COALESCE(q.active_qty, 0) >= ts.batch_capacity THEN 'Full'
            ELSE 'Open'
        END
    ");
    $syncSlotsStmt->execute([':nowTime' => $nowTime]);

    $slotsData = $pdo->query("
        SELECT ts.slot_id,
               ts.slot_label,
               ts.time_from,
               ts.time_to,
               ts.batch_capacity,
               ts.slot_status,
               COALESCE(SUM(
                   CASE
                       WHEN o.status_id NOT IN (5, 7)
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

    $orderRows = $pdo->query("
        SELECT o.*,
               os.status_name,
               ts.slot_label,
               ts.time_from,
               ts.time_to,
               c.first_name AS cust_first_name,
               c.last_name AS cust_last_name,
               c.container_label,
               c.house_no,
               c.street_name,
               c.barangay,
               c.city,
               oi.product_id,
               oi.order_type,
               oi.unit_price,
               oi.quantity,
               oi.subtotal,
               cp.container_type,
               cp.container_name,
               p.payment_status,
               p.date_paid
        FROM order_information o
        INNER JOIN customer c ON c.customer_id = o.customer_id
        INNER JOIN time_slot ts ON ts.slot_id = o.slot_id
        INNER JOIN order_status os ON os.status_id = o.status_id
        LEFT JOIN order_item oi ON oi.order_id = o.order_id
        LEFT JOIN container_product cp ON cp.product_id = oi.product_id
        LEFT JOIN payment p ON p.order_id = o.order_id
        WHERE DATE(o.date_ordered) >= CURDATE()
           OR o.status_id NOT IN (5, 7)
           OR (o.status_id = 5 AND DATE(p.date_paid) = CURDATE())
        ORDER BY
           CASE
               WHEN o.service_mode LIKE 'Walk-In%' AND o.status_id NOT IN (5, 7) THEN 0
               WHEN o.status_id = 6 THEN 1
               WHEN o.status_id NOT IN (5, 7) THEN 2
               ELSE 3
           END ASC,
           o.date_ordered DESC,
           o.order_id ASC
    ")->fetchAll();

    $batchOrdersList = [];
    foreach ($orderRows as $row) {
        $cid       = (string)$row['customer_id'];
        $primaryPh = $phonesMap[$cid]['primary'] ?? '';
        $backupPh  = $phonesMap[$cid]['backup'] ?? '';
        $brgyNum   = preg_replace('/[^0-9]/', '', (string)$row['barangay']) ?: (string)$row['barangay'];

        $pId   = (string)($row['product_id'] ?? 'PRD-01');
        $oType = (string)($row['order_type'] ?? 'Refill Only');
        if ($oType === 'Other') {
            $selectProductVal = 'OTHER';
        } elseif ($oType === 'New Container + Refill') {
            $selectProductVal = "NEW-REFILL|{$pId}";
        } elseif ($oType === 'New Container Only') {
            $selectProductVal = "NEW|{$pId}";
        } else {
            $selectProductVal = "REFILL|{$pId}";
        }

        $qty        = (int)($row['quantity'] ?? 1);
        $totalPrice = (float)$row['total_price'];
        $cTypeLabel = ($oType === 'Other') ? 'Other' : (string)($row['container_type'] ?? '5-Gal - Slim');
        $stId       = (int)$row['status_id'];
        $delayHold  = (string)($row['delay_hold_notice'] ?? 'None');
        $payStat    = (string)($row['payment_status'] ?? '');
        $normMode   = normalizeStationServiceMode((string)$row['service_mode']);

        $isUtangOrder = (
            stripos($delayHold, 'Utang') !== false ||
            ($stId === 5 && $payStat === 'Unpaid')
        );

        $orderDateIso  = substr((string)$row['date_ordered'], 0, 10);
        $isCarriedOver = ($orderDateIso < date('Y-m-d')) && !in_array($stId, [5, 7], true);

        $slotTimeTo = (string)$row['time_to'];
        $requiresStatusChange = (($nowTime >= $slotTimeTo) || $isCarriedOver) && !in_array($stId, [5, 6, 7], true);

        $batchOrdersList[] = [
            'order_id'               => (string)$row['order_id'],
            'customer_id'            => $cid,
            'employee_id'            => (string)($row['employee_id'] ?? ''),
            'slot_id'                => (int)$row['slot_id'],
            'slot_label'             => (string)$row['slot_label'],
            'status_id'              => $stId,
            'status_select_val'      => $isUtangOrder ? '5_UNPAID' : (string)$stId,
            'status_name'            => $isUtangOrder ? 'Delivered (Unpaid / Utang)' : (string)$row['status_name'],
            'is_utang'               => $isUtangOrder,
            'is_rescheduled'         => ($stId === 6),
            'is_carried_over'        => $isCarriedOver,
            'is_priority_walkin'     => (stripos($normMode, 'Walk-In') === 0),
            'date_ordered'           => $orderDateIso,
            'requires_status_change' => $requiresStatusChange,
            'service_mode'           => $normMode,
            'inspection_status'      => (string)$row['inspection_status'],
            'delay_hold_notice'      => $delayHold,
            'additional_notes'       => (string)($row['additional_notes'] ?? ''),
            'customer_name'          => $row['cust_first_name'] . ' ' . $row['cust_last_name'],
            'container_label'        => (string)$row['container_label'],
            'primary_phone'          => $primaryPh,
            'backup_phone'           => $backupPh,
            'barangay_num'           => $brgyNum,
            'address_only'           => $row['house_no'] . ' ' . $row['street_name'] . ', ' . $row['city'],
            'brgy_address_cell'      => 'Brgy ' . $brgyNum . ' - ' . $row['house_no'] . ' ' . $row['street_name'] . ', ' . $row['city'],
            'gallon_price_cell'      => ($qty > 1 ? "{$qty}x " : '') . $cTypeLabel . ' | P' . number_format($totalPrice, 2),
            'product_select'         => $selectProductVal,
            'quantity'               => $qty,
            'total_price'            => number_format($totalPrice, 2, '.', '')
        ];
    }

    $employeesList   = $pdo->query("SELECT employee_id, first_name, last_name, emp_role FROM employee WHERE emp_status = 'Active' ORDER BY employee_id ASC")->fetchAll();
    $customersLookup = getActiveCustomersLookup($pdo, $phonesMap);
    $productsList    = $pdo->query("SELECT product_id, container_name, container_type, refill_price, new_container_price, availability_status FROM container_product ORDER BY product_id ASC")->fetchAll();

    $selectedSlotParam = isset($_GET['slot']) ? max(1, min(5, (int)$_GET['slot'])) : 1;
    if (!isset($_GET['slot']) && !empty($batchOrdersList)) {
        $selectedSlotParam = (int)$batchOrdersList[0]['slot_id'];
    }

    // FETCH API for Live Batch Queue & Slot Counters 
    if (isset($_GET['ajax_batch']) && $_GET['ajax_batch'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'    => 'ok',
            'slots'     => $slotsData,
            'orders'    => $batchOrdersList,
            'customers' => $customersLookup,
            'products'  => $productsList,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo "<script>\n";
    echo "  window.MAJESTIC_BATCH_DATA = " . json_encode([
        'selected_slot'      => $selectedSlotParam,
        'current_admin_user' => $currentActor,
        'slots'              => $slotsData,
        'orders'             => $batchOrdersList,
        'employees'          => $employeesList,
        'customers'          => $customersLookup,
        'products'           => $productsList,
        'flash'              => [
            'message' => $batchStatusMessage,
            'type'    => $batchStatusType
        ]
    ], JSON_UNESCAPED_UNICODE) . ";\n";
    echo "</script>\n";
}

/* ======================================================
   PAGE 5: ORDER RECORDS PAGE HANDLER (Order_Records.php)
   =======================================================*/
$recordStatusMessage = '';
$recordStatusType    = '';

if ($currentPageName === 'order_records.php') {
    $currentActor = getCurrentAdminActorLabel($pdo);

    if (isset($_GET['saved']) && $_GET['saved'] !== '') {
        $recordStatusMessage = 'Order Record & Payment Log for ' . htmlspecialchars((string)$_GET['saved']) . ' saved successfully.';
        $recordStatusType    = 'success';
    } elseif (isset($_GET['deleted']) && $_GET['deleted'] !== '') {
        $recordStatusMessage = 'Order Record ' . htmlspecialchars((string)$_GET['deleted']) . ' deleted successfully.';
        $recordStatusType    = 'success';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formAction         = trim((string)($_POST['form_action'] ?? 'save'));
        $selectedDateFilter = trim((string)($_POST['selected_date_filter'] ?? date('Y-m-d')));
        $orderId            = trim((string)($_POST['order_id'] ?? 'ORD-#'));
        $customerId         = trim((string)($_POST['customer_id'] ?? 'CUS-#'));
        $statusRaw          = trim((string)($_POST['status_id'] ?? '5'));
        $serviceModeRaw     = trim((string)($_POST['service_mode'] ?? 'Pickup/Delivery'));
        $orderChannelRaw    = trim((string)($_POST['order_channel'] ?? 'User/Admin Encoded'));
        $productOrderText   = trim((string)($_POST['product_order'] ?? '5-Gal - Slim Refill'));
        $quantityInput      = max(1, (int)($_POST['quantity'] ?? 1));
        $batchSlotText      = trim((string)($_POST['batch_slot'] ?? '1'));
        $customerName       = trim((string)($_POST['customer_name'] ?? ''));
        $address            = trim((string)($_POST['address'] ?? ''));
        $barangayRaw        = trim((string)($_POST['barangay'] ?? '22'));
        $cellphone          = trim((string)($_POST['cellphone'] ?? ''));
        $backupPhone        = trim((string)($_POST['backup_phone'] ?? ''));
        $containerLabel     = trim((string)($_POST['container_label'] ?? ''));
        $paymentStatusRaw   = trim((string)($_POST['payment_status'] ?? 'Paid'));
        $totalPaidRaw       = trim((string)($_POST['total_paid'] ?? '30.00'));
        $datePaidRaw        = trim((string)($_POST['date_paid'] ?? ''));
        $recordedByInput    = trim((string)($_POST['recorded_by'] ?? ''));
        $paymentNotes       = trim((string)($_POST['payment_notes'] ?? ''));

        $recordedByFinal = $recordedByInput !== '' ? $recordedByInput : $currentActor;

        // DELETE EXISTING ORDER RECORD
        if ($formAction === 'delete' && $orderId !== '' && $orderId !== 'ORD-#') {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM payment WHERE order_id = :oid")->execute([':oid' => $orderId]);
                $pdo->prepare("DELETE FROM order_item WHERE order_id = :oid")->execute([':oid' => $orderId]);
                $pdo->prepare("DELETE FROM order_information WHERE order_id = :oid")->execute([':oid' => $orderId]);
                $pdo->commit();

                $dateParam = $selectedDateFilter !== '' ? '&date=' . urlencode($selectedDateFilter) : '';
                header('Location: Order_Records.php?deleted=' . urlencode($orderId) . $dateParam);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $recordStatusMessage = 'Database Error: ' . $e->getMessage();
                $recordStatusType    = 'error';
            }
        }

        //  SAVE (ENCODE NEW OR UPDATE EXISTING) ORDER RECORD & CASH PAYMENT LOG
        if ($formAction === 'save') {
            if ($customerName === '' || $address === '' || $cellphone === '' || $productOrderText === '') {
                $recordStatusMessage = 'Incomplete Form: Please fill out Order Type, Quantity, Customer Name, Address, and Cellphone #.';
                $recordStatusType    = 'error';
            } elseif (!preg_match('/^09\d{9}$/', $cellphone)) {
                $recordStatusMessage = 'Invalid Cellphone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
                $recordStatusType    = 'error';
            } elseif ($backupPhone !== '' && !preg_match('/^09\d{9}$/', $backupPhone)) {
                $recordStatusMessage = 'Invalid Backup Phone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
                $recordStatusType    = 'error';
            } else {
                $serviceMode  = normalizeStationServiceMode($serviceModeRaw);
                $orderChannel = normalizeStationOrderChannel($orderChannelRaw);

                $slotId = 1;
                if (preg_match('/^[1-5]$/', $batchSlotText)) {
                    $slotId = (int)$batchSlotText;
                } elseif (stripos($batchSlotText, '10:00') !== false || stripos($batchSlotText, 'Batch 2') !== false) {
                    $slotId = 2;
                } elseif (stripos($batchSlotText, '12:00') !== false || stripos($batchSlotText, 'Batch 3') !== false) {
                    $slotId = 3;
                } elseif (stripos($batchSlotText, '2:00') !== false || stripos($batchSlotText, 'Batch 4') !== false) {
                    $slotId = 4;
                } elseif (stripos($batchSlotText, '4:00') !== false || stripos($batchSlotText, 'Batch 5') !== false) {
                    $slotId = 5;
                }

                $qty = $quantityInput;
                if ($qty === 1 && preg_match('/(\d+)\s*x/i', $productOrderText, $qm)) {
                    $qty = max(1, (int)$qm[1]);
                }

                $productId = (stripos($productOrderText, 'Round') !== false) ? 'PRD-02' : 'PRD-01';
                if (strcasecmp(trim($productOrderText), 'Other') === 0) {
                    $orderType = 'Other';
                } elseif (stripos($productOrderText, 'New') !== false && stripos($productOrderText, 'Refill') !== false) {
                    $orderType = 'New Container + Refill';
                } elseif (stripos($productOrderText, 'New') !== false) {
                    $orderType = 'New Container Only';
                } else {
                    $orderType = 'Refill Only';
                }

                $isStatusUtang = ($statusRaw === '5_UNPAID');
                $statusId      = $isStatusUtang ? 5 : max(1, min(7, (int)$statusRaw));

                if ($isStatusUtang || stripos($paymentStatusRaw, 'Unpaid') !== false || stripos($paymentStatusRaw, 'Utang') !== false) {
                    $paymentStatus = 'Unpaid';
                } elseif (stripos($paymentStatusRaw, 'Remit') !== false) {
                    $paymentStatus = 'Remitted';
                } else {
                    $paymentStatus = 'Paid';
                }

                $delayNoticeSql = ($paymentStatus === 'Unpaid' && ($isStatusUtang || $statusId === 5))
                    ? 'Unpaid / Utang'
                    : 'None';

                $defaultUnit = ($orderType === 'New Container Only') ? 250.00 : (($orderType === 'New Container + Refill') ? 280.00 : 30.00);
                $cleanedAmount = (float)preg_replace('/[^0-9.]/', '', $totalPaidRaw);
                if ($cleanedAmount <= 0) {
                    $cleanedAmount = $defaultUnit * $qty;
                }
                $unitPrice  = round($cleanedAmount / $qty, 2);
                $totalPrice = $cleanedAmount;
                $totalPaid  = ($paymentStatus === 'Unpaid') ? 0.00 : $totalPrice;

                $targetDateStr = ($datePaidRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePaidRaw))
                    ? $datePaidRaw
                    : (($selectedDateFilter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateFilter)) ? $selectedDateFilter : date('Y-m-d'));
                $datePaidSql = $targetDateStr . ' ' . date('H:i:s');

                try {
                    $pdo->beginTransaction();

                    $resolvedCustId = resolveOrCreateBatchCustomer(
                        $pdo,
                        $customerId,
                        $customerName,
                        $containerLabel,
                        $address,
                        $barangayRaw,
                        $cellphone,
                        $backupPhone,
                        $paymentNotes
                    );

                    $resolvedEmpId = !empty($_SESSION['user']['employee_id'])
                        ? (string)$_SESSION['user']['employee_id']
                        : ($_SESSION['employee_id'] ?? null);
                    if (!$resolvedEmpId) {
                        $firstEmp      = $pdo->query("SELECT employee_id FROM employee ORDER BY employee_id ASC LIMIT 1")->fetch();
                        $resolvedEmpId = $firstEmp ? (string)$firstEmp['employee_id'] : null;
                    }

                    $isNewOrder = ($orderId === '' || $orderId === 'ORD-#');

                    if ($isNewOrder) {
                        $targetOrderId = getNextFormattedId($pdo, 'order_information', 'order_id', 'ORD-');

                        $insOrd = $pdo->prepare("
                            INSERT INTO order_information (
                                order_id, customer_id, employee_id, slot_id, status_id,
                                order_channel, service_mode, inspection_status,
                                delay_hold_notice, additional_notes, total_price, date_ordered
                            ) VALUES (
                                :oid, :cid, :eid, :sid, :stid,
                                :chan, :smode, 'Good',
                                :delay, :notes, :tprice, :dord
                            )
                        ");
                        $insOrd->execute([
                            ':oid'    => $targetOrderId,
                            ':cid'    => $resolvedCustId,
                            ':eid'    => $resolvedEmpId,
                            ':sid'    => $slotId,
                            ':stid'   => $statusId,
                            ':chan'   => $orderChannel,
                            ':smode'  => $serviceMode,
                            ':delay'  => $delayNoticeSql,
                            ':notes'  => $paymentNotes !== '' ? $paymentNotes : null,
                            ':tprice' => $totalPrice,
                            ':dord'   => $datePaidSql
                        ]);
                    } else {
                        $targetOrderId = $orderId;

                        $updOrd = $pdo->prepare("
                            UPDATE order_information
                            SET customer_id = :cid,
                                slot_id = :sid,
                                status_id = :stid,
                                order_channel = :chan,
                                service_mode = :smode,
                                delay_hold_notice = :delay,
                                additional_notes = :notes,
                                total_price = :tprice
                            WHERE order_id = :oid
                        ");
                        $updOrd->execute([
                            ':cid'    => $resolvedCustId,
                            ':sid'    => $slotId,
                            ':stid'   => $statusId,
                            ':chan'   => $orderChannel,
                            ':smode'  => $serviceMode,
                            ':delay'  => $delayNoticeSql,
                            ':notes'  => $paymentNotes !== '' ? $paymentNotes : null,
                            ':tprice' => $totalPrice,
                            ':oid'    => $targetOrderId
                        ]);

                        $pdo->prepare("DELETE FROM order_item WHERE order_id = :oid")->execute([':oid' => $targetOrderId]);
                    }

                    // order_item insertion
                    $insItem = $pdo->prepare("
                        INSERT INTO order_item (
                            order_id, product_id, order_type,
                            unit_price, quantity, subtotal
                        ) VALUES (
                            :oid, :pid, :otype,
                            :uprice, :qty, :sub
                        )
                    ");
                    $insItem->execute([
                        ':oid'    => $targetOrderId,
                        ':pid'    => $productId,
                        ':otype'  => $orderType,
                        ':uprice' => $unitPrice,
                        ':qty'    => $qty,
                        ':sub'    => $totalPrice
                    ]);

                    $payChk = $pdo->prepare("SELECT payment_id FROM payment WHERE order_id = :oid LIMIT 1");
                    $payChk->execute([':oid' => $targetOrderId]);
                    if ($payChk->fetch()) {
                        $updPay = $pdo->prepare("
                            UPDATE payment
                            SET total_paid = :tpaid,
                                recorded_by = :rec,
                                payment_notes = :pnotes,
                                date_paid = :dpaid,
                                payment_status = :pstatus
                            WHERE order_id = :oid
                        ");
                        $updPay->execute([
                            ':tpaid'   => $totalPaid,
                            ':rec'     => $recordedByFinal,
                            ':pnotes'  => $paymentNotes !== '' ? $paymentNotes : null,
                            ':dpaid'   => $datePaidSql,
                            ':pstatus' => $paymentStatus,
                            ':oid'     => $targetOrderId
                        ]);
                    } else {
                        $newPayId = getNextFormattedId($pdo, 'payment', 'payment_id', 'PAY-');
                        $insPay   = $pdo->prepare("
                            INSERT INTO payment (
                                payment_id, order_id, payment_method,
                                total_paid, recorded_by, payment_notes,
                                date_paid, payment_status
                            ) VALUES (
                                :pid, :oid, 'Cash Only',
                                :tpaid, :rec, :pnotes,
                                :dpaid, :pstatus
                            )
                        ");
                        $insPay->execute([
                            ':pid'     => $newPayId,
                            ':oid'     => $targetOrderId,
                            ':tpaid'   => $totalPaid,
                            ':rec'     => $recordedByFinal,
                            ':pnotes'  => $paymentNotes !== '' ? $paymentNotes : null,
                            ':dpaid'   => $datePaidSql,
                            ':pstatus' => $paymentStatus
                        ]);
                    }

                    $pdo->commit();
                    header('Location: Order_Records.php?date=' . urlencode($targetDateStr) . '&saved=' . urlencode($targetOrderId));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $recordStatusMessage = 'Database Error: ' . $e->getMessage();
                    $recordStatusType    = 'error';
                }
            }
        }
    }

    // Query All Order Records & Payment Logs from MySQL
    $phonesMap = getCustomerPhonesMap($pdo);

    $recRows = $pdo->query("
        SELECT o.*,
               os.status_name,
               ts.slot_label,
               c.first_name AS cust_first_name,
               c.last_name AS cust_last_name,
               c.container_label,
               c.house_no,
               c.street_name,
               c.barangay,
               c.city,
               oi.product_id,
               oi.order_type,
               oi.unit_price,
               oi.quantity,
               oi.subtotal,
               cp.container_type,
               p.payment_id,
               p.total_paid,
               p.recorded_by,
               p.payment_notes,
               p.date_paid,
               p.payment_status
        FROM order_information o
        INNER JOIN customer c ON c.customer_id = o.customer_id
        INNER JOIN time_slot ts ON ts.slot_id = o.slot_id
        INNER JOIN order_status os ON os.status_id = o.status_id
        LEFT JOIN order_item oi ON oi.order_id = o.order_id
        LEFT JOIN container_product cp ON cp.product_id = oi.product_id
        LEFT JOIN payment p ON p.order_id = o.order_id
        ORDER BY COALESCE(p.date_paid, o.date_ordered) DESC, o.order_id DESC
    ")->fetchAll();

    $orderRecordsList = [];
    $availableDates   = [];
    foreach ($recRows as $r) {
        $cid       = (string)$r['customer_id'];
        $primaryPh = $phonesMap[$cid]['primary'] ?? '';
        $backupPh  = $phonesMap[$cid]['backup'] ?? '';

        $brgyNum = preg_replace('/[^0-9]/', '', (string)$r['barangay']) ?: (string)$r['barangay'];
        $qty     = (int)($r['quantity'] ?? 1);
        $cType   = (string)($r['container_type'] ?? '5-Gal - Slim');
        $oType   = (string)($r['order_type'] ?? 'Refill Only');
        if ($oType === 'Other') {
            $baseItem = 'Other';
        } elseif ($oType === 'New Container + Refill') {
            $baseItem = "New {$cType} + Refill";
        } elseif ($oType === 'New Container Only') {
            $baseItem = "New {$cType}";
        } else {
            $baseItem = "{$cType} Refill";
        }
        $shortItem = ($qty > 1) ? "{$qty}x {$baseItem}" : $baseItem;

        $stId      = (int)$r['status_id'];
        $delayHold = (string)($r['delay_hold_notice'] ?? 'None');
        $payStatus = (string)($r['payment_status'] ?? '');

        $isUtangOrder = (
            stripos($delayHold, 'Utang') !== false ||
            ($stId === 5 && $payStatus === 'Unpaid')
        );

        if ($isUtangOrder) {
            $payStatus = 'Unpaid';
        } elseif ($payStatus === '') {
            $payStatus = ($stId === 5) ? 'Paid' : 'Unpaid';
        }

        $rawDateOrderedStr = substr((string)$r['date_ordered'], 0, 10);
        $datePaidStr       = !empty($r['date_paid']) ? substr((string)$r['date_paid'], 0, 10) : $rawDateOrderedStr;

        $effectiveRecordDate = ($stId === 5 && $datePaidStr !== '') ? $datePaidStr : $rawDateOrderedStr;

        if ($effectiveRecordDate !== '' && !in_array($effectiveRecordDate, $availableDates, true)) {
            $availableDates[] = $effectiveRecordDate;
        }

        $orderRecordsList[] = [
            'order_id'              => (string)$r['order_id'],
            'customer_id'           => $cid,
            'customer_tag_cell'     => $cid . ' - ' . $r['container_label'],
            'customer_name'         => $r['cust_first_name'] . ' ' . $r['cust_last_name'],
            'container_label'       => (string)$r['container_label'],
            'primary_phone'         => $primaryPh,
            'backup_phone'          => $backupPh,
            'address_only'          => $r['house_no'] . ' ' . $r['street_name'] . ', ' . $r['city'],
            'barangay_num'          => $brgyNum,
            'brgy_address_cell'     => 'Brgy ' . $brgyNum . ' - ' . $r['house_no'] . ' ' . $r['street_name'] . ', ' . $r['city'],
            'service_mode'          => normalizeStationServiceMode((string)$r['service_mode']),
            'order_channel'         => normalizeStationOrderChannel((string)$r['order_channel']),
            'slot_id'               => (int)$r['slot_id'],
            'slot_label'            => 'Batch ' . (int)$r['slot_id'] . ': ' . (string)$r['slot_label'],
            'batch_filter_key'      => 'Batch ' . (int)$r['slot_id'],
            'status_id'             => $stId,
            'status_select_val'     => $isUtangOrder ? '5_UNPAID' : (string)$stId,
            'status_name'           => $isUtangOrder ? 'Delivered (Unpaid / Utang)' : (string)$r['status_name'],
            'is_utang'              => $isUtangOrder,
            'delay_hold_notice'     => $delayHold,
            'base_order_type'       => $baseItem,
            'order_summary'         => $shortItem,
            'quantity'              => $qty,
            'total_price'           => number_format((float)$r['total_price'], 2, '.', ''),
            'payment_status'        => $payStatus,
            'total_paid'            => number_format((float)($r['total_paid'] ?? $r['total_price']), 2, '.', ''),
            'date_ordered'          => $effectiveRecordDate,
            'original_date_ordered' => $rawDateOrderedStr,
            'date_paid'             => $datePaidStr,
            'recorded_by'           => (string)($r['recorded_by'] ?? $currentActor),
            'payment_notes'         => (string)($r['payment_notes'] ?? $r['additional_notes'] ?? '')
        ];
    }

    $customersLookup = getActiveCustomersLookup($pdo, $phonesMap);

    $initialDate = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['date'])
        ? (string)$_GET['date']
        : date('Y-m-d');
    
        // FETCH API for Live Order Records & Cash Audit Log 
    if (isset($_GET['ajax_records']) && $_GET['ajax_records'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'          => 'ok',
            'available_dates' => $availableDates,
            'records'         => $orderRecordsList,
            'customers'       => $customersLookup,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo "<script>\n";
    echo "  window.MAJESTIC_RECORDS_DATA = " . json_encode([
        'today_iso'          => date('Y-m-d'),
        'today_label'        => date('M j, Y'),
        'selected_date'      => $initialDate,
        'current_admin_user' => $currentActor,
        'available_dates'    => $availableDates,
        'records'            => $orderRecordsList,
        'customers'          => $customersLookup,
        'flash'              => [
            'message' => $recordStatusMessage,
            'type'    => $recordStatusType
        ]
    ], JSON_UNESCAPED_UNICODE) . ";\n";
    echo "</script>\n";
}
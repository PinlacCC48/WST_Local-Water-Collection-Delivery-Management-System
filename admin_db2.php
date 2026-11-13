<?php
/* ============================
   Database Connection for:
   1. Admin_Dashboard.php
   2. Accounts.php
   =============================*/
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

$currentPageName = strtolower(basename($_SERVER['PHP_SELF'] ?? ''));

/* ===============================================
   PAGE 1: ADMIN DASHBOARD (Admin_Dashboard.php)
   =============================================== */
$dashboardDateHeading = 'Station Operation Overview - Today (' . date('M j, Y') . ')';
$kpiActiveBatchTime   = 'Station is Closed';
$kpiActiveBatchSub    = '0/20 Containers Queued (8AM - 6 PM)';
$kpiPendingCount      = '0 Orders';
$kpiPendingBreakdown  = '0 Walk-In/Pickup | 0 Pickup | 0 Refill | 0 Out';
$kpiCashSales         = 'P0.00';
$kpiCashSub           = '0 Walk-in & Rider Cash Remitted';
$kpiGallonOutput      = '0/100 Gal';
$kpiGallonSub         = '0 Refills | 0 New Gallon';

$batchBarsData = [
    1 => ['status_text' => 'Open (0/20 Containers)', 'fill_pct' => 0],
    2 => ['status_text' => 'Open (0/20 Containers)', 'fill_pct' => 0],
    3 => ['status_text' => 'Open (0/20 Containers)', 'fill_pct' => 0],
    4 => ['status_text' => 'Open (0/20 Containers)', 'fill_pct' => 0],
    5 => ['status_text' => 'Open (0/20 Containers)', 'fill_pct' => 0],
];

if ($currentPageName === 'admin_dashboard.php') {
    $nowTime = date('H:i:s');

    // Fetch  Time Slots & Today's + Carried-Over Rescheduled/Pending + Completed-Today Quantities
    $slotStmt = $pdo->query("
        SELECT ts.slot_id,
               ts.slot_label,
               ts.time_from,
               ts.time_to,
               ts.batch_capacity,
               ts.slot_status,
               COALESCE(SUM(
                   CASE
                       WHEN o.status_id != 7 AND (
                           DATE(o.date_ordered) >= CURDATE()
                           OR o.status_id != 5
                           OR (o.status_id = 5 AND EXISTS (
                               SELECT 1 FROM payment p
                               WHERE p.order_id = o.order_id AND DATE(p.date_paid) = CURDATE()
                           ))
                       )
                       THEN oi.quantity
                       ELSE 0
                   END
               ), 0) AS queued_containers
        FROM time_slot ts
        LEFT JOIN order_information o ON o.slot_id = ts.slot_id
        LEFT JOIN order_item oi ON oi.order_id = o.order_id
        GROUP BY ts.slot_id, ts.slot_label, ts.time_from, ts.time_to, ts.batch_capacity, ts.slot_status
        ORDER BY ts.slot_id ASC
    ");
    $slots = $slotStmt->fetchAll();

    foreach ($slots as $slot) {
        $slotId   = (int)$slot['slot_id'];
        $capacity = (int)$slot['batch_capacity'] ?: 20;
        $queued   = (int)$slot['queued_containers'];
        $timeFrom = (string)$slot['time_from'];
        $timeTo   = (string)$slot['time_to'];
        $label    = (string)$slot['slot_label'];

        $fillPct = min(100, (int)round(($queued / $capacity) * 100));

        if ($nowTime >= $timeFrom && $nowTime < $timeTo) {
            $kpiActiveBatchTime = $label;
            $kpiActiveBatchSub  = "{$queued}/{$capacity} Containers Queued (8AM - 6 PM)";
            $barState           = $queued >= $capacity ? 'Full' : 'Active';
        } elseif ($nowTime >= $timeTo) {
            $barState = 'Completed';
        } else {
            $barState = $queued >= $capacity ? 'Full' : 'Open';
        }

        $batchBarsData[$slotId] = [
            'status_text' => "{$barState} ({$queued}/{$capacity} Containers)",
            'fill_pct'    => $fillPct
        ];
    }

    // Fetch Orders Pending Action
    $pendingRows = $pdo->query("
        SELECT o.status_id, o.service_mode
        FROM order_information o
        WHERE o.status_id NOT IN (5, 7)
    ")->fetchAll();

    $totalPendingCount = count($pendingRows);
    $walkInPickupCount = 0;
    $pickupCount       = 0;
    $refillCount       = 0;
    $outCount          = 0;

    foreach ($pendingRows as $pRow) {
        $stId     = (int)$pRow['status_id'];
        $sMode    = (string)$pRow['service_mode'];
        $isWalkIn = ($sMode === 'Walk-In/Pickup' || $sMode === 'Walk-In');

        if ($stId === 4) {
            $outCount++;
        } elseif ($stId === 3) {
            if ($isWalkIn) {
                $walkInPickupCount++;
            } else {
                $refillCount++;
            }
        } else {
            if ($isWalkIn) {
                $walkInPickupCount++;
            } else {
                $pickupCount++;
            }
        }
    }

    $kpiPendingCount     = "{$totalPendingCount} Orders";
    $kpiPendingBreakdown = "{$walkInPickupCount} Walk-In/Pickup | {$pickupCount} Pickup | {$refillCount} Refill | {$outCount} Out";

    //Today's Cash Sales Log
    $cashRow = $pdo->query("
        SELECT COALESCE(SUM(p.total_paid), 0) AS total_cash,
               COUNT(p.payment_id) AS remitted_count
        FROM payment p
        WHERE DATE(p.date_paid) = CURDATE()
          AND p.payment_status IN ('Paid', 'Remitted')
    ")->fetch();

    $todayCashTotal = (float)($cashRow['total_cash'] ?? 0);
    $remittedCount  = (int)($cashRow['remitted_count'] ?? 0);

    $kpiCashSales = 'P' . number_format($todayCashTotal, 2);
    $kpiCashSub   = "{$remittedCount} Walk-in & Rider Cash Remitted";

    //Daily Gallon Output (Includes orders placed today OR carried-over orders completed/paid today)
    $gallonRows = $pdo->query("
        SELECT oi.order_type, COALESCE(SUM(oi.quantity), 0) AS qty_sum
        FROM order_item oi
        INNER JOIN order_information o ON o.order_id = oi.order_id
        LEFT JOIN payment p ON p.order_id = o.order_id
        WHERE o.status_id != 7
          AND (
              DATE(o.date_ordered) = CURDATE()
              OR (o.status_id = 5 AND DATE(p.date_paid) = CURDATE())
          )
        GROUP BY oi.order_type
    ")->fetchAll();

    $refillGallons = 0;
    $newGallons    = 0;
    foreach ($gallonRows as $gRow) {
        $oType = (string)$gRow['order_type'];
        $qSum  = (int)$gRow['qty_sum'];
        if ($oType === 'Refill Only') {
            $refillGallons += $qSum;
        } elseif ($oType === 'New Container Only') {
            $newGallons += $qSum;
        } elseif ($oType === 'New Container + Refill') {
            $refillGallons += $qSum;
            $newGallons    += $qSum;
        }
    }
    $totalGallonsOut = $refillGallons + $newGallons;

    $kpiGallonOutput = "{$totalGallonsOut}/100 Gal";
    $kpiGallonSub    = "{$refillGallons} Refills | {$newGallons} New Gallon";


// FETCH API for Live Admin Dashboard 
    if (isset($_GET['ajax_dashboard']) && $_GET['ajax_dashboard'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'               => 'ok',
            'server_time'          => date('h:i:s A'),
            'kpi_active_batch'     => $kpiActiveBatchTime,
            'kpi_active_batch_sub' => $kpiActiveBatchSub,
            'kpi_pending_count'    => $kpiPendingCount,
            'kpi_pending_sub'      => $kpiPendingBreakdown,
            'kpi_cash_sales'       => $kpiCashSales,
            'kpi_cash_sub'         => $kpiCashSub,
            'kpi_gallon_output'    => $kpiGallonOutput,
            'kpi_gallon_sub'       => $kpiGallonSub,
            'batch_bars'           => $batchBarsData,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }}

/* =======================================
   PAGE 2: ACCOUNTS PAGE (Accounts.php)
   ======================================= */
$accountStatusMessage  = '';
$accountStatusType     = '';
$accountsListJson      = '[]';
$totalAccountsCount    = 0;
$customerAccountsCount = 0;
$staffAccountsCount    = 0;

if ($currentPageName === 'accounts.php') {
    $setAccountStatus = static function (string $msg, string $type = 'error') use (&$accountStatusMessage, &$accountStatusType): void {
        $accountStatusMessage = $msg;
        $accountStatusType    = $type;
    };

    if (isset($_GET['saved']) && $_GET['saved'] !== '') {
        $setAccountStatus('Account ' . htmlspecialchars((string)$_GET['saved']) . ' saved successfully.', 'success');
    } elseif (isset($_GET['deleted']) && $_GET['deleted'] !== '') {
        $setAccountStatus('Account ' . htmlspecialchars((string)$_GET['deleted']) . ' deleted successfully.', 'success');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formAction     = trim((string)($_POST['form_action'] ?? 'save'));
        $accountId      = trim((string)($_POST['account_id'] ?? 'ID-#'));
        $role           = trim((string)($_POST['role'] ?? 'Customer'));
        $firstName      = trim((string)($_POST['first_name'] ?? ''));
        $lastName       = trim((string)($_POST['last_name'] ?? ''));
        $containerLabel = trim((string)($_POST['container_label'] ?? ''));
        $houseNo        = trim((string)($_POST['house_no'] ?? ''));
        $streetName     = trim((string)($_POST['street_name'] ?? ''));
        $barangayRaw    = trim((string)($_POST['barangay'] ?? ''));
        $emailAddress   = trim((string)($_POST['email_address'] ?? ''));
        $primaryPhone   = trim((string)($_POST['primary_phone'] ?? ''));
        $backupPhone    = trim((string)($_POST['backup_phone'] ?? ''));
        $gateRiderNote  = trim((string)($_POST['gate_rider_note'] ?? ''));
        $status         = ($status = trim((string)($_POST['status'] ?? 'Active'))) === 'Inactive' ? 'Inactive' : 'Active';
        $password       = trim((string)($_POST['password'] ?? ''));

        $barangay = preg_match('/^\d+$/', $barangayRaw) ? 'Barangay ' . $barangayRaw : $barangayRaw;

        // DELETE EXISTING ACCOUNT
        if ($formAction === 'delete' && $accountId !== '' && $accountId !== 'ID-#') {
            if (stripos($accountId, 'EMP-') === 0) {
                $activeEmpCount = (int)$pdo->query("SELECT COUNT(*) FROM employee WHERE emp_status = 'Active'")->fetchColumn();
                $currentLoggedEmp = (string)($_SESSION['employee_id'] ?? $_SESSION['user']['employee_id'] ?? '');

                if (strcasecmp($accountId, 'EMP-0001') === 0) {
                    $setAccountStatus('Protected Account: Primary Administrator account (EMP-0001) cannot be deleted.');
                } elseif (strcasecmp($accountId, $currentLoggedEmp) === 0) {
                    $setAccountStatus('Action Blocked: You cannot delete the staff/admin account you are currently logged in with.');
                } elseif ($activeEmpCount <= 1) {
                    $setAccountStatus('Action Blocked: At least 1 active Admin/Staff account must remain in the system.');
                } else {
                    try {
                        $pdo->beginTransaction();
                        $pdo->prepare("DELETE FROM employee_cp_num WHERE employee_id = :id")->execute([':id' => $accountId]);
                        $pdo->prepare("DELETE FROM employee WHERE employee_id = :id")->execute([':id' => $accountId]);
                        $pdo->commit();
                        header('Location: Accounts.php?deleted=' . urlencode($accountId));
                        exit;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $setAccountStatus('Cannot delete staff account ' . htmlspecialchars($accountId) . ' because it has linked order records. Set its Status to Inactive instead.');
                    }
                }
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM customer_cp_num WHERE customer_id = :id")->execute([':id' => $accountId]);
                    $pdo->prepare("DELETE FROM customer WHERE customer_id = :id")->execute([':id' => $accountId]);
                    $pdo->commit();
                    header('Location: Accounts.php?deleted=' . urlencode($accountId));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $setAccountStatus('Cannot delete customer account ' . htmlspecialchars($accountId) . ' because it has linked order records. Set its Status to Inactive instead.');
                }
            }
        }

        // ADD NEW OR UPDATE EXISTING ACCOUNT
        if ($formAction === 'save') {
            $isCustomerRole = ($role === 'Customer');
            $isNewRecord    = ($accountId === '' || $accountId === 'ID-#');
            $excludeId      = $isNewRecord ? '' : $accountId;

            if (
                $firstName === '' || $lastName === '' ||
                $houseNo === '' || $streetName === '' || $barangay === '' ||
                $emailAddress === '' || $primaryPhone === '' ||
                ($isCustomerRole && $containerLabel === '')
            ) {
                $setAccountStatus('Incomplete Form: Please fill out all required fields marked with (*).');
            } elseif (!$isCustomerRole && strcasecmp($accountId, 'EMP-0001') === 0 && $status === 'Inactive') {
                $setAccountStatus('Protected Account: Primary Administrator (EMP-0001) must remain Active so you never lose portal access.');
            } elseif (!preg_match('/^09\d{9}$/', $primaryPhone)) {
                $setAccountStatus('Invalid Cellphone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).');
            } elseif ($backupPhone !== '' && !preg_match('/^09\d{9}$/', $backupPhone)) {
                $setAccountStatus('Invalid Backup Phone #: Must be an 11-digit number starting with 09 (e.g., 09171234567).');
            } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $setAccountStatus('Invalid Email Address: Please enter a valid email address.');
            } elseif ($isNewRecord && $password === '') {
                $setAccountStatus('Password Required: Please enter a password when creating a new account.');
            } else {
                $dupEmailStmt = $pdo->prepare("
                    SELECT customer_id AS acct_id FROM customer WHERE LOWER(email_address) = LOWER(:em1) AND customer_id != :id1
                    UNION ALL
                    SELECT employee_id AS acct_id FROM employee WHERE LOWER(email_address) = LOWER(:em2) AND employee_id != :id2
                    LIMIT 1
                ");
                $dupEmailStmt->execute([
                    ':em1' => $emailAddress,
                    ':id1' => $excludeId,
                    ':em2' => $emailAddress,
                    ':id2' => $excludeId
                ]);

                $tagConflict = false;
                if ($isCustomerRole) {
                    $chkTagStmt = $pdo->prepare("
                        SELECT customer_id
                        FROM customer
                        WHERE LOWER(container_label) = LOWER(:lbl)
                          AND customer_id != :cid
                        LIMIT 1
                    ");
                    $chkTagStmt->execute([':lbl' => $containerLabel, ':cid' => $excludeId]);
                    $tagConflict = (bool)$chkTagStmt->fetch();
                }

                if ($dupEmailStmt->fetch()) {
                    $setAccountStatus('Email Already Registered: "' . htmlspecialchars($emailAddress) . '" is already used by another account.');
                } elseif ($tagConflict) {
                    $setAccountStatus('Tag Name Taken: The Nickname (Gallon Tag) "' . htmlspecialchars($containerLabel) . '" is already taken by another customer. Please choose a different tag name.');
                } else {
                    try {
                        $pdo->beginTransaction();

                        if ($isCustomerRole) {
                            if ($isNewRecord || stripos($accountId, 'CUS-') !== 0) {
                                $maxNum = (int)$pdo->query("
                                    SELECT COALESCE(MAX(CAST(SUBSTRING(customer_id, 5) AS UNSIGNED)), 0)
                                    FROM customer
                                    WHERE customer_id LIKE 'CUS-%'
                                ")->fetchColumn();
                                $targetId = sprintf('CUS-%04d', $maxNum + 1);

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
                                        CURRENT_DATE, :st
                                    )
                                ");
                                $insCust->execute([
                                    ':cid'    => $targetId,
                                    ':fname'  => $firstName,
                                    ':lname'  => $lastName,
                                    ':label'  => $containerLabel,
                                    ':house'  => $houseNo,
                                    ':street' => $streetName,
                                    ':brgy'   => $barangay,
                                    ':email'  => $emailAddress,
                                    ':phash'  => $password,
                                    ':gate'   => $gateRiderNote !== '' ? $gateRiderNote : null,
                                    ':st'     => $status
                                ]);
                            } else {
                                $targetId = $accountId;
                                $updCust = $pdo->prepare("
                                    UPDATE customer
                                    SET first_name = :fname,
                                        last_name = :lname,
                                        container_label = :label,
                                        house_no = :house,
                                        street_name = :street,
                                        barangay = :brgy,
                                        email_address = :email,
                                        password_hash = COALESCE(NULLIF(:phash, ''), password_hash),
                                        gate_rider_note = :gate,
                                        cust_status = :st
                                    WHERE customer_id = :cid
                                ");
                                $updCust->execute([
                                    ':fname'  => $firstName,
                                    ':lname'  => $lastName,
                                    ':label'  => $containerLabel,
                                    ':house'  => $houseNo,
                                    ':street' => $streetName,
                                    ':brgy'   => $barangay,
                                    ':email'  => $emailAddress,
                                    ':phash'  => $password,
                                    ':gate'   => $gateRiderNote !== '' ? $gateRiderNote : null,
                                    ':st'     => $status,
                                    ':cid'    => $targetId
                                ]);
                            }

                            $pdo->prepare("DELETE FROM customer_cp_num WHERE customer_id = :cid")->execute([':cid' => $targetId]);
                            $insPhone = $pdo->prepare("INSERT INTO customer_cp_num (customer_id, cp_num, cp_type) VALUES (:cid, :cp, :ctype)");
                            $insPhone->execute([':cid' => $targetId, ':cp' => $primaryPhone, ':ctype' => 'Primary']);
                            if ($backupPhone !== '' && $backupPhone !== $primaryPhone) {
                                $insPhone->execute([':cid' => $targetId, ':cp' => $backupPhone, ':ctype' => 'Backup']);
                            }
                        } else {
                            $validEmpRole = in_array($role, ['Admin', 'Bookkeeper', 'Delivery Rider', 'Dishwasher'], true)
                                ? $role
                                : 'Admin';

                            if ($isNewRecord || stripos($accountId, 'EMP-') !== 0) {
                                $maxNum = (int)$pdo->query("
                                    SELECT COALESCE(MAX(CAST(SUBSTRING(employee_id, 5) AS UNSIGNED)), 0)
                                    FROM employee
                                    WHERE employee_id LIKE 'EMP-%'
                                ")->fetchColumn();
                                $targetId = sprintf('EMP-%04d', $maxNum + 1);

                                $insEmp = $pdo->prepare("
                                    INSERT INTO employee (
                                        employee_id, first_name, last_name,
                                        house_no, street_name, barangay, city,
                                        email_address, password_hash, emp_role,
                                        date_hired, emp_status, manager_id
                                    ) VALUES (
                                        :eid, :fname, :lname,
                                        :house, :street, :brgy, 'Caloocan City',
                                        :email, :phash, :role,
                                        CURRENT_DATE, :st, NULL
                                    )
                                ");
                                $insEmp->execute([
                                    ':eid'    => $targetId,
                                    ':fname'  => $firstName,
                                    ':lname'  => $lastName,
                                    ':house'  => $houseNo,
                                    ':street' => $streetName,
                                    ':brgy'   => $barangay,
                                    ':email'  => $emailAddress,
                                    ':phash'  => $password,
                                    ':role'   => $validEmpRole,
                                    ':st'     => $status
                                ]);
                            } else {
                                $targetId = $accountId;
                                $updEmp = $pdo->prepare("
                                    UPDATE employee
                                    SET first_name = :fname,
                                        last_name = :lname,
                                        house_no = :house,
                                        street_name = :street,
                                        barangay = :brgy,
                                        email_address = :email,
                                        password_hash = COALESCE(NULLIF(:phash, ''), password_hash),
                                        emp_role = :role,
                                        emp_status = :st
                                    WHERE employee_id = :eid
                                ");
                                $updEmp->execute([
                                    ':fname'  => $firstName,
                                    ':lname'  => $lastName,
                                    ':house'  => $houseNo,
                                    ':street' => $streetName,
                                    ':brgy'   => $barangay,
                                    ':email'  => $emailAddress,
                                    ':phash'  => $password,
                                    ':role'   => $validEmpRole,
                                    ':st'     => $status,
                                    ':eid'    => $targetId
                                ]);
                            }

                            $pdo->prepare("DELETE FROM employee_cp_num WHERE employee_id = :eid")->execute([':eid' => $targetId]);
                            $insEmpPhone = $pdo->prepare("INSERT INTO employee_cp_num (employee_id, cp_num) VALUES (:eid, :cp)");
                            $insEmpPhone->execute([':eid' => $targetId, ':cp' => $primaryPhone]);
                            if ($backupPhone !== '' && $backupPhone !== $primaryPhone) {
                                $insEmpPhone->execute([':eid' => $targetId, ':cp' => $backupPhone]);
                            }
                        }

                        $pdo->commit();
                        header('Location: Accounts.php?saved=' . urlencode($targetId));
                        exit;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $setAccountStatus('Database Error: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    // fetch all Employee & Customer Phone Numbers in per 2-hour batch queries
    $empPhonesMap = [];
    foreach ($pdo->query("SELECT employee_id, cp_num FROM employee_cp_num")->fetchAll() as $ep) {
        $empPhonesMap[(string)$ep['employee_id']][] = (string)$ep['cp_num'];
    }

    $custPhonesMap = [];
    foreach ($pdo->query("SELECT customer_id, cp_type, cp_num FROM customer_cp_num")->fetchAll() as $cp) {
        $cidKey = (string)$cp['customer_id'];
        if (($cp['cp_type'] ?? '') === 'Primary') {
            $custPhonesMap[$cidKey]['primary'] = (string)$cp['cp_num'];
        } elseif (($cp['cp_type'] ?? '') === 'Backup') {
            $custPhonesMap[$cidKey]['backup'] = (string)$cp['cp_num'];
        }
    }

    // Query All Employees + Customers to Display in Accounts Directory
    $allAccounts = [];

    $empRows = $pdo->query("SELECT * FROM employee ORDER BY employee_id ASC")->fetchAll();
    foreach ($empRows as $emp) {
        $eid            = (string)$emp['employee_id'];
        $phones         = $empPhonesMap[$eid] ?? [];
        $brgyShort      = preg_replace('/[^0-9]/', '', (string)$emp['barangay']) ?: (string)$emp['barangay'];
        $empAddrDisplay = $emp['house_no'] . ' ' . $emp['street_name'] . ', ' . $emp['city'];

        $allAccounts[] = [
            'account_id'      => $eid,
            'id'              => $eid,
            'category'        => 'Staff',
            'account_type'    => 'Staff',
            'role'            => (string)$emp['emp_role'],
            'first_name'      => (string)$emp['first_name'],
            'last_name'       => (string)$emp['last_name'],
            'full_name'       => $emp['first_name'] . ' ' . $emp['last_name'],
            'container_label' => 'Staff',
            'house_no'        => (string)$emp['house_no'],
            'street_name'     => (string)$emp['street_name'],
            'barangay'        => $brgyShort,
            'barangay_full'   => (string)$emp['barangay'],
            'barangay_num'    => $brgyShort,
            'city'            => (string)$emp['city'],
            'address_display' => $empAddrDisplay,
            'address'         => $empAddrDisplay,
            'email_address'   => (string)$emp['email_address'],
            'primary_phone'   => (string)($phones[0] ?? ''),
            'backup_phone'    => (string)($phones[1] ?? ''),
            'gate_rider_note' => '',
            'status'          => (string)$emp['emp_status'],
            'password'        => (strpos((string)$emp['password_hash'], '$2') === 0) ? 'admin12345' : (string)$emp['password_hash']
        ];
        $staffAccountsCount++;
    }

    $custRows = $pdo->query("SELECT * FROM customer ORDER BY customer_id ASC")->fetchAll();
    foreach ($custRows as $cust) {
        $cid             = (string)$cust['customer_id'];
        $primaryPh       = $custPhonesMap[$cid]['primary'] ?? '';
        $backupPh        = $custPhonesMap[$cid]['backup'] ?? '';
        $brgyShort       = preg_replace('/[^0-9]/', '', (string)$cust['barangay']) ?: (string)$cust['barangay'];
        $custAddrDisplay = $cust['house_no'] . ' ' . $cust['street_name'] . ', ' . $cust['city'];

        $allAccounts[] = [
            'account_id'      => $cid,
            'id'              => $cid,
            'category'        => 'Customer',
            'account_type'    => 'Customer',
            'role'            => 'Customer',
            'first_name'      => (string)$cust['first_name'],
            'last_name'       => (string)$cust['last_name'],
            'full_name'       => $cust['first_name'] . ' ' . $cust['last_name'],
            'container_label' => (string)$cust['container_label'],
            'house_no'        => (string)$cust['house_no'],
            'street_name'     => (string)$cust['street_name'],
            'barangay'        => $brgyShort,
            'barangay_full'   => (string)$cust['barangay'],
            'barangay_num'    => $brgyShort,
            'city'            => (string)$cust['city'],
            'address_display' => $custAddrDisplay,
            'address'         => $custAddrDisplay,
            'email_address'   => (string)$cust['email_address'],
            'primary_phone'   => $primaryPh,
            'backup_phone'    => $backupPh,
            'gate_rider_note' => (string)($cust['gate_rider_note'] ?? ''),
            'status'          => (string)$cust['cust_status'],
            'password'        => (strpos((string)$cust['password_hash'], '$2') === 0) ? 'customer123' : (string)$cust['password_hash']
        ];
        $customerAccountsCount++;
    }

    $totalAccountsCount = $staffAccountsCount + $customerAccountsCount;
    $accountsListJson   = json_encode($allAccounts, JSON_UNESCAPED_UNICODE) ?: '[]';

    echo "<script>\n";
    echo "  window.MAJESTIC_ACCOUNTS_DATA = " . $accountsListJson . ";\n";
    echo "  window.MAJESTIC_ACCOUNTS_FLASH = " . json_encode([
        'message' => $accountStatusMessage,
        'type'    => $accountStatusType
    ], JSON_UNESCAPED_UNICODE) . ";\n";
    echo "</script>\n";
}
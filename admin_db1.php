<?php
/* ===========================
Database Connection for:
   1. Login.php
   2. Register.php
   ===========================*/
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/csrf_helper.php';

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

/* =========   PASSWORD CHECK for PASSWORDS   ========= */
function verifyAndUpgradePassword(PDO $pdo, string $table, string $idCol, string $id, string $typed, string $stored): bool {
    if (password_verify($typed, $stored)) {
        return true;
    }
    // Old accounts saved before hashing was added: accept once, then re-save as a hash.
    $isOldPlainText = (password_get_info($stored)['algo'] ?? null) === null;
    if ($isOldPlainText && hash_equals($stored, $typed)) {
        $upgrade = $pdo->prepare("UPDATE {$table} SET password_hash = :h WHERE {$idCol} = :id");
        $upgrade->execute([':h' => password_hash($typed, PASSWORD_DEFAULT), ':id' => $id]);
        return true;
    }
    return false;
}

/* =========   SHARED VIEW VARIABLES FOR LOGIN & REGISTER  ========== */
$statusMessage         = '';
$statusType            = '';
$loginStatusMessage    = '';
$loginStatusType       = '';
$registerStatusMessage = '';
$registerStatusType    = '';
$enteredIdentifier     = '';

// Sticky Registration Form Values
$firstName      = '';
$lastName       = '';
$containerLabel = '';
$houseNo        = '';
$streetName     = '';
$barangay       = '';
$city           = 'Caloocan City';
$primaryPhone   = '';
$backupPhone    = '';
$emailAddress   = '';
$gateRiderNote  = '';

$currentPageName = strtolower(basename($_SERVER['PHP_SELF'] ?? ''));

/* =========   FETCH LIVE 5-GAL REFILL & NEW CONTAINER PRICES FOR LOGIN/REGISTER SIDEBAR  ========== */
$loginRefillPrice = 'P30.00';
$loginNewPrice    = 'P250.00';
try {
    $rateRow = $pdo->query("
        SELECT refill_price, new_container_price
        FROM container_product
        WHERE product_id = 'PRD-01'
        LIMIT 1
    ")->fetch() ?: $pdo->query("
        SELECT refill_price, new_container_price
        FROM container_product
        ORDER BY product_id ASC
        LIMIT 1
    ")->fetch();

    if ($rateRow) {
        $loginRefillPrice = 'P' . number_format((float)$rateRow['refill_price'], 2);
        $loginNewPrice    = 'P' . number_format((float)$rateRow['new_container_price'], 2);
    }
} catch (Throwable $e) {
    // Fallback to P30.00 and P250.00 if table is not yet loaded
}

/* =============================
   1. LOGIN PAGE (Login.php)
================================= */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $loginStatusMessage = 'You have been logged out successfully.';
    $loginStatusType    = 'success';
    $statusMessage      = $loginStatusMessage;
    $statusType         = $loginStatusType;
} elseif (isset($_GET['inactive'])) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $loginStatusMessage = 'Account Inactive: Your account has been deactivated. Please contact the station administrator.';
    $loginStatusType    = 'error';
    $statusMessage      = $loginStatusMessage;
    $statusType         = $loginStatusType;
}

if (isset($_GET['registered']) && $_GET['registered'] !== '') {
    $loginStatusMessage = 'Registration complete! Account ' . htmlspecialchars((string)$_GET['registered']) . ' is ready — please sign in below.';
    $loginStatusType    = 'success';
    $statusMessage      = $loginStatusMessage;
    $statusType         = $loginStatusType;
}

/* =========   CSRF: created AFTER the logout/inactive session reset above   ========= */
majesticCsrfCheck();
$csrfToken    = majesticCsrfToken();
$majesticCsrf = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_identifier'], $_POST['password'])) {
    $enteredIdentifier = trim((string)$_POST['login_identifier']);
    $password          = trim((string)$_POST['password']);

    if ($enteredIdentifier === '' || $password === '') {
        $loginStatusMessage = 'Invalid Account or Password: Please enter both your Email Address / Phone No. and Password.';
        $loginStatusType    = 'error';
    } else {
        // Check employee & employee_cp_num (Admin / Staff Portal)
        $stmtEmp = $pdo->prepare("
            SELECT DISTINCT e.*
            FROM employee e
            LEFT JOIN employee_cp_num ep ON ep.employee_id = e.employee_id
            WHERE (LOWER(e.email_address) = LOWER(:id1)
                   OR LOWER(e.employee_id) = LOWER(:id2)
                   OR ep.cp_num = :id3)
            LIMIT 1
        ");
        $stmtEmp->execute([
            ':id1' => $enteredIdentifier,
            ':id2' => $enteredIdentifier,
            ':id3' => $enteredIdentifier
        ]);
        $emp = $stmtEmp->fetch();

        if ($emp && verifyAndUpgradePassword($pdo, 'employee', 'employee_id', (string)$emp['employee_id'], $password, (string)$emp['password_hash'])) {
            if ((string)$emp['emp_status'] !== 'Active') {
                $loginStatusMessage = 'Account Inactive: This staff account (' . htmlspecialchars((string)$emp['employee_id']) . ') is currently deactivated. Please contact the station administrator.';
                $loginStatusType    = 'error';
            } else {
                $phoneStmt = $pdo->prepare("SELECT cp_num FROM employee_cp_num WHERE employee_id = :eid");
                $phoneStmt->execute([':eid' => $emp['employee_id']]);
                $phones = $phoneStmt->fetchAll(PDO::FETCH_COLUMN);

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'account_type'  => 'Employee',
                    'employee_id'   => $emp['employee_id'],
                    'first_name'    => $emp['first_name'],
                    'last_name'     => $emp['last_name'],
                    'full_name'     => $emp['first_name'] . ' ' . $emp['last_name'],
                    'emp_role'      => $emp['emp_role'],
                    'house_no'      => $emp['house_no'],
                    'street_name'   => $emp['street_name'],
                    'barangay'      => $emp['barangay'],
                    'city'          => $emp['city'],
                    'email_address' => $emp['email_address'],
                    'primary_phone' => $phones[0] ?? '',
                    'backup_phone'  => $phones[1] ?? ''
                ];

                header('Location: Admin_Dashboard.php');
                exit;
            }
        } else {
            // Check customer & customer_cp_num (Customer Portal)
            $stmtCust = $pdo->prepare("
                SELECT DISTINCT c.*
                FROM customer c
                LEFT JOIN customer_cp_num cp ON cp.customer_id = c.customer_id
                WHERE (LOWER(c.email_address) = LOWER(:id1)
                       OR LOWER(c.customer_id) = LOWER(:id2)
                       OR LOWER(c.container_label) = LOWER(:id3)
                       OR cp.cp_num = :id4)
                LIMIT 1
            ");
            $stmtCust->execute([
                ':id1' => $enteredIdentifier,
                ':id2' => $enteredIdentifier,
                ':id3' => $enteredIdentifier,
                ':id4' => $enteredIdentifier
            ]);
            $cust = $stmtCust->fetch();

            if ($cust && verifyAndUpgradePassword($pdo, 'customer', 'customer_id', (string)$cust['customer_id'], $password, (string)$cust['password_hash'])) {
                if ((string)$cust['cust_status'] !== 'Active') {
                    $loginStatusMessage = 'Account Inactive: Your customer account (' . htmlspecialchars((string)$cust['customer_id']) . ') is currently deactivated. Please contact Majestic Spring Water Station.';
                    $loginStatusType    = 'error';
                } else {
                    $phoneStmt = $pdo->prepare("
                        SELECT cp_type, cp_num
                        FROM customer_cp_num
                        WHERE customer_id = :cid
                    ");
                    $phoneStmt->execute([':cid' => $cust['customer_id']]);
                    $phoneRows = $phoneStmt->fetchAll();

                    $custPrimaryPhone = '';
                    $custBackupPhone  = '';
                    foreach ($phoneRows as $pRow) {
                        if (($pRow['cp_type'] ?? '') === 'Primary') {
                            $custPrimaryPhone = (string)$pRow['cp_num'];
                        } elseif (($pRow['cp_type'] ?? '') === 'Backup') {
                            $custBackupPhone = (string)$pRow['cp_num'];
                        }
                    }

                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'account_type'    => 'Customer',
                        'customer_id'     => $cust['customer_id'],
                        'first_name'      => $cust['first_name'],
                        'last_name'       => $cust['last_name'],
                        'full_name'       => $cust['first_name'] . ' ' . $cust['last_name'],
                        'container_label' => $cust['container_label'],
                        'house_no'        => $cust['house_no'],
                        'street_name'     => $cust['street_name'],
                        'barangay'        => $cust['barangay'],
                        'city'            => $cust['city'],
                        'address'         => $cust['house_no'] . ' ' . $cust['street_name'] . ', ' . $cust['city'],
                        'email_address'   => $cust['email_address'],
                        'primary_phone'   => $custPrimaryPhone,
                        'backup_phone'    => $custBackupPhone,
                        'gate_rider_note' => $cust['gate_rider_note'] ?? ''
                    ];

                    header('Location: Customer_Dashboard.php');
                    exit;
                }
            } else {
                $loginStatusMessage = 'Invalid Email Address / Phone No. or Password.';
                $loginStatusType    = 'error';
            }
        }
    }
    $statusMessage = $loginStatusMessage;
    $statusType    = $loginStatusType;
}

/* =======================================
   2. REGISTER PAGE (Register.php)
  ======================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'], $_POST['last_name'], $_POST['container_label'])) {
    $firstName       = trim((string)($_POST['first_name'] ?? ''));
    $lastName        = trim((string)($_POST['last_name'] ?? ''));
    $containerLabel  = trim((string)($_POST['container_label'] ?? ''));
    $houseNo         = trim((string)($_POST['house_no'] ?? ''));
    $streetName      = trim((string)($_POST['street_name'] ?? ''));
    $barangay        = trim((string)($_POST['barangay'] ?? ''));
    $city            = trim((string)($_POST['city'] ?? 'Caloocan City'));
    $primaryPhone    = trim((string)($_POST['primary_phone'] ?? ''));
    $backupPhone     = trim((string)($_POST['backup_phone'] ?? ''));
    $emailAddress    = trim((string)($_POST['email_address'] ?? ''));
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $gateRiderNote   = trim((string)($_POST['gate_rider_note'] ?? ''));

    // Validate required fields and formats
    if (
        $firstName === '' || $lastName === '' || $containerLabel === '' ||
        $houseNo === '' || $streetName === '' || $barangay === '' ||
        $primaryPhone === '' || $emailAddress === '' || $password === '' || $confirmPassword === ''
    ) {
        $registerStatusMessage = 'Incomplete Registration: Please fill out all required fields marked with (*).';
        $registerStatusType    = 'error';
    } elseif (!preg_match('/^09\d{9}$/', $primaryPhone)) {
        $registerStatusMessage = 'Invalid Primary Phone No.: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
        $registerStatusType    = 'error';
    } elseif ($backupPhone !== '' && !preg_match('/^09\d{9}$/', $backupPhone)) {
        $registerStatusMessage = 'Invalid Backup Phone No.: Must be an 11-digit number starting with 09 (e.g., 09171234567).';
        $registerStatusType    = 'error';
    } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $registerStatusMessage = 'Invalid Email Address: Please enter a valid email address.';
        $registerStatusType    = 'error';
    } elseif (strlen($password) < 8) {
        $registerStatusMessage = 'Weak Password: Password must be at least 8 characters long.';
        $registerStatusType    = 'error';
    } elseif ($password !== $confirmPassword) {
        $registerStatusMessage = 'Password Mismatch: Password and Confirm Password do not match.';
        $registerStatusType    = 'error';
    } else {
        $dupStmt = $pdo->prepare("
            SELECT customer_id AS account_id FROM customer WHERE LOWER(email_address) = LOWER(:em1)
            UNION
            SELECT employee_id AS account_id FROM employee WHERE LOWER(email_address) = LOWER(:em2)
            LIMIT 1
        ");
        $dupStmt->execute([
            ':em1' => $emailAddress,
            ':em2' => $emailAddress
        ]);

        $tagDupStmt = $pdo->prepare("
            SELECT customer_id FROM customer WHERE LOWER(container_label) = LOWER(:lbl) LIMIT 1
        ");
        $tagDupStmt->execute([':lbl' => $containerLabel]);

        if ($dupStmt->fetch()) {
            $registerStatusMessage = 'Duplicate Email: An account with this email address is already registered.';
            $registerStatusType    = 'error';
        } elseif ($tagDupStmt->fetch()) {
            $registerStatusMessage = 'Tag Name Taken: The Container Label / Gallon Tag "' . htmlspecialchars($containerLabel) . '" is already taken. Please choose a different tag name.';
            $registerStatusType    = 'error';
        } else {
            try {
                $pdo->beginTransaction();

                $idStmt = $pdo->query("SELECT customer_id FROM customer");
                $maxNum = 0;
                while ($row = $idStmt->fetch()) {
                    if (preg_match('/(\d+)$/', (string)$row['customer_id'], $m)) {
                        $num = (int)$m[1];
                        if ($num > $maxNum) {
                            $maxNum = $num;
                        }
                    }
                }
                $newCustomerId = sprintf('CUS-%04d', $maxNum + 1);
                $passwordHash  = password_hash($password, PASSWORD_DEFAULT);

                $insertCustomer = $pdo->prepare("
                    INSERT INTO customer (
                        customer_id, first_name, last_name, container_label,
                        house_no, street_name, barangay, city,
                        email_address, password_hash, gate_rider_note,
                        date_registered, cust_status
                    ) VALUES (
                        :cid, :fname, :lname, :label,
                        :house, :street, :brgy, :city,
                        :email, :phash, :gate,
                        CURRENT_DATE, 'Active'
                    )
                ");
                $insertCustomer->execute([
                    ':cid'    => $newCustomerId,
                    ':fname'  => $firstName,
                    ':lname'  => $lastName,
                    ':label'  => $containerLabel,
                    ':house'  => $houseNo,
                    ':street' => $streetName,
                    ':brgy'   => $barangay,
                    ':city'   => $city !== '' ? $city : 'Caloocan City',
                    ':email'  => $emailAddress,
                    ':phash'  => $passwordHash,
                    ':gate'   => $gateRiderNote !== '' ? $gateRiderNote : null
                ]);

                $insertPhone = $pdo->prepare("
                    INSERT INTO customer_cp_num (customer_id, cp_num, cp_type)
                    VALUES (:cid, :cp, :ctype)
                ");
                $insertPhone->execute([
                    ':cid'   => $newCustomerId,
                    ':cp'    => $primaryPhone,
                    ':ctype' => 'Primary'
                ]);

                if ($backupPhone !== '' && $backupPhone !== $primaryPhone) {
                    $insertPhone->execute([
                        ':cid'   => $newCustomerId,
                        ':cp'    => $backupPhone,
                        ':ctype' => 'Backup'
                    ]);
                }

                $pdo->commit();

                header('Location: Login.php?registered=' . urlencode($newCustomerId));
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('WCDMS register error: ' . $e->getMessage());
                $registerStatusMessage = 'Database Error: Unable to register your account right now. Please try again.';
                $registerStatusType    = 'error';
            }
        }
    }
    $statusMessage = $registerStatusMessage;
    $statusType    = $registerStatusType;
}

// Automatically replace hardcoded P30.00 and P250.00 in Login.php and Register.php with live database prices
if (in_array($currentPageName, ['login.php', 'register.php'], true)) {
    ob_start(function (string $html) use ($loginRefillPrice, $loginNewPrice, $majesticCsrf): string {
        $html = majesticCsrfInject($html, $majesticCsrf);
        $html = preg_replace('/>\s*P30\.00\s*</i', '>' . $loginRefillPrice . '<', $html);
        $html = preg_replace('/>\s*P250\.00\s*</i', '>' . $loginNewPrice . '<', $html);
        return $html;
    });
}
?>
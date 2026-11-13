<?php
/* ============================
   Database Connection for
   1. Container_Products.php
   ===========================*/
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

/* ==============================================
   CONTAINER PRODUCTS  (Container_Products.php)
   =============================================== */
$productStatusMessage = '';
$productStatusType    = '';
$productsListJson     = '[]';
$totalProductsCount   = 0;

$setProductStatus = static function (string $msg, string $type = 'error') use (&$productStatusMessage, &$productStatusType): void {
    $productStatusMessage = $msg;
    $productStatusType    = $type;
};

if (isset($_GET['saved']) && $_GET['saved'] !== '') {
    $setProductStatus('Container Product ' . htmlspecialchars((string)$_GET['saved']) . ' saved successfully.', 'success');
} elseif (isset($_GET['deleted']) && $_GET['deleted'] !== '') {
    $setProductStatus('Container Product ' . htmlspecialchars((string)$_GET['deleted']) . ' deleted successfully.', 'success');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction        = trim((string)($_POST['form_action'] ?? 'save'));
    $productId         = trim((string)($_POST['product_id'] ?? 'ID-#'));
    $containerTypeRaw  = trim((string)($_POST['container_type'] ?? ''));
    $containerName     = trim((string)($_POST['container_name'] ?? ''));
    $capacityGallons   = (int)($_POST['capacity_gallons'] ?? 5);
    $refillPrice       = (float)($_POST['refill_price'] ?? 30.00);
    $newContainerPrice = (float)($_POST['new_container_price'] ?? 250.00);
    $availableStock    = (int)($_POST['available_stock'] ?? $_POST['available_stocks'] ?? 0);
    $inspectionNote    = trim((string)($_POST['inspection_note'] ?? ''));
    $availabilityRaw   = trim((string)($_POST['availability_status'] ?? 'Available'));

    $containerType = (stripos($containerTypeRaw, 'Round') !== false)
        ? '5-Gal - Round'
        : '5-Gal - Slim';

    $availabilityStatus = ($availabilityRaw === 'Out of Stock' || $availableStock <= 0)
        ? 'Out of Stock'
        : 'Available';

    // DELETE CONTAINER PRODUCT
    if ($formAction === 'delete' && $productId !== '' && $productId !== 'ID-#') {
        try {
            $delStmt = $pdo->prepare("DELETE FROM container_product WHERE product_id = :pid");
            $delStmt->execute([':pid' => $productId]);
            header('Location: Container_Products.php?deleted=' . urlencode($productId));
            exit;
        } catch (Throwable $e) {
            $setProductStatus('Cannot delete product ' . htmlspecialchars($productId) . ' because it is referenced in existing customer orders. You may set its status to Out of Stock instead.');
        }
    }

    // ADD NEW OR UPDATE EXISTING CONTAINER PRODUCT
    if ($formAction === 'save') {
        if ($containerTypeRaw === '' || $containerName === '' || $capacityGallons <= 0) {
            $setProductStatus('Incomplete Form: Please fill out Container Type, Container Name, Pricing, and Available Stocks.');
        } else {
            try {
                $isNewProd = ($productId === '' || $productId === 'ID-#');

                if ($isNewProd) {
                    $maxNum = (int)$pdo->query("
                        SELECT COALESCE(MAX(CAST(SUBSTRING(product_id, 5) AS UNSIGNED)), 0)
                        FROM container_product
                        WHERE product_id LIKE 'PRD-%'
                    ")->fetchColumn();
                    $targetProdId = sprintf('PRD-%02d', $maxNum + 1);

                    $stmtProd = $pdo->prepare("
                        INSERT INTO container_product (
                            product_id, container_name, container_type,
                            capacity_gallons, refill_price, new_container_price,
                            available_stock, inspection_note, availability_status
                        ) VALUES (
                            :pid, :cname, :ctype,
                            :cap, :rprice, :nprice,
                            :stock, :inote, :astat
                        )
                    ");
                } else {
                    $targetProdId = $productId;
                    $stmtProd = $pdo->prepare("
                        UPDATE container_product
                        SET container_name = :cname,
                            container_type = :ctype,
                            capacity_gallons = :cap,
                            refill_price = :rprice,
                            new_container_price = :nprice,
                            available_stock = :stock,
                            inspection_note = :inote,
                            availability_status = :astat
                        WHERE product_id = :pid
                    ");
                }

                $stmtProd->execute([
                    ':pid'    => $targetProdId,
                    ':cname'  => $containerName,
                    ':ctype'  => $containerType,
                    ':cap'    => $capacityGallons,
                    ':rprice' => $refillPrice,
                    ':nprice' => $newContainerPrice,
                    ':stock'  => $availableStock,
                    ':inote'  => $inspectionNote !== '' ? $inspectionNote : null,
                    ':astat'  => $availabilityStatus
                ]);

                header('Location: Container_Products.php?saved=' . urlencode($targetProdId));
                exit;
            } catch (Throwable $e) {
                $setProductStatus('Database Error: ' . $e->getMessage());
            }
        }
    }
}

// Query All Container Products for Catalog Table
$prodRows = $pdo->query("SELECT * FROM container_product ORDER BY product_id ASC")->fetchAll();

$productsArray = [];
foreach ($prodRows as $pr) {
    $productsArray[] = [
        'product_id'          => (string)$pr['product_id'],
        'container_name'      => (string)$pr['container_name'],
        'container_type'      => (string)$pr['container_type'],
        'capacity_gallons'    => (int)$pr['capacity_gallons'],
        'refill_price'        => number_format((float)$pr['refill_price'], 2, '.', ''),
        'new_container_price' => number_format((float)$pr['new_container_price'], 2, '.', ''),
        'available_stock'     => (int)$pr['available_stock'],
        'inspection_note'     => (string)($pr['inspection_note'] ?? ''),
        'availability_status' => (string)$pr['availability_status']
    ];
}

$totalProductsCount = count($productsArray);
$productsListJson   = json_encode($productsArray, JSON_UNESCAPED_UNICODE) ?: '[]';

echo "<script>\n";
echo "  window.MAJESTIC_PRODUCTS_DATA = " . $productsListJson . ";\n";
echo "  window.MAJESTIC_PRODUCTS_FLASH = " . json_encode([
    'message' => $productStatusMessage,
    'type'    => $productStatusType
], JSON_UNESCAPED_UNICODE) . ";\n";
echo "</script>\n";
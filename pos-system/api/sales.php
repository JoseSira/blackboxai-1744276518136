<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/SaleController.php';

// Set JSON response headers
header('Content-Type: application/json');

// Initialize controllers
$auth = new AuthController();
$saleController = new SaleController();

// Verify authentication
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

// Get JSON request body for POST/PUT requests
$jsonBody = file_get_contents('php://input');
$requestData = $jsonBody ? json_decode($jsonBody, true) : [];

// Handle different endpoints and methods
try {
    switch ($method) {
        case 'GET':
            if ($endpoint === 'sales') {
                // List sales
                $page = $_GET['page'] ?? 1;
                $filters = [
                    'branch_id' => $_GET['branch_id'] ?? null,
                    'user_id' => $_GET['user_id'] ?? null,
                    'customer_id' => $_GET['customer_id'] ?? null,
                    'date_from' => $_GET['date_from'] ?? null,
                    'date_to' => $_GET['date_to'] ?? null,
                    'payment_method' => $_GET['payment_method'] ?? null,
                    'payment_status' => $_GET['payment_status'] ?? null
                ];
                $result = $saleController->getSales($filters, $page);
            } elseif (is_numeric($endpoint)) {
                // Get single sale
                $result = $saleController->getSale($endpoint);
            } elseif ($endpoint === 'receipt' && isset($_GET['id'])) {
                // Generate receipt
                $result = $saleController->generateReceipt($_GET['id']);
            } elseif ($endpoint === 'report') {
                // Get sales report
                if (empty($_GET['start_date']) || empty($_GET['end_date'])) {
                    throw new Exception('Start date and end date are required for reports');
                }
                $result = $saleController->getSalesReport(
                    $_GET['start_date'],
                    $_GET['end_date'],
                    $_GET['branch_id'] ?? null
                );
            } elseif ($endpoint === 'top-products') {
                // Get top products
                if (empty($_GET['start_date']) || empty($_GET['end_date'])) {
                    throw new Exception('Start date and end date are required');
                }
                $result = $saleController->getTopProducts(
                    $_GET['start_date'],
                    $_GET['end_date'],
                    $_GET['limit'] ?? 10
                );
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'POST':
            if ($endpoint === 'sales') {
                // Validate required data
                if (empty($requestData['items'])) {
                    throw new Exception('No items provided');
                }

                // Create new sale
                $result = $saleController->createSale(
                    $requestData,
                    $requestData['items']
                );
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'PUT':
            if ($endpoint === 'cancel' && isset($requestData['sale_id'])) {
                // Cancel sale
                $result = $saleController->cancelSale($requestData['sale_id']);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

    // Send response
    if (isset($result['success']) && !$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Helper function to clean and validate filters
function cleanFilters($filters) {
    $cleaned = [];
    foreach ($filters as $key => $value) {
        if ($value !== null && $value !== '') {
            // Validate dates
            if (in_array($key, ['date_from', 'date_to']) && !validateDate($value)) {
                throw new Exception("Invalid date format for {$key}");
            }
            // Validate numeric values
            if (in_array($key, ['branch_id', 'user_id', 'customer_id']) && !is_numeric($value)) {
                throw new Exception("Invalid value for {$key}");
            }
            $cleaned[$key] = $value;
        }
    }
    return $cleaned;
}

// Helper function to validate sale data
function validateSaleData($data) {
    $required = ['branch_id', 'payment_method', 'items'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Field {$field} is required");
        }
    }

    // Validate items
    foreach ($data['items'] as $item) {
        if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
            throw new Exception('Invalid item data');
        }
        if ($item['quantity'] <= 0) {
            throw new Exception('Invalid quantity');
        }
        if ($item['unit_price'] < 0) {
            throw new Exception('Invalid unit price');
        }
    }

    return true;
}
?>

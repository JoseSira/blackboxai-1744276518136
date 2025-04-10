<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Set JSON response headers
header('Content-Type: application/json');

// Initialize controllers
$auth = new AuthController();
$productController = new ProductController();

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
            if ($endpoint === 'products') {
                // List products
                $page = $_GET['page'] ?? 1;
                $filters = [
                    'search' => $_GET['search'] ?? '',
                    'category_id' => $_GET['category_id'] ?? '',
                    'status' => $_GET['status'] ?? 'active'
                ];
                $result = $productController->getProducts($filters, $page);
            } elseif (is_numeric($endpoint)) {
                // Get single product
                $result = $productController->getProduct($endpoint);
            } elseif ($endpoint === 'low-stock') {
                // Get low stock products
                $result = $productController->getLowStockProducts();
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'POST':
            if ($endpoint === 'products') {
                // Create new product
                $result = $productController->addProduct($requestData);
            } elseif ($endpoint === 'stock') {
                // Update stock
                $result = $productController->updateStock(
                    $requestData['product_id'],
                    $requestData['quantity'],
                    $requestData['movement_type'],
                    $requestData['notes'] ?? ''
                );
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'PUT':
            if (is_numeric($endpoint)) {
                // Update product
                $result = $productController->updateProduct($endpoint, $requestData);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'DELETE':
            if (is_numeric($endpoint)) {
                // Delete/deactivate product
                $result = $productController->deactivateProduct($endpoint);
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
?>

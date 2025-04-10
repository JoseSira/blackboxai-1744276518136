<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CategoryController.php';

// Set JSON response headers
header('Content-Type: application/json');

// Initialize controllers
$auth = new AuthController();
$categoryController = new CategoryController();

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
            if ($endpoint === 'categories') {
                // List categories
                $includeProducts = isset($_GET['include_products']) && $_GET['include_products'] === 'true';
                $result = $categoryController->getCategories($includeProducts);
            } elseif ($endpoint === 'tree') {
                // Get category tree
                $result = $categoryController->getCategoryTree();
            } elseif (strpos($endpoint, 'path') === 0 && isset($_GET['id'])) {
                // Get category path
                $result = $categoryController->getCategoryPath($_GET['id']);
            } elseif (is_numeric($endpoint)) {
                // Get single category
                $category = $categoryController->validateCategoryAccess($endpoint);
                if (!$category) {
                    throw new Exception('Category not found');
                }
                $result = [
                    'success' => true,
                    'data' => $category
                ];
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'POST':
            if ($endpoint === 'categories') {
                // Create new category
                $result = $categoryController->createCategory($requestData);
            } elseif ($endpoint === 'move' && isset($requestData['category_id'])) {
                // Move category
                $result = $categoryController->moveCategory(
                    $requestData['category_id'],
                    $requestData['new_parent_id'] ?? null
                );
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'PUT':
            if (is_numeric($endpoint)) {
                // Update category
                $result = $categoryController->updateCategory($endpoint, $requestData);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'DELETE':
            if (is_numeric($endpoint)) {
                // Delete category
                $result = $categoryController->deleteCategory($endpoint);
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

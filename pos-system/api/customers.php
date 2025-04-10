<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// Set JSON response headers
header('Content-Type: application/json');

// Initialize controllers
$auth = new AuthController();
$customerController = new CustomerController();

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
            if ($endpoint === 'customers') {
                // List customers
                $page = $_GET['page'] ?? 1;
                $filters = [
                    'search' => $_GET['search'] ?? ''
                ];
                $result = $customerController->getCustomers($filters, $page);
            } elseif ($endpoint === 'search') {
                // Search customers
                if (empty($_GET['q'])) {
                    throw new Exception('Search query is required');
                }
                $result = $customerController->searchCustomers($_GET['q']);
            } elseif ($endpoint === 'top') {
                // Get top customers
                $limit = $_GET['limit'] ?? 10;
                $result = $customerController->getTopCustomers($limit);
            } elseif (is_numeric($endpoint)) {
                // Get single customer details
                $result = $customerController->getCustomerDetails($endpoint);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'POST':
            if ($endpoint === 'customers') {
                // Create new customer
                $result = $customerController->createCustomer($requestData);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'PUT':
            if (is_numeric($endpoint)) {
                // Update customer
                $result = $customerController->updateCustomer($endpoint, $requestData);
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

// Helper function to validate customer data
function validateCustomerData($data) {
    $errors = [];

    // Required fields
    if (empty($data['first_name'])) {
        $errors['first_name'] = 'First name is required';
    }

    // Email validation
    if (!empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
    }

    // Phone validation
    if (!empty($data['phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) < 10) {
            $errors['phone'] = 'Invalid phone number';
        }
    }

    // Tax ID validation
    if (!empty($data['tax_id'])) {
        if (!preg_match('/^[A-Z0-9]{10,15}$/', $data['tax_id'])) {
            $errors['tax_id'] = 'Invalid tax ID format';
        }
    }

    return $errors;
}

// Helper function to format customer data for response
function formatCustomerResponse($customer) {
    return [
        'id' => $customer['id'],
        'name' => $customer['first_name'] . ' ' . $customer['last_name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'tax_id' => $customer['tax_id'],
        'address' => $customer['address'],
        'total_purchases' => $customer['total_purchases'] ?? 0,
        'total_spent' => $customer['total_spent'] ?? 0,
        'created_at' => $customer['created_at']
    ];
}
?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/BranchController.php';

// Set JSON response headers
header('Content-Type: application/json');

// Initialize controllers
$auth = new AuthController();
$branchController = new BranchController();

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
            if ($endpoint === 'branches') {
                // List branches
                $page = $_GET['page'] ?? 1;
                $filters = [
                    'search' => $_GET['search'] ?? '',
                    'status' => $_GET['status'] ?? null
                ];
                $result = $branchController->getBranches($filters, $page);
            } elseif ($endpoint === 'user-branches') {
                // Get branches accessible to current user
                $result = $branchController->getUserBranches();
            } elseif ($endpoint === 'summary' && isset($_GET['id'])) {
                // Get branch summary
                $result = $branchController->getBranchSummary($_GET['id']);
            } elseif (is_numeric($endpoint)) {
                // Get single branch details
                $result = $branchController->getBranchDetails($endpoint);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'POST':
            if ($endpoint === 'branches') {
                // Create new branch
                if (empty($requestData['name'])) {
                    throw new Exception('Branch name is required');
                }
                $result = $branchController->createBranch($requestData);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'PUT':
            if (is_numeric($endpoint)) {
                // Update branch
                $result = $branchController->updateBranch($endpoint, $requestData);
            } elseif ($endpoint === 'deactivate' && isset($requestData['branch_id'])) {
                // Deactivate branch
                $result = $branchController->deactivateBranch($requestData['branch_id']);
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

// Helper function to validate branch data
function validateBranchData($data) {
    $errors = [];

    // Required fields
    if (empty($data['name'])) {
        $errors['name'] = 'Branch name is required';
    }

    // Phone validation
    if (!empty($data['phone'])) {
        if (!preg_match('/^[0-9\-\(\)\/\+\s]*$/', $data['phone'])) {
            $errors['phone'] = 'Invalid phone number format';
        }
    }

    // Email validation
    if (!empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
    }

    // Status validation
    if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
        $errors['status'] = 'Invalid status value';
    }

    return $errors;
}

// Helper function to format branch data for response
function formatBranchResponse($branch) {
    return [
        'id' => $branch['id'],
        'name' => $branch['name'],
        'address' => $branch['address'],
        'phone' => $branch['phone'],
        'email' => $branch['email'],
        'status' => $branch['status'],
        'stats' => [
            'total_users' => $branch['total_users'] ?? 0,
            'open_registers' => $branch['open_registers'] ?? 0,
            'total_sales' => $branch['total_sales'] ?? 0,
            'total_revenue' => floatval($branch['total_revenue'] ?? 0)
        ],
        'created_at' => $branch['created_at']
    ];
}

// Helper function to format money values
function formatMoney($amount) {
    return number_format($amount, 2, '.', '');
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
            // Validate status
            if ($key === 'status' && !in_array($value, ['active', 'inactive'])) {
                throw new Exception("Invalid status value");
            }
            $cleaned[$key] = $value;
        }
    }
    return $cleaned;
}
?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/RegisterSessionController.php';

// Set JSON response headers
header('Content-Type: application/json');

// Initialize controllers
$auth = new AuthController();
$registerSessionController = new RegisterSessionController();

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
            if ($endpoint === 'sessions') {
                // List sessions
                $page = $_GET['page'] ?? 1;
                $filters = [
                    'branch_id' => $_GET['branch_id'] ?? null,
                    'user_id' => $_GET['user_id'] ?? null,
                    'status' => $_GET['status'] ?? null,
                    'date_from' => $_GET['date_from'] ?? null,
                    'date_to' => $_GET['date_to'] ?? null
                ];
                $result = $registerSessionController->getSessions($filters, $page);
            } elseif ($endpoint === 'current' && isset($_GET['branch_id'])) {
                // Get current session for branch
                $result = $registerSessionController->getCurrentSession($_GET['branch_id']);
            } elseif ($endpoint === 'report' && isset($_GET['id'])) {
                // Generate session report
                $result = $registerSessionController->generateSessionReport($_GET['id']);
            } elseif (is_numeric($endpoint)) {
                // Get single session details
                $result = $registerSessionController->getSessionDetails($endpoint);
            } else {
                throw new Exception('Invalid endpoint');
            }
            break;

        case 'POST':
            if ($endpoint === 'open') {
                // Open new session
                if (empty($requestData['branch_id']) || !isset($requestData['initial_cash'])) {
                    throw new Exception('Branch ID and initial cash amount are required');
                }
                $result = $registerSessionController->openSession($requestData);
            } elseif ($endpoint === 'close' && isset($requestData['session_id'])) {
                // Close session
                if (!isset($requestData['final_cash'])) {
                    throw new Exception('Final cash amount is required');
                }
                $result = $registerSessionController->closeSession(
                    $requestData['session_id'],
                    $requestData
                );
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
            if (in_array($key, ['date_from', 'date_to'])) {
                if (!validateDate($value)) {
                    throw new Exception("Invalid date format for {$key}");
                }
            }
            // Validate numeric values
            if (in_array($key, ['branch_id', 'user_id']) && !is_numeric($value)) {
                throw new Exception("Invalid value for {$key}");
            }
            // Validate status
            if ($key === 'status' && !in_array($value, ['open', 'closed'])) {
                throw new Exception("Invalid status value");
            }
            $cleaned[$key] = $value;
        }
    }
    return $cleaned;
}

// Helper function to format session data for response
function formatSessionResponse($session) {
    return [
        'id' => $session['id'],
        'branch' => [
            'id' => $session['branch_id'],
            'name' => $session['branch_name']
        ],
        'user' => [
            'id' => $session['user_id'],
            'name' => $session['user_name']
        ],
        'status' => $session['status'],
        'opening_time' => $session['opening_time'],
        'closing_time' => $session['closing_time'],
        'initial_cash' => floatval($session['initial_cash']),
        'final_cash' => floatval($session['final_cash']),
        'cash_difference' => floatval($session['cash_difference']),
        'total_sales' => [
            'count' => intval($session['total_sales']),
            'amount' => floatval($session['total_amount'])
        ],
        'notes' => $session['notes']
    ];
}

// Helper function to format money values
function formatMoney($amount) {
    return number_format($amount, 2, '.', '');
}
?>

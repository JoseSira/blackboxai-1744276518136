<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('BASE_PATH', realpath(__DIR__ . '/..'));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pos_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Application configuration
define('APP_NAME', getenv('APP_NAME') ?: 'POS System');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') ?: true);
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'America/Mexico_City');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Security configuration
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-secret-key');
define('JWT_EXPIRATION', 3600); // 1 hour
define('PASSWORD_HASH_COST', 12);

// File upload configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf'
]);

// Subscription plans configuration
define('SUBSCRIPTION_PLANS', [
    'basic' => [
        'name' => 'Basic',
        'price' => 29.99,
        'features' => [
            'max_products' => 100,
            'max_users' => 3,
            'max_branches' => 1
        ]
    ],
    'premium' => [
        'name' => 'Premium',
        'price' => 49.99,
        'features' => [
            'max_products' => 500,
            'max_users' => 10,
            'max_branches' => 3
        ]
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 99.99,
        'features' => [
            'max_products' => -1, // unlimited
            'max_users' => -1, // unlimited
            'max_branches' => -1 // unlimited
        ]
    ]
]);

// Receipt configuration
define('RECEIPT_CONFIG', [
    'paper_width' => 80, // mm
    'margin_left' => 2,
    'margin_right' => 2,
    'line_height' => 4,
    'font_size' => 9,
    'logo_max_height' => 50
]);

// Tax configuration
define('DEFAULT_TAX_RATE', 0.16); // 16%
define('TAX_INCLUDED', true);

// Inventory configuration
define('LOW_STOCK_THRESHOLD', 10);
define('ENABLE_NEGATIVE_STOCK', false);

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour

// API configuration
define('API_RATE_LIMIT', 60); // requests per minute
define('API_RESPONSE_CACHE_TTL', 300); // 5 minutes

// Logging configuration
define('LOG_PATH', BASE_PATH . '/logs');
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// Email configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: APP_NAME);

// User roles and permissions
define('USER_ROLES', [
    'admin' => [
        'name' => 'Administrator',
        'permissions' => [
            'manage_users',
            'manage_roles',
            'manage_branches',
            'manage_products',
            'manage_categories',
            'manage_customers',
            'manage_sales',
            'manage_register',
            'view_reports',
            'manage_settings'
        ]
    ],
    'manager' => [
        'name' => 'Manager',
        'permissions' => [
            'manage_products',
            'manage_categories',
            'manage_customers',
            'manage_sales',
            'manage_register',
            'view_reports'
        ]
    ],
    'cashier' => [
        'name' => 'Cashier',
        'permissions' => [
            'make_sales',
            'manage_register',
            'view_customers'
        ]
    ]
]);

// Helper functions

// Function to get base URL
function baseUrl($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// Function to get asset URL
function assetUrl($path) {
    return baseUrl('assets/' . ltrim($path, '/'));
}

// Function to format money
function formatMoney($amount) {
    return number_format($amount, 2, '.', ',');
}

// Function to format date
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// Function to sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Function to validate permission
function hasPermission($permission) {
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role'])) {
        return false;
    }

    $userRole = $_SESSION['user']['role'];
    return in_array($permission, USER_ROLES[$userRole]['permissions']);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

// Function to get current user
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Function to get user role name
function getRoleName($role) {
    return USER_ROLES[$role]['name'] ?? $role;
}

// Function to check subscription limits
function checkSubscriptionLimit($business, $feature) {
    if (!isset($business['subscription_plan'])) {
        return false;
    }

    $plan = SUBSCRIPTION_PLANS[$business['subscription_plan']];
    $limit = $plan['features'][$feature] ?? 0;

    // -1 means unlimited
    if ($limit === -1) {
        return true;
    }

    // Check current usage against limit
    $usage = 0;
    switch ($feature) {
        case 'max_products':
            $usage = getProductCount($business['id']);
            break;
        case 'max_users':
            $usage = getUserCount($business['id']);
            break;
        case 'max_branches':
            $usage = getBranchCount($business['id']);
            break;
    }

    return $usage < $limit;
}

// Function to log error
function logError($message, $context = []) {
    $logFile = LOG_PATH . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to generate random string
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

// Function to validate date
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Load required files
require_once BASE_PATH . '/config/database.php';
?>

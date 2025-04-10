<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Business.php';

class AuthController {
    private $userModel;
    private $businessModel;

    public function __construct() {
        $this->userModel = new User();
        $this->businessModel = new Business();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($username, $password) {
        try {
            // Validate login credentials
            $user = $this->userModel->validateLogin($username, $password);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }

            // Get business data
            $business = $this->businessModel->findById($user['business_id']);
            if (!$business || $business['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Business account is inactive'
                ];
            }

            // Check subscription status
            if ($business['subscription_status'] === 'inactive') {
                return [
                    'success' => false,
                    'message' => 'Business subscription has expired'
                ];
            }

            // Set session data
            $_SESSION['user'] = $user;
            $_SESSION['business'] = $business;

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function register($data) {
        try {
            $this->userModel->beginTransaction();

            // Create business
            $businessData = [
                'name' => $data['business_name'],
                'tax_id' => $data['tax_id'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'subscription_plan' => 'basic',
                'subscription_status' => 'trial',
                'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'status' => 'active'
            ];

            $businessId = $this->businessModel->createBusiness($businessData);

            // Create admin user
            $userData = [
                'business_id' => $businessId,
                'username' => $data['username'],
                'password' => $data['password'],
                'email' => $data['email'],
                'role' => 'admin',
                'status' => 'active'
            ];

            $userId = $this->userModel->createUser($userData);

            // Set admin permissions
            $adminPermissions = USER_ROLES['admin']['permissions'];
            $this->userModel->setUserPermissions($userId, $adminPermissions);

            $this->userModel->commit();

            return [
                'success' => true,
                'message' => 'Registration successful',
                'business_id' => $businessId,
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            $this->userModel->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function logout() {
        // Destroy session
        session_destroy();
        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }

    public function isAuthenticated() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function getCurrentBusiness() {
        return $_SESSION['business'] ?? null;
    }

    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requirePermission($permission) {
        $this->requireAuth();

        $user = $this->getCurrentUser();
        if (!in_array($permission, $user['permissions'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Permission denied'
                ]);
                exit;
            } else {
                header('Location: /dashboard.php?error=' . urlencode('Permission denied'));
                exit;
            }
        }
    }

    public function hasPermission($permission) {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $user = $this->getCurrentUser();
        return in_array($permission, $user['permissions']);
    }

    public function validateRegistrationData($data) {
        $errors = [];

        // Business name validation
        if (empty($data['business_name'])) {
            $errors['business_name'] = 'Business name is required';
        }

        // Tax ID validation (optional)
        if (!empty($data['tax_id'])) {
            if (!preg_match('/^[A-Z0-9]{10,15}$/', $data['tax_id'])) {
                $errors['tax_id'] = 'Invalid tax ID format';
            }
        }

        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
            $errors['username'] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        // Phone validation (optional)
        if (!empty($data['phone'])) {
            if (!preg_match('/^[0-9\-\(\)\/\+\s]*$/', $data['phone'])) {
                $errors['phone'] = 'Invalid phone number format';
            }
        }

        return $errors;
    }

    public function validateLoginData($data) {
        $errors = [];

        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        return $errors;
    }

    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get user data
            $user = $this->userModel->findById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Update password
            $this->userModel->updateUser($userId, ['password' => $newPassword]);

            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateSubscription() {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $business = $this->getCurrentBusiness();
        
        // Check if subscription has expired
        if ($business['subscription_status'] === 'inactive' || 
            ($business['subscription_expires_at'] && strtotime($business['subscription_expires_at']) < time())) {
            
            // Update business subscription status
            $this->businessModel->update($business['id'], [
                'subscription_status' => 'inactive'
            ]);

            // Update session data
            $_SESSION['business']['subscription_status'] = 'inactive';

            return false;
        }

        return true;
    }

    public function checkSubscriptionLimit($feature) {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $business = $this->getCurrentBusiness();
        
        // Get plan limits
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
                $usage = $this->businessModel->getProductCount($business['id']);
                break;
            case 'max_users':
                $usage = $this->businessModel->getUserCount($business['id']);
                break;
            case 'max_branches':
                $usage = $this->businessModel->getBranchCount($business['id']);
                break;
        }

        return $usage < $limit;
    }
}
?>

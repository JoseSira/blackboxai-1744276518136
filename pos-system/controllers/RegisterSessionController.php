<?php
require_once __DIR__ . '/../models/RegisterSession.php';
require_once __DIR__ . '/AuthController.php';

class RegisterSessionController {
    private $registerSessionModel;
    private $auth;

    public function __construct() {
        $this->registerSessionModel = new RegisterSession();
        $this->auth = new AuthController();
    }

    public function openSession($data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_register');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();
            $data['business_id'] = $currentUser['business_id'];
            $data['user_id'] = $currentUser['id'];

            // Validate data
            $errors = $this->validateSessionData($data);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $errors
                ];
            }

            // Open session
            $sessionId = $this->registerSessionModel->openSession($data);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Register session opened successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function closeSession($sessionId, $data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_register');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify session belongs to user's business
            $session = $this->registerSessionModel->findById($sessionId);
            if (!$session || $session['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Session not found");
            }

            // Validate data
            if (!isset($data['final_cash']) || !is_numeric($data['final_cash'])) {
                throw new Exception("Final cash amount is required");
            }

            // Close session
            $result = $this->registerSessionModel->closeSession($sessionId, $data);

            return [
                'success' => true,
                'data' => $result,
                'message' => 'Register session closed successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getSessionDetails($sessionId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_register');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify session belongs to user's business
            $session = $this->registerSessionModel->findById($sessionId);
            if (!$session || $session['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Session not found");
            }

            // Get session details
            $details = $this->registerSessionModel->getSessionDetails($sessionId);

            return [
                'success' => true,
                'data' => $details
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCurrentSession($branchId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_register');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify branch belongs to user's business
            // This verification should be done in a BranchController, but for now we'll do it here
            $sql = "SELECT business_id FROM branches WHERE id = :branch_id";
            $stmt = $this->registerSessionModel->query($sql, ['branch_id' => $branchId]);
            $branch = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$branch || $branch['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Branch not found");
            }

            // Get current session
            $session = $this->registerSessionModel->getCurrentSession($branchId);

            return [
                'success' => true,
                'data' => $session
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getSessions($filters = [], $page = 1) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_register');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get sessions
            $result = $this->registerSessionModel->getSessions(
                $currentUser['business_id'],
                $filters,
                $page
            );

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validateSessionData($data) {
        $errors = [];

        // Branch ID is required
        if (empty($data['branch_id'])) {
            $errors['branch_id'] = 'Branch is required';
        }

        // Initial cash is required and must be numeric
        if (!isset($data['initial_cash']) || !is_numeric($data['initial_cash'])) {
            $errors['initial_cash'] = 'Initial cash amount is required';
        } elseif ($data['initial_cash'] < 0) {
            $errors['initial_cash'] = 'Initial cash amount cannot be negative';
        }

        return $errors;
    }

    public function generateSessionReport($sessionId) {
        try {
            // Get session details
            $details = $this->registerSessionModel->getSessionDetails($sessionId);
            if (!$details) {
                throw new Exception("Session not found");
            }

            $session = $details['session'];
            $sales = $details['sales'];
            $salesByHour = $details['sales_by_hour'];

            // Format report data
            $report = [
                'session' => [
                    'id' => $session['id'],
                    'branch' => $session['branch_name'],
                    'cashier' => $session['user_name'],
                    'opening_time' => $session['opening_time'],
                    'closing_time' => $session['closing_time'],
                    'status' => $session['status'],
                    'initial_cash' => $session['initial_cash'],
                    'final_cash' => $session['final_cash'],
                    'cash_difference' => $session['cash_difference'],
                    'notes' => $session['notes']
                ],
                'sales_summary' => [
                    'total_sales' => $sales['total_sales'],
                    'total_amount' => $sales['total_amount'],
                    'cash_sales' => $sales['cash_sales'],
                    'card_sales' => $sales['card_sales']
                ],
                'sales_by_hour' => $salesByHour
            ];

            return [
                'success' => true,
                'data' => $report
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateSessionAccess($sessionId) {
        // Get current user's business
        $currentUser = $this->auth->getCurrentUser();

        // Verify session belongs to user's business
        $session = $this->registerSessionModel->findById($sessionId);
        return $session && $session['business_id'] === $currentUser['business_id'];
    }
}
?>

<?php
require_once __DIR__ . '/../models/Branch.php';
require_once __DIR__ . '/AuthController.php';

class BranchController {
    private $branchModel;
    private $auth;

    public function __construct() {
        $this->branchModel = new Branch();
        $this->auth = new AuthController();
    }

    public function createBranch($data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_branches');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();
            $data['business_id'] = $currentUser['business_id'];

            // Validate data
            $errors = $this->branchModel->validateBranchData($data);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $errors
                ];
            }

            // Create branch
            $branchId = $this->branchModel->createBranch($data);

            return [
                'success' => true,
                'branch_id' => $branchId,
                'message' => 'Branch created successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateBranch($branchId, $data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_branches');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify branch belongs to user's business
            $branch = $this->branchModel->findById($branchId);
            if (!$branch || $branch['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Branch not found");
            }

            // Validate data
            $errors = $this->branchModel->validateBranchData($data, true);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $errors
                ];
            }

            // Update branch
            $this->branchModel->updateBranch($branchId, $data);

            return [
                'success' => true,
                'message' => 'Branch updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getBranches($filters = [], $page = 1) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_branches');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get branches
            $result = $this->branchModel->getBranches(
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

    public function getBranchDetails($branchId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_branches');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify branch belongs to user's business
            $branch = $this->branchModel->findById($branchId);
            if (!$branch || $branch['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Branch not found");
            }

            // Get branch details
            $details = $this->branchModel->getBranchDetails($branchId);

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

    public function deactivateBranch($branchId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_branches');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify branch belongs to user's business
            $branch = $this->branchModel->findById($branchId);
            if (!$branch || $branch['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Branch not found");
            }

            // Deactivate branch
            $this->branchModel->deactivateBranch($branchId);

            return [
                'success' => true,
                'message' => 'Branch deactivated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getUserBranches() {
        try {
            // Get current user
            $currentUser = $this->auth->getCurrentUser();

            // If user is admin or has manage_branches permission, return all branches
            if ($currentUser['role'] === 'admin' || $this->auth->hasPermission('manage_branches')) {
                return $this->getBranches();
            }

            // Otherwise, return only the user's assigned branch
            if (!empty($currentUser['branch_id'])) {
                $branch = $this->branchModel->findById($currentUser['branch_id']);
                if ($branch) {
                    return [
                        'success' => true,
                        'data' => [
                            'total' => 1,
                            'pages' => 1,
                            'current_page' => 1,
                            'branches' => [$branch]
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'branches' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateBranchAccess($branchId) {
        // Get current user's business
        $currentUser = $this->auth->getCurrentUser();

        // Verify branch belongs to user's business
        $branch = $this->branchModel->findById($branchId);
        return $branch && $branch['business_id'] === $currentUser['business_id'];
    }

    public function getBranchSummary($branchId) {
        try {
            // Get branch details
            $details = $this->branchModel->getBranchDetails($branchId);
            if (!$details) {
                throw new Exception("Branch not found");
            }

            // Format summary data
            return [
                'success' => true,
                'data' => [
                    'branch' => [
                        'id' => $details['branch']['id'],
                        'name' => $details['branch']['name'],
                        'status' => $details['branch']['status']
                    ],
                    'stats' => [
                        'total_users' => count($details['users']),
                        'open_registers' => count($details['open_sessions']),
                        'total_sales' => $details['stats']['total_sales'],
                        'total_revenue' => $details['stats']['total_revenue']
                    ]
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>

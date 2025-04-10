<?php
require_once __DIR__ . '/../models/Business.php';
require_once __DIR__ . '/../models/User.php';

class BusinessController {
    private $businessModel;
    private $userModel;

    public function __construct() {
        $this->businessModel = new Business();
        $this->userModel = new User();
    }

    public function registerBusiness($businessData, $userData) {
        try {
            $this->conn->beginTransaction();

            // First, register the user
            $userId = $this->userModel->register($userData);

            if (!$userId) {
                throw new Exception("Failed to create user account");
            }

            // Add owner_id to business data
            $businessData['owner_id'] = $userId;

            // Create the business
            $businessId = $this->businessModel->createBusiness($businessData);

            if (!$businessId) {
                throw new Exception("Failed to create business");
            }

            // Update user with business_id
            $this->userModel->update($userId, ['business_id' => $businessId]);

            $this->conn->commit();

            return [
                'success' => true,
                'business_id' => $businessId,
                'message' => 'Business registered successfully'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getBusinessDetails($businessId) {
        try {
            // Get basic business information
            $business = $this->businessModel->findById($businessId);
            
            if (!$business) {
                throw new Exception("Business not found");
            }

            // Get subscription details
            $subscription = $this->businessModel->getSubscriptionDetails($businessId);

            // Get business statistics
            $stats = $this->businessModel->getBusinessStats($businessId);

            // Get branches
            $branches = $this->businessModel->getBranches($businessId);

            return [
                'success' => true,
                'data' => [
                    'business' => $business,
                    'subscription' => $subscription,
                    'stats' => $stats,
                    'branches' => $branches
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateSubscription($businessId, $subscriptionId) {
        try {
            $this->businessModel->updateSubscription($businessId, $subscriptionId);
            
            return [
                'success' => true,
                'message' => 'Subscription updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateLicense($licenseKey) {
        try {
            $business = $this->businessModel->validateLicense($licenseKey);
            
            return [
                'success' => true,
                'data' => $business
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function addBranch($businessId, $branchData) {
        try {
            // Check branch limit
            $this->businessModel->checkBranchLimit($businessId);

            // Add business_id to branch data
            $branchData['business_id'] = $businessId;
            $branchData['status'] = 'active';

            // Create branch
            $sql = "INSERT INTO branches (business_id, name, address, phone, email, status) 
                    VALUES (:business_id, :name, :address, :phone, :email, :status)";
            
            $this->businessModel->query($sql, $branchData);

            return [
                'success' => true,
                'message' => 'Branch added successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateBranch($branchId, $branchData) {
        try {
            // Remove fields that shouldn't be updated
            unset($branchData['business_id']);
            unset($branchData['status']);

            $sql = "UPDATE branches SET 
                    name = :name,
                    address = :address,
                    phone = :phone,
                    email = :email
                    WHERE id = :id";
            
            $branchData['id'] = $branchId;
            $this->businessModel->query($sql, $branchData);

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

    public function deactivateBranch($branchId) {
        try {
            $sql = "UPDATE branches SET status = 'inactive' WHERE id = :id";
            $this->businessModel->query($sql, ['id' => $branchId]);

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

    public function addUser($businessId, $userData) {
        try {
            // Check user limit
            $this->businessModel->checkUserLimit($businessId);

            // Add business_id to user data
            $userData['business_id'] = $businessId;
            $userData['status'] = 'active';

            // Register user
            $userId = $this->userModel->register($userData);

            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User added successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getBusinessStats($businessId) {
        try {
            $stats = $this->businessModel->getBusinessStats($businessId);
            
            return [
                'success' => true,
                'data' => $stats
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

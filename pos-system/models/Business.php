<?php
require_once __DIR__ . '/BaseModel.php';

class Business extends BaseModel {
    protected $table = 'businesses';

    public function __construct() {
        parent::__construct();
    }

    public function createBusiness($data) {
        try {
            // Generate unique license key
            $data['license_key'] = $this->generateLicenseKey();
            
            // Validate required fields
            $requiredFields = ['name', 'owner_id', 'subscription_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Set subscription dates
            $data['subscription_start_date'] = date('Y-m-d');
            
            // Get subscription duration from subscriptions table
            $sql = "SELECT duration_months FROM subscriptions WHERE id = :subscription_id";
            $stmt = $this->query($sql, ['subscription_id' => $data['subscription_id']]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscription) {
                $data['subscription_end_date'] = date('Y-m-d', strtotime("+{$subscription['duration_months']} months"));
            } else {
                throw new Exception("Invalid subscription");
            }

            // Create the business
            $businessId = $this->create($data);

            // Create default branch for the business
            $this->createDefaultBranch($businessId, $data['name']);

            return $businessId;
        } catch (Exception $e) {
            throw new Exception("Failed to create business: " . $e->getMessage());
        }
    }

    private function createDefaultBranch($businessId, $businessName) {
        $sql = "INSERT INTO branches (business_id, name, status) VALUES (:business_id, :name, 'active')";
        $params = [
            'business_id' => $businessId,
            'name' => "Sucursal Principal - " . $businessName
        ];
        return $this->query($sql, $params);
    }

    public function validateLicense($licenseKey) {
        try {
            $sql = "SELECT b.*, s.name as subscription_name, s.features 
                    FROM businesses b 
                    JOIN subscriptions s ON b.subscription_id = s.id 
                    WHERE b.license_key = :license_key";
            
            $stmt = $this->query($sql, ['license_key' => $licenseKey]);
            $business = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$business) {
                throw new Exception("Invalid license key");
            }

            if ($business['status'] !== 'active') {
                throw new Exception("Business account is " . $business['status']);
            }

            // Check if subscription has expired
            if (strtotime($business['subscription_end_date']) < time()) {
                // Update business status to suspended
                $this->update($business['id'], ['status' => 'suspended']);
                throw new Exception("Subscription has expired");
            }

            return $business;
        } catch (Exception $e) {
            throw new Exception("License validation failed: " . $e->getMessage());
        }
    }

    public function getBranches($businessId) {
        return $this->findAll([
            'business_id' => $businessId,
            'status' => 'active'
        ], 'name ASC');
    }

    public function getSubscriptionDetails($businessId) {
        $sql = "SELECT b.*, s.* 
                FROM businesses b 
                JOIN subscriptions s ON b.subscription_id = s.id 
                WHERE b.id = :business_id";
        
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateSubscription($businessId, $subscriptionId) {
        try {
            // Get new subscription details
            $sql = "SELECT duration_months FROM subscriptions WHERE id = :subscription_id";
            $stmt = $this->query($sql, ['subscription_id' => $subscriptionId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$subscription) {
                throw new Exception("Invalid subscription");
            }

            // Update business subscription
            $data = [
                'subscription_id' => $subscriptionId,
                'subscription_start_date' => date('Y-m-d'),
                'subscription_end_date' => date('Y-m-d', strtotime("+{$subscription['duration_months']} months")),
                'status' => 'active'
            ];

            return $this->update($businessId, $data);
        } catch (Exception $e) {
            throw new Exception("Failed to update subscription: " . $e->getMessage());
        }
    }

    public function checkUserLimit($businessId) {
        $sql = "SELECT COUNT(*) as user_count, s.max_users 
                FROM users u 
                JOIN businesses b ON u.business_id = b.id 
                JOIN subscriptions s ON b.subscription_id = s.id 
                WHERE b.id = :business_id 
                GROUP BY s.max_users";
        
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['user_count'] >= $result['max_users']) {
            throw new Exception("User limit reached for current subscription");
        }

        return true;
    }

    public function checkBranchLimit($businessId) {
        $sql = "SELECT COUNT(*) as branch_count, s.max_branches 
                FROM branches br 
                JOIN businesses b ON br.business_id = b.id 
                JOIN subscriptions s ON b.subscription_id = s.id 
                WHERE b.id = :business_id 
                GROUP BY s.max_branches";
        
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['branch_count'] >= $result['max_branches']) {
            throw new Exception("Branch limit reached for current subscription");
        }

        return true;
    }

    private function generateLicenseKey() {
        $key = '';
        $pattern = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for ($i = 0; $i < 16; $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $key .= '-';
            }
            $key .= $pattern[random_int(0, strlen($pattern) - 1)];
        }
        return $key;
    }

    public function getBusinessStats($businessId) {
        // Get total sales for today
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_sales 
                FROM sales 
                WHERE business_id = :business_id 
                AND DATE(created_at) = CURDATE()";
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $todaySales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

        // Get total products
        $sql = "SELECT COUNT(*) as total_products 
                FROM products 
                WHERE business_id = :business_id 
                AND status = 'active'";
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

        // Get low stock alerts
        $sql = "SELECT COUNT(*) as low_stock_count 
                FROM products 
                WHERE business_id = :business_id 
                AND current_stock <= min_stock 
                AND status = 'active'";
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $lowStockCount = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock_count'];

        // Get total customers
        $sql = "SELECT COUNT(*) as total_customers 
                FROM customers 
                WHERE business_id = :business_id";
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

        return [
            'today_sales' => $todaySales,
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
            'total_customers' => $totalCustomers
        ];
    }
}
?>

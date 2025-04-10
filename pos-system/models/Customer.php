<?php
require_once __DIR__ . '/BaseModel.php';

class Customer extends BaseModel {
    protected $table = 'customers';

    public function __construct() {
        parent::__construct();
    }

    public function createCustomer($data) {
        try {
            // Validate required fields
            $requiredFields = ['first_name', 'business_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Validate email if provided
            if (!empty($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }

                // Check if email already exists
                $existingCustomer = $this->findOne([
                    'email' => $data['email'],
                    'business_id' => $data['business_id']
                ]);
                
                if ($existingCustomer) {
                    throw new Exception("Email already exists");
                }
            }

            // Create customer
            return $this->create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to create customer: " . $e->getMessage());
        }
    }

    public function updateCustomer($customerId, $data) {
        try {
            // Validate customer exists
            $customer = $this->findById($customerId);
            if (!$customer) {
                throw new Exception("Customer not found");
            }

            // Validate email if being updated
            if (!empty($data['email']) && $data['email'] !== $customer['email']) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }

                // Check if email already exists
                $existingCustomer = $this->findOne([
                    'email' => $data['email'],
                    'business_id' => $customer['business_id']
                ]);
                
                if ($existingCustomer) {
                    throw new Exception("Email already exists");
                }
            }

            // Update customer
            return $this->update($customerId, $data);
        } catch (Exception $e) {
            throw new Exception("Failed to update customer: " . $e->getMessage());
        }
    }

    public function getCustomers($businessId, $filters = [], $page = 1, $limit = 10) {
        try {
            $conditions = ['business_id' => $businessId];
            $params = ['business_id' => $businessId];

            // Apply filters
            if (!empty($filters['search'])) {
                $conditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }

            // Build WHERE clause
            $where = "";
            foreach ($conditions as $key => $value) {
                if (is_numeric($key)) {
                    $where .= " AND " . $value;
                } else {
                    $where .= " AND " . $key . " = :" . str_replace('.', '_', $key);
                }
            }

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1" . $where;
            $stmt = $this->query($countSql, $params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get customers
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM sales s WHERE s.customer_id = c.id) as total_sales,
                    (SELECT SUM(total_amount) FROM sales s WHERE s.customer_id = c.id) as total_spent
                    FROM {$this->table} c 
                    WHERE 1=1" . $where . "
                    ORDER BY c.first_name ASC 
                    LIMIT :offset, :limit";
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $stmt = $this->query($sql, $params);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page,
                'customers' => $customers
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get customers: " . $e->getMessage());
        }
    }

    public function getCustomerDetails($customerId) {
        try {
            // Get customer basic info
            $customer = $this->findById($customerId);
            if (!$customer) {
                throw new Exception("Customer not found");
            }

            // Get purchase history
            $sql = "SELECT s.*, 
                    COUNT(si.id) as total_items,
                    GROUP_CONCAT(p.name SEPARATOR ', ') as products
                    FROM sales s
                    JOIN sale_items si ON s.id = si.sale_id
                    JOIN products p ON si.product_id = p.id
                    WHERE s.customer_id = :customer_id
                    GROUP BY s.id
                    ORDER BY s.created_at DESC
                    LIMIT 10";
            
            $stmt = $this->query($sql, ['customer_id' => $customerId]);
            $purchaseHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics
            $sql = "SELECT 
                    COUNT(*) as total_purchases,
                    SUM(total_amount) as total_spent,
                    AVG(total_amount) as average_purchase,
                    MAX(created_at) as last_purchase
                    FROM sales 
                    WHERE customer_id = :customer_id";
            
            $stmt = $this->query($sql, ['customer_id' => $customerId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'customer' => $customer,
                'purchase_history' => $purchaseHistory,
                'stats' => $stats
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get customer details: " . $e->getMessage());
        }
    }

    public function searchCustomers($businessId, $query) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE business_id = :business_id 
                    AND (first_name LIKE :query 
                        OR last_name LIKE :query 
                        OR email LIKE :query 
                        OR phone LIKE :query)
                    LIMIT 10";
            
            $params = [
                'business_id' => $businessId,
                'query' => "%{$query}%"
            ];
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to search customers: " . $e->getMessage());
        }
    }

    public function getTopCustomers($businessId, $limit = 10) {
        try {
            $sql = "SELECT 
                    c.*,
                    COUNT(s.id) as total_purchases,
                    SUM(s.total_amount) as total_spent,
                    AVG(s.total_amount) as average_purchase,
                    MAX(s.created_at) as last_purchase
                    FROM {$this->table} c
                    JOIN sales s ON c.id = s.customer_id
                    WHERE c.business_id = :business_id
                    GROUP BY c.id
                    ORDER BY total_spent DESC
                    LIMIT :limit";
            
            $stmt = $this->query($sql, [
                'business_id' => $businessId,
                'limit' => $limit
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get top customers: " . $e->getMessage());
        }
    }
}
?>

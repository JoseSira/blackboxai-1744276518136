<?php
require_once __DIR__ . '/BaseModel.php';

class Branch extends BaseModel {
    protected $table = 'branches';

    public function __construct() {
        parent::__construct();
    }

    public function createBranch($data) {
        try {
            // Validate required fields
            $requiredFields = ['name', 'business_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Check if branch name already exists for this business
            $existingBranch = $this->findOne([
                'name' => $data['name'],
                'business_id' => $data['business_id']
            ]);
            
            if ($existingBranch) {
                throw new Exception("Branch name already exists");
            }

            // Create branch
            return $this->create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to create branch: " . $e->getMessage());
        }
    }

    public function updateBranch($branchId, $data) {
        try {
            // Validate branch exists
            $branch = $this->findById($branchId);
            if (!$branch) {
                throw new Exception("Branch not found");
            }

            // Check if branch name already exists (excluding current branch)
            if (!empty($data['name']) && $data['name'] !== $branch['name']) {
                $existingBranch = $this->findOne([
                    'name' => $data['name'],
                    'business_id' => $branch['business_id']
                ]);
                
                if ($existingBranch) {
                    throw new Exception("Branch name already exists");
                }
            }

            // Update branch
            return $this->update($branchId, $data);
        } catch (Exception $e) {
            throw new Exception("Failed to update branch: " . $e->getMessage());
        }
    }

    public function getBranches($businessId, $filters = [], $page = 1, $limit = 10) {
        try {
            $conditions = ['business_id' => $businessId];
            $params = ['business_id' => $businessId];

            // Apply filters
            if (!empty($filters['search'])) {
                $conditions[] = "(name LIKE :search OR address LIKE :search OR phone LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }
            if (!empty($filters['status'])) {
                $conditions['status'] = $filters['status'];
                $params['status'] = $filters['status'];
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

            // Get branches with statistics
            $sql = "SELECT b.*, 
                    (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id) as total_users,
                    (SELECT COUNT(*) FROM register_sessions rs WHERE rs.branch_id = b.id AND rs.status = 'open') as open_registers,
                    (SELECT COUNT(*) FROM sales s WHERE s.branch_id = b.id) as total_sales,
                    (SELECT COALESCE(SUM(total_amount), 0) FROM sales s WHERE s.branch_id = b.id) as total_revenue
                    FROM {$this->table} b 
                    WHERE 1=1" . $where . "
                    ORDER BY b.name ASC 
                    LIMIT :offset, :limit";
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $stmt = $this->query($sql, $params);
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page,
                'branches' => $branches
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get branches: " . $e->getMessage());
        }
    }

    public function getBranchDetails($branchId) {
        try {
            // Get branch basic info
            $branch = $this->findById($branchId);
            if (!$branch) {
                throw new Exception("Branch not found");
            }

            // Get users assigned to this branch
            $sql = "SELECT u.id, u.username, u.email, u.role, u.status
                    FROM users u
                    WHERE u.branch_id = :branch_id
                    ORDER BY u.username";
            
            $stmt = $this->query($sql, ['branch_id' => $branchId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get sales statistics
            $sql = "SELECT 
                    COUNT(*) as total_sales,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(AVG(total_amount), 0) as average_sale,
                    MAX(created_at) as last_sale
                    FROM sales 
                    WHERE branch_id = :branch_id";
            
            $stmt = $this->query($sql, ['branch_id' => $branchId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get current register sessions
            $sql = "SELECT rs.*, u.username as user_name
                    FROM register_sessions rs
                    JOIN users u ON rs.user_id = u.id
                    WHERE rs.branch_id = :branch_id AND rs.status = 'open'";
            
            $stmt = $this->query($sql, ['branch_id' => $branchId]);
            $openSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'branch' => $branch,
                'users' => $users,
                'stats' => $stats,
                'open_sessions' => $openSessions
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get branch details: " . $e->getMessage());
        }
    }

    public function deactivateBranch($branchId) {
        try {
            // Check if branch has open sessions
            $sql = "SELECT COUNT(*) as count FROM register_sessions 
                    WHERE branch_id = :branch_id AND status = 'open'";
            $stmt = $this->query($sql, ['branch_id' => $branchId]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                throw new Exception("Cannot deactivate branch with open register sessions");
            }

            // Update branch status
            return $this->update($branchId, ['status' => 'inactive']);
        } catch (Exception $e) {
            throw new Exception("Failed to deactivate branch: " . $e->getMessage());
        }
    }

    public function validateBranchData($data, $isUpdate = false) {
        $errors = [];

        // Name is required
        if (empty($data['name'])) {
            $errors['name'] = 'Branch name is required';
        }

        // Phone validation if provided
        if (!empty($data['phone'])) {
            if (!preg_match('/^[0-9\-\(\)\/\+\s]*$/', $data['phone'])) {
                $errors['phone'] = 'Invalid phone number format';
            }
        }

        // Email validation if provided
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }

        return $errors;
    }
}
?>

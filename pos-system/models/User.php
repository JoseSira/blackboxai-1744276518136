<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';

    public function __construct() {
        parent::__construct();
    }

    public function createUser($data) {
        try {
            // Validate required fields
            $requiredFields = ['username', 'email', 'password', 'role', 'business_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Check if username already exists
            if ($this->exists(['username' => $data['username']])) {
                throw new Exception("Username already exists");
            }

            // Check if email already exists
            if ($this->exists(['email' => $data['email']])) {
                throw new Exception("Email already exists");
            }

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => PASSWORD_HASH_COST]);

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'active';
            }

            // Create user
            return $this->create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to create user: " . $e->getMessage());
        }
    }

    public function updateUser($userId, $data) {
        try {
            // Get current user data
            $user = $this->findById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Check if username is being changed and already exists
            if (isset($data['username']) && $data['username'] !== $user['username']) {
                if ($this->exists(['username' => $data['username']])) {
                    throw new Exception("Username already exists");
                }
            }

            // Check if email is being changed and already exists
            if (isset($data['email']) && $data['email'] !== $user['email']) {
                if ($this->exists(['email' => $data['email']])) {
                    throw new Exception("Email already exists");
                }
            }

            // Hash password if being updated
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => PASSWORD_HASH_COST]);
            } else {
                unset($data['password']);
            }

            // Update user
            return $this->update($userId, $data);
        } catch (Exception $e) {
            throw new Exception("Failed to update user: " . $e->getMessage());
        }
    }

    public function getUsers($businessId, $filters = [], $page = 1, $limit = 10) {
        try {
            $conditions = ['business_id' => $businessId];
            $params = ['business_id' => $businessId];

            // Apply filters
            if (!empty($filters['search'])) {
                $conditions[] = "(username LIKE :search OR email LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }
            if (!empty($filters['role'])) {
                $conditions['role'] = $filters['role'];
                $params['role'] = $filters['role'];
            }
            if (!empty($filters['status'])) {
                $conditions['status'] = $filters['status'];
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['branch_id'])) {
                $conditions['branch_id'] = $filters['branch_id'];
                $params['branch_id'] = $filters['branch_id'];
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

            // Get users with branch info
            $sql = "SELECT u.*, b.name as branch_name 
                    FROM {$this->table} u 
                    LEFT JOIN branches b ON u.branch_id = b.id 
                    WHERE 1=1" . $where . "
                    ORDER BY u.username ASC 
                    LIMIT :offset, :limit";
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $stmt = $this->query($sql, $params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page,
                'users' => $users
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get users: " . $e->getMessage());
        }
    }

    public function getUserPermissions($userId) {
        try {
            $sql = "SELECT permission FROM user_permissions WHERE user_id = :user_id";
            $stmt = $this->query($sql, ['user_id' => $userId]);
            $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $permissions;
        } catch (Exception $e) {
            throw new Exception("Failed to get user permissions: " . $e->getMessage());
        }
    }

    public function setUserPermissions($userId, $permissions) {
        try {
            $this->conn->beginTransaction();

            // Delete existing permissions
            $sql = "DELETE FROM user_permissions WHERE user_id = :user_id";
            $this->query($sql, ['user_id' => $userId]);

            // Insert new permissions
            if (!empty($permissions)) {
                $values = [];
                $params = [];
                foreach ($permissions as $i => $permission) {
                    $values[] = "(:user_id_{$i}, :permission_{$i})";
                    $params["user_id_{$i}"] = $userId;
                    $params["permission_{$i}"] = $permission;
                }
                
                $sql = "INSERT INTO user_permissions (user_id, permission) VALUES " . implode(', ', $values);
                $this->query($sql, $params);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to set user permissions: " . $e->getMessage());
        }
    }

    public function validateLogin($username, $password) {
        try {
            // Get user by username
            $user = $this->findOne(['username' => $username, 'status' => 'active']);
            if (!$user) {
                return false;
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                return false;
            }

            // Get user permissions
            $permissions = $this->getUserPermissions($user['id']);

            // Return user data without password
            unset($user['password']);
            $user['permissions'] = $permissions;

            return $user;
        } catch (Exception $e) {
            throw new Exception("Failed to validate login: " . $e->getMessage());
        }
    }

    public function validateUserData($data, $isUpdate = false) {
        $errors = [];

        // Username validation
        if (isset($data['username'])) {
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
                $errors['username'] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
            }
        }

        // Email validation
        if (isset($data['email'])) {
            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }

        // Password validation (required for new users)
        if (!$isUpdate || isset($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }
        }

        // Role validation
        if (isset($data['role'])) {
            if (empty($data['role'])) {
                $errors['role'] = 'Role is required';
            } elseif (!array_key_exists($data['role'], USER_ROLES)) {
                $errors['role'] = 'Invalid role';
            }
        }

        // Status validation
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'inactive'])) {
                $errors['status'] = 'Invalid status';
            }
        }

        return $errors;
    }

    public function deactivateUser($userId) {
        try {
            // Check if user exists
            $user = $this->findById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Cannot deactivate last active admin
            if ($user['role'] === 'admin') {
                $activeAdmins = $this->count([
                    'business_id' => $user['business_id'],
                    'role' => 'admin',
                    'status' => 'active'
                ]);
                if ($activeAdmins <= 1) {
                    throw new Exception("Cannot deactivate the last active administrator");
                }
            }

            // Update user status
            return $this->update($userId, ['status' => 'inactive']);
        } catch (Exception $e) {
            throw new Exception("Failed to deactivate user: " . $e->getMessage());
        }
    }
}
?>

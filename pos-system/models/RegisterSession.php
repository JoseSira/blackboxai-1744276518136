<?php
require_once __DIR__ . '/BaseModel.php';

class RegisterSession extends BaseModel {
    protected $table = 'register_sessions';

    public function __construct() {
        parent::__construct();
    }

    public function openSession($data) {
        try {
            // Validate required fields
            $requiredFields = ['business_id', 'branch_id', 'user_id', 'initial_cash'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Check if there's already an open session for this register
            $openSession = $this->findOne([
                'branch_id' => $data['branch_id'],
                'status' => 'open'
            ]);

            if ($openSession) {
                throw new Exception("There's already an open session for this register");
            }

            // Create session
            $data['status'] = 'open';
            $data['opening_time'] = date('Y-m-d H:i:s');
            
            return $this->create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to open session: " . $e->getMessage());
        }
    }

    public function closeSession($sessionId, $data) {
        try {
            $this->conn->beginTransaction();

            // Get session
            $session = $this->findById($sessionId);
            if (!$session) {
                throw new Exception("Session not found");
            }

            if ($session['status'] !== 'open') {
                throw new Exception("Session is not open");
            }

            // Calculate expected cash
            $sql = "SELECT 
                    COALESCE(SUM(CASE 
                        WHEN payment_method = 'cash' THEN total_amount
                        ELSE 0
                    END), 0) as total_cash,
                    COALESCE(SUM(CASE 
                        WHEN payment_method = 'card' THEN total_amount
                        ELSE 0
                    END), 0) as total_card
                    FROM sales 
                    WHERE register_session_id = :session_id";
            
            $stmt = $this->query($sql, ['session_id' => $sessionId]);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            $expectedCash = $session['initial_cash'] + $totals['total_cash'];
            $cashDifference = $data['final_cash'] - $expectedCash;

            // Update session
            $updateData = [
                'status' => 'closed',
                'closing_time' => date('Y-m-d H:i:s'),
                'final_cash' => $data['final_cash'],
                'total_cash_sales' => $totals['total_cash'],
                'total_card_sales' => $totals['total_card'],
                'cash_difference' => $cashDifference,
                'notes' => $data['notes'] ?? null
            ];

            $this->update($sessionId, $updateData);

            $this->conn->commit();
            return [
                'session_id' => $sessionId,
                'expected_cash' => $expectedCash,
                'cash_difference' => $cashDifference,
                'totals' => $totals
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to close session: " . $e->getMessage());
        }
    }

    public function getSessionDetails($sessionId) {
        try {
            // Get session basic info
            $sql = "SELECT rs.*, 
                    u.username as user_name,
                    b.name as branch_name
                    FROM register_sessions rs
                    JOIN users u ON rs.user_id = u.id
                    JOIN branches b ON rs.branch_id = b.id
                    WHERE rs.id = :session_id";
            
            $stmt = $this->query($sql, ['session_id' => $sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception("Session not found");
            }

            // Get sales summary
            $sql = "SELECT 
                    COUNT(*) as total_sales,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total_amount ELSE 0 END), 0) as card_sales
                    FROM sales 
                    WHERE register_session_id = :session_id";
            
            $stmt = $this->query($sql, ['session_id' => $sessionId]);
            $sales = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get sales by hour
            $sql = "SELECT 
                    DATE_FORMAT(created_at, '%H:00') as hour,
                    COUNT(*) as sales_count,
                    COALESCE(SUM(total_amount), 0) as total_amount
                    FROM sales 
                    WHERE register_session_id = :session_id
                    GROUP BY DATE_FORMAT(created_at, '%H:00')
                    ORDER BY hour";
            
            $stmt = $this->query($sql, ['session_id' => $sessionId]);
            $salesByHour = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'session' => $session,
                'sales' => $sales,
                'sales_by_hour' => $salesByHour
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get session details: " . $e->getMessage());
        }
    }

    public function getCurrentSession($branchId) {
        try {
            return $this->findOne([
                'branch_id' => $branchId,
                'status' => 'open'
            ]);
        } catch (Exception $e) {
            throw new Exception("Failed to get current session: " . $e->getMessage());
        }
    }

    public function getSessions($businessId, $filters = [], $page = 1, $limit = 10) {
        try {
            $conditions = ['rs.business_id' => $businessId];
            $params = ['business_id' => $businessId];

            // Apply filters
            if (!empty($filters['branch_id'])) {
                $conditions['rs.branch_id'] = $filters['branch_id'];
                $params['branch_id'] = $filters['branch_id'];
            }
            if (!empty($filters['user_id'])) {
                $conditions['rs.user_id'] = $filters['user_id'];
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['status'])) {
                $conditions['rs.status'] = $filters['status'];
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(rs.opening_time) >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(rs.opening_time) <= :date_to";
                $params['date_to'] = $filters['date_to'];
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
            $countSql = "SELECT COUNT(*) as total 
                        FROM {$this->table} rs 
                        WHERE 1=1" . $where;
            $stmt = $this->query($countSql, $params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get sessions
            $sql = "SELECT rs.*, 
                    u.username as user_name,
                    b.name as branch_name,
                    (SELECT COUNT(*) FROM sales s WHERE s.register_session_id = rs.id) as total_sales,
                    (SELECT COALESCE(SUM(total_amount), 0) FROM sales s WHERE s.register_session_id = rs.id) as total_amount
                    FROM {$this->table} rs
                    JOIN users u ON rs.user_id = u.id
                    JOIN branches b ON rs.branch_id = b.id
                    WHERE 1=1" . $where . "
                    ORDER BY rs.opening_time DESC
                    LIMIT :offset, :limit";

            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $stmt = $this->query($sql, $params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page,
                'sessions' => $sessions
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get sessions: " . $e->getMessage());
        }
    }
}
?>

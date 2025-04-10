<?php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Product.php';

class Sale extends BaseModel {
    protected $table = 'sales';
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function createSale($saleData, $items) {
        try {
            $this->conn->beginTransaction();

            // Validate required fields
            $requiredFields = ['business_id', 'branch_id', 'user_id', 'register_session_id'];
            foreach ($requiredFields as $field) {
                if (!isset($saleData[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            foreach ($items as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
                $taxAmount += $item['tax_amount'];
            }

            // Apply discount if any
            $discountAmount = isset($saleData['discount_amount']) ? $saleData['discount_amount'] : 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Create sale record
            $saleData['subtotal'] = $subtotal;
            $saleData['tax_amount'] = $taxAmount;
            $saleData['total_amount'] = $totalAmount;
            
            $saleId = $this->create($saleData);

            // Create sale items and update inventory
            foreach ($items as $item) {
                // Get product details
                $product = $this->productModel->findById($item['product_id']);
                if (!$product) {
                    throw new Exception("Product not found: {$item['product_id']}");
                }

                // Check stock
                if ($product['unit_type'] !== 'combo' && $product['current_stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$product['name']}");
                }

                // Create sale item
                $itemData = [
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total_amount' => ($item['unit_price'] * $item['quantity']) + $item['tax_amount'] - ($item['discount_amount'] ?? 0)
                ];

                $sql = "INSERT INTO sale_items 
                        (sale_id, product_id, quantity, unit_price, tax_rate, tax_amount, discount_amount, total_amount) 
                        VALUES (:sale_id, :product_id, :quantity, :unit_price, :tax_rate, :tax_amount, :discount_amount, :total_amount)";
                $this->query($sql, $itemData);

                // Update inventory
                if ($product['unit_type'] === 'combo') {
                    // Handle combo products
                    $this->updateComboInventory($product['id'], $item['quantity'], 'sale');
                } else {
                    // Regular product
                    $this->productModel->updateStock(
                        $product['id'],
                        -$item['quantity'],
                        'sale',
                        "Sale #{$saleId}"
                    );
                }
            }

            $this->conn->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to create sale: " . $e->getMessage());
        }
    }

    private function updateComboInventory($comboId, $quantity, $movementType) {
        // Get combo components
        $sql = "SELECT product_id, quantity as component_quantity 
                FROM product_combos 
                WHERE combo_id = :combo_id";
        $stmt = $this->query($sql, ['combo_id' => $comboId]);
        $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update stock for each component
        foreach ($components as $component) {
            $totalQuantity = $component['component_quantity'] * $quantity;
            $this->productModel->updateStock(
                $component['product_id'],
                -$totalQuantity,
                $movementType,
                "Combo sale #{$comboId}"
            );
        }
    }

    public function getSale($saleId) {
        try {
            // Get sale details
            $sql = "SELECT s.*, 
                    u.username as user_name,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    b.name as branch_name
                    FROM sales s
                    LEFT JOIN users u ON s.user_id = u.id
                    LEFT JOIN customers c ON s.customer_id = c.id
                    LEFT JOIN branches b ON s.branch_id = b.id
                    WHERE s.id = :sale_id";
            
            $stmt = $this->query($sql, ['sale_id' => $saleId]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                throw new Exception("Sale not found");
            }

            // Get sale items
            $sql = "SELECT si.*, p.name as product_name, p.sku
                    FROM sale_items si
                    JOIN products p ON si.product_id = p.id
                    WHERE si.sale_id = :sale_id";
            
            $stmt = $this->query($sql, ['sale_id' => $saleId]);
            $sale['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $sale;
        } catch (Exception $e) {
            throw new Exception("Failed to get sale: " . $e->getMessage());
        }
    }

    public function getSales($businessId, $filters = [], $page = 1, $limit = 10) {
        try {
            $conditions = ['s.business_id' => $businessId];
            $params = ['business_id' => $businessId];

            // Apply filters
            if (!empty($filters['branch_id'])) {
                $conditions['s.branch_id'] = $filters['branch_id'];
                $params['branch_id'] = $filters['branch_id'];
            }
            if (!empty($filters['user_id'])) {
                $conditions['s.user_id'] = $filters['user_id'];
                $params['user_id'] = $filters['user_id'];
            }
            if (!empty($filters['customer_id'])) {
                $conditions['s.customer_id'] = $filters['customer_id'];
                $params['customer_id'] = $filters['customer_id'];
            }
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(s.created_at) >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(s.created_at) <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            if (!empty($filters['payment_method'])) {
                $conditions['s.payment_method'] = $filters['payment_method'];
                $params['payment_method'] = $filters['payment_method'];
            }
            if (!empty($filters['payment_status'])) {
                $conditions['s.payment_status'] = $filters['payment_status'];
                $params['payment_status'] = $filters['payment_status'];
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
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} s WHERE 1=1" . $where;
            $stmt = $this->query($countSql, $params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get sales
            $sql = "SELECT s.*, 
                    u.username as user_name,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    b.name as branch_name
                    FROM {$this->table} s
                    LEFT JOIN users u ON s.user_id = u.id
                    LEFT JOIN customers c ON s.customer_id = c.id
                    LEFT JOIN branches b ON s.branch_id = b.id
                    WHERE 1=1" . $where . "
                    ORDER BY s.created_at DESC
                    LIMIT :offset, :limit";

            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $stmt = $this->query($sql, $params);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page,
                'sales' => $sales
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get sales: " . $e->getMessage());
        }
    }

    public function cancelSale($saleId) {
        try {
            $this->conn->beginTransaction();

            // Get sale details
            $sale = $this->getSale($saleId);
            if ($sale['payment_status'] === 'cancelled') {
                throw new Exception("Sale is already cancelled");
            }

            // Restore inventory
            foreach ($sale['items'] as $item) {
                $product = $this->productModel->findById($item['product_id']);
                if ($product['unit_type'] === 'combo') {
                    $this->updateComboInventory($product['id'], -$item['quantity'], 'cancelled');
                } else {
                    $this->productModel->updateStock(
                        $item['product_id'],
                        $item['quantity'],
                        'cancelled',
                        "Cancelled sale #{$saleId}"
                    );
                }
            }

            // Update sale status
            $this->update($saleId, ['payment_status' => 'cancelled']);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to cancel sale: " . $e->getMessage());
        }
    }

    public function getSalesReport($businessId, $startDate, $endDate, $branchId = null) {
        try {
            $params = [
                'business_id' => $businessId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            $branchCondition = "";
            if ($branchId) {
                $branchCondition = " AND s.branch_id = :branch_id";
                $params['branch_id'] = $branchId;
            }

            $sql = "SELECT 
                    DATE(s.created_at) as date,
                    COUNT(*) as total_sales,
                    SUM(s.subtotal) as total_subtotal,
                    SUM(s.tax_amount) as total_tax,
                    SUM(s.discount_amount) as total_discount,
                    SUM(s.total_amount) as total_amount,
                    COUNT(DISTINCT s.customer_id) as unique_customers
                    FROM sales s
                    WHERE s.business_id = :business_id
                    AND DATE(s.created_at) BETWEEN :start_date AND :end_date
                    AND s.payment_status = 'completed'
                    {$branchCondition}
                    GROUP BY DATE(s.created_at)
                    ORDER BY DATE(s.created_at)";

            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get sales report: " . $e->getMessage());
        }
    }

    public function getTopProducts($businessId, $startDate, $endDate, $limit = 10) {
        try {
            $sql = "SELECT 
                    p.id, p.name, p.sku,
                    COUNT(DISTINCT s.id) as sale_count,
                    SUM(si.quantity) as total_quantity,
                    SUM(si.total_amount) as total_amount
                    FROM products p
                    JOIN sale_items si ON p.id = si.product_id
                    JOIN sales s ON si.sale_id = s.id
                    WHERE s.business_id = :business_id
                    AND DATE(s.created_at) BETWEEN :start_date AND :end_date
                    AND s.payment_status = 'completed'
                    GROUP BY p.id, p.name, p.sku
                    ORDER BY total_amount DESC
                    LIMIT :limit";

            $stmt = $this->query($sql, [
                'business_id' => $businessId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'limit' => $limit
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get top products: " . $e->getMessage());
        }
    }
}
?>

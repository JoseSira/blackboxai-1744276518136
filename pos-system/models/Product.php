<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    protected $table = 'products';

    public function __construct() {
        parent::__construct();
    }

    public function createProduct($data) {
        try {
            $this->conn->beginTransaction();

            // Validate required fields
            $requiredFields = ['name', 'business_id', 'price', 'unit_type'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSKU($data['business_id']);
            }

            // Create the product
            $productId = $this->create($data);

            // If it's a combo product, add its components
            if ($data['unit_type'] === 'combo' && !empty($data['combo_products'])) {
                foreach ($data['combo_products'] as $comboProduct) {
                    $sql = "INSERT INTO product_combos (combo_id, product_id, quantity) 
                            VALUES (:combo_id, :product_id, :quantity)";
                    $params = [
                        'combo_id' => $productId,
                        'product_id' => $comboProduct['product_id'],
                        'quantity' => $comboProduct['quantity']
                    ];
                    $this->query($sql, $params);
                }
            }

            // Create initial inventory movement
            if (isset($data['current_stock']) && $data['current_stock'] > 0) {
                $sql = "INSERT INTO inventory_movements 
                        (business_id, product_id, movement_type, quantity, notes) 
                        VALUES (:business_id, :product_id, 'adjustment', :quantity, 'Initial stock')";
                $params = [
                    'business_id' => $data['business_id'],
                    'product_id' => $productId,
                    'quantity' => $data['current_stock']
                ];
                $this->query($sql, $params);
            }

            $this->conn->commit();
            return $productId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to create product: " . $e->getMessage());
        }
    }

    public function updateProduct($productId, $data) {
        try {
            $this->conn->beginTransaction();

            // Get current product data
            $currentProduct = $this->findById($productId);
            if (!$currentProduct) {
                throw new Exception("Product not found");
            }

            // Update product
            $this->update($productId, $data);

            // If it's a combo product, update its components
            if ($currentProduct['unit_type'] === 'combo' && isset($data['combo_products'])) {
                // Remove existing combo products
                $sql = "DELETE FROM product_combos WHERE combo_id = :combo_id";
                $this->query($sql, ['combo_id' => $productId]);

                // Add new combo products
                foreach ($data['combo_products'] as $comboProduct) {
                    $sql = "INSERT INTO product_combos (combo_id, product_id, quantity) 
                            VALUES (:combo_id, :product_id, :quantity)";
                    $params = [
                        'combo_id' => $productId,
                        'product_id' => $comboProduct['product_id'],
                        'quantity' => $comboProduct['quantity']
                    ];
                    $this->query($sql, $params);
                }
            }

            // If stock was updated, create inventory movement
            if (isset($data['current_stock']) && $data['current_stock'] != $currentProduct['current_stock']) {
                $difference = $data['current_stock'] - $currentProduct['current_stock'];
                $sql = "INSERT INTO inventory_movements 
                        (business_id, product_id, movement_type, quantity, notes) 
                        VALUES (:business_id, :product_id, 'adjustment', :quantity, 'Stock adjustment')";
                $params = [
                    'business_id' => $currentProduct['business_id'],
                    'product_id' => $productId,
                    'quantity' => $difference
                ];
                $this->query($sql, $params);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to update product: " . $e->getMessage());
        }
    }

    public function getProducts($businessId, $filters = [], $page = 1, $limit = 10) {
        try {
            $conditions = ['p.business_id' => $businessId];
            $params = ['business_id' => $businessId];

            // Build query conditions based on filters
            if (!empty($filters['category_id'])) {
                $conditions['p.category_id'] = $filters['category_id'];
                $params['category_id'] = $filters['category_id'];
            }
            if (!empty($filters['search'])) {
                $conditions[] = "(p.name LIKE :search OR p.sku LIKE :search OR p.barcode LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }
            if (isset($filters['status'])) {
                $conditions['p.status'] = $filters['status'];
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
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} p WHERE 1=1" . $where;
            $stmt = $this->query($countSql, $params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get products
            $sql = "SELECT p.*, c.name as category_name 
                    FROM {$this->table} p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE 1=1" . $where . "
                    ORDER BY p.name ASC 
                    LIMIT :offset, :limit";
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $stmt = $this->query($sql, $params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get combo products for combo type products
            foreach ($products as &$product) {
                if ($product['unit_type'] === 'combo') {
                    $sql = "SELECT pc.*, p.name, p.sku 
                            FROM product_combos pc 
                            JOIN products p ON pc.product_id = p.id 
                            WHERE pc.combo_id = :combo_id";
                    $stmt = $this->query($sql, ['combo_id' => $product['id']]);
                    $product['combo_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            return [
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page,
                'products' => $products
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get products: " . $e->getMessage());
        }
    }

    public function updateStock($productId, $quantity, $movementType, $notes = '') {
        try {
            $this->conn->beginTransaction();

            // Get product
            $product = $this->findById($productId);
            if (!$product) {
                throw new Exception("Product not found");
            }

            // Update stock
            $newStock = $product['current_stock'] + $quantity;
            $this->update($productId, ['current_stock' => $newStock]);

            // Create inventory movement
            $sql = "INSERT INTO inventory_movements 
                    (business_id, product_id, movement_type, quantity, notes) 
                    VALUES (:business_id, :product_id, :movement_type, :quantity, :notes)";
            $params = [
                'business_id' => $product['business_id'],
                'product_id' => $productId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'notes' => $notes
            ];
            $this->query($sql, $params);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to update stock: " . $e->getMessage());
        }
    }

    public function getLowStockProducts($businessId) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.business_id = :business_id 
                AND p.current_stock <= p.min_stock 
                AND p.status = 'active'";
        
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateSKU($businessId) {
        // Get count of products for this business
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE business_id = :business_id";
        $stmt = $this->query($sql, ['business_id' => $businessId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Generate SKU: P + BusinessID + Sequential Number (padded to 6 digits)
        return 'P' . str_pad($businessId, 3, '0', STR_PAD_LEFT) . 
               str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }

    public function getInventoryMovements($productId, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM inventory_movements 
                WHERE product_id = :product_id";
        $params = ['product_id' => $productId];

        if ($startDate) {
            $sql .= " AND DATE(created_at) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND DATE(created_at) <= :end_date";
            $params['end_date'] = $endDate;
        }

        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

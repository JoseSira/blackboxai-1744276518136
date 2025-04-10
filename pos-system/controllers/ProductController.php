<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/AuthController.php';

class ProductController {
    private $productModel;
    private $auth;

    public function __construct() {
        $this->productModel = new Product();
        $this->auth = new AuthController();
    }

    public function addProduct($data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();
            $data['business_id'] = $currentUser['business_id'];

            // Validate price and cost
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                throw new Exception("Invalid price value");
            }
            if (isset($data['cost']) && (!is_numeric($data['cost']) || $data['cost'] < 0)) {
                throw new Exception("Invalid cost value");
            }

            // Handle combo products
            if ($data['unit_type'] === 'combo') {
                if (empty($data['combo_products'])) {
                    throw new Exception("Combo products are required for combo type");
                }
                // Validate combo products
                foreach ($data['combo_products'] as $comboProduct) {
                    if (!isset($comboProduct['product_id']) || !isset($comboProduct['quantity']) || 
                        $comboProduct['quantity'] <= 0) {
                        throw new Exception("Invalid combo product data");
                    }
                }
            }

            // Create product
            $productId = $this->productModel->createProduct($data);

            return [
                'success' => true,
                'product_id' => $productId,
                'message' => 'Product added successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateProduct($productId, $data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify product belongs to user's business
            $product = $this->productModel->findById($productId);
            if (!$product || $product['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Product not found");
            }

            // Validate price and cost
            if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
                throw new Exception("Invalid price value");
            }
            if (isset($data['cost']) && (!is_numeric($data['cost']) || $data['cost'] < 0)) {
                throw new Exception("Invalid cost value");
            }

            // Handle combo products
            if (isset($data['unit_type']) && $data['unit_type'] === 'combo') {
                if (empty($data['combo_products'])) {
                    throw new Exception("Combo products are required for combo type");
                }
                // Validate combo products
                foreach ($data['combo_products'] as $comboProduct) {
                    if (!isset($comboProduct['product_id']) || !isset($comboProduct['quantity']) || 
                        $comboProduct['quantity'] <= 0) {
                        throw new Exception("Invalid combo product data");
                    }
                }
            }

            // Update product
            $this->productModel->updateProduct($productId, $data);

            return [
                'success' => true,
                'message' => 'Product updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getProducts($filters = [], $page = 1, $limit = 10) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get products
            $result = $this->productModel->getProducts(
                $currentUser['business_id'],
                $filters,
                $page,
                $limit
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

    public function updateStock($productId, $quantity, $movementType, $notes = '') {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify product belongs to user's business
            $product = $this->productModel->findById($productId);
            if (!$product || $product['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Product not found");
            }

            // Validate quantity
            if (!is_numeric($quantity)) {
                throw new Exception("Invalid quantity value");
            }

            // Update stock
            $this->productModel->updateStock($productId, $quantity, $movementType, $notes);

            return [
                'success' => true,
                'message' => 'Stock updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getLowStockProducts() {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get low stock products
            $products = $this->productModel->getLowStockProducts($currentUser['business_id']);

            return [
                'success' => true,
                'data' => $products
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getInventoryMovements($productId, $startDate = null, $endDate = null) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify product belongs to user's business
            $product = $this->productModel->findById($productId);
            if (!$product || $product['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Product not found");
            }

            // Get inventory movements
            $movements = $this->productModel->getInventoryMovements($productId, $startDate, $endDate);

            return [
                'success' => true,
                'data' => $movements
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deactivateProduct($productId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_products');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify product belongs to user's business
            $product = $this->productModel->findById($productId);
            if (!$product || $product['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Product not found");
            }

            // Deactivate product
            $this->productModel->update($productId, ['status' => 'inactive']);

            return [
                'success' => true,
                'message' => 'Product deactivated successfully'
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

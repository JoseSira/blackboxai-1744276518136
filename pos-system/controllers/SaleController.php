<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/AuthController.php';

class SaleController {
    private $saleModel;
    private $productModel;
    private $auth;

    public function __construct() {
        $this->saleModel = new Sale();
        $this->productModel = new Product();
        $this->auth = new AuthController();
    }

    public function createSale($saleData, $items) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_sales');

            // Get current user's business and branch
            $currentUser = $this->auth->getCurrentUser();
            $saleData['business_id'] = $currentUser['business_id'];
            $saleData['user_id'] = $currentUser['id'];

            // Validate items
            if (empty($items)) {
                throw new Exception("No items in sale");
            }

            // Validate each item and calculate totals
            foreach ($items as &$item) {
                $product = $this->productModel->findById($item['product_id']);
                if (!$product) {
                    throw new Exception("Product not found: {$item['product_id']}");
                }

                // Check if product belongs to the same business
                if ($product['business_id'] !== $currentUser['business_id']) {
                    throw new Exception("Invalid product");
                }

                // Check stock
                if ($product['unit_type'] !== 'combo' && $product['current_stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$product['name']}");
                }

                // Calculate tax
                $item['tax_amount'] = ($item['unit_price'] * $item['quantity']) * ($product['tax_rate'] / 100);
                $item['tax_rate'] = $product['tax_rate'];
            }

            // Create sale
            $saleId = $this->saleModel->createSale($saleData, $items);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'message' => 'Sale completed successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getSale($saleId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_sales');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get sale
            $sale = $this->saleModel->getSale($saleId);
            
            // Verify sale belongs to user's business
            if (!$sale || $sale['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Sale not found");
            }

            return [
                'success' => true,
                'data' => $sale
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getSales($filters = [], $page = 1) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_sales');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get sales
            $result = $this->saleModel->getSales(
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

    public function cancelSale($saleId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_sales');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify sale belongs to user's business
            $sale = $this->saleModel->getSale($saleId);
            if (!$sale || $sale['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Sale not found");
            }

            // Cancel sale
            $this->saleModel->cancelSale($saleId);

            return [
                'success' => true,
                'message' => 'Sale cancelled successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function generateReceipt($saleId) {
        try {
            // Get sale details
            $sale = $this->saleModel->getSale($saleId);
            if (!$sale) {
                throw new Exception("Sale not found");
            }

            // Format receipt data
            $receipt = [
                'business' => [
                    'name' => $sale['business_name'],
                    'branch' => $sale['branch_name'],
                    'address' => $sale['branch_address'],
                    'phone' => $sale['branch_phone']
                ],
                'sale' => [
                    'id' => $sale['id'],
                    'date' => $sale['created_at'],
                    'cashier' => $sale['user_name'],
                    'customer' => $sale['customer_first_name'] . ' ' . $sale['customer_last_name'],
                    'payment_method' => $sale['payment_method'],
                    'items' => $sale['items'],
                    'subtotal' => $sale['subtotal'],
                    'tax_amount' => $sale['tax_amount'],
                    'discount_amount' => $sale['discount_amount'],
                    'total_amount' => $sale['total_amount']
                ]
            ];

            return [
                'success' => true,
                'data' => $receipt
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getSalesReport($startDate, $endDate, $branchId = null) {
        try {
            // Verify permissions
            $this->auth->requirePermission('view_reports');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get sales report
            $report = $this->saleModel->getSalesReport(
                $currentUser['business_id'],
                $startDate,
                $endDate,
                $branchId
            );

            return [
                'success' => true,
                'data' => $report
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getTopProducts($startDate, $endDate, $limit = 10) {
        try {
            // Verify permissions
            $this->auth->requirePermission('view_reports');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get top products
            $products = $this->saleModel->getTopProducts(
                $currentUser['business_id'],
                $startDate,
                $endDate,
                $limit
            );

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

    public function validateSaleData($saleData, $items) {
        $errors = [];

        // Validate sale data
        if (empty($saleData['branch_id'])) {
            $errors[] = "Branch is required";
        }
        if (empty($saleData['register_session_id'])) {
            $errors[] = "Register session is required";
        }
        if (empty($saleData['payment_method'])) {
            $errors[] = "Payment method is required";
        }

        // Validate items
        if (empty($items)) {
            $errors[] = "No items in sale";
        } else {
            foreach ($items as $item) {
                if (empty($item['product_id'])) {
                    $errors[] = "Product ID is required for all items";
                }
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    $errors[] = "Valid quantity is required for all items";
                }
                if (!isset($item['unit_price']) || $item['unit_price'] < 0) {
                    $errors[] = "Valid unit price is required for all items";
                }
            }
        }

        return $errors;
    }
}
?>

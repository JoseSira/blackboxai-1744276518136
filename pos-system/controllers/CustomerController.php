<?php
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/AuthController.php';

class CustomerController {
    private $customerModel;
    private $auth;

    public function __construct() {
        $this->customerModel = new Customer();
        $this->auth = new AuthController();
    }

    public function createCustomer($data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_customers');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();
            $data['business_id'] = $currentUser['business_id'];

            // Validate data
            $errors = $this->validateCustomerData($data);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $errors
                ];
            }

            // Create customer
            $customerId = $this->customerModel->createCustomer($data);

            return [
                'success' => true,
                'customer_id' => $customerId,
                'message' => 'Customer created successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateCustomer($customerId, $data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_customers');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify customer belongs to user's business
            $customer = $this->customerModel->findById($customerId);
            if (!$customer || $customer['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Customer not found");
            }

            // Validate data
            $errors = $this->validateCustomerData($data, true);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $errors
                ];
            }

            // Update customer
            $this->customerModel->updateCustomer($customerId, $data);

            return [
                'success' => true,
                'message' => 'Customer updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCustomers($filters = [], $page = 1) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_customers');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get customers
            $result = $this->customerModel->getCustomers(
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

    public function getCustomerDetails($customerId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_customers');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify customer belongs to user's business
            $customer = $this->customerModel->findById($customerId);
            if (!$customer || $customer['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Customer not found");
            }

            // Get customer details
            $details = $this->customerModel->getCustomerDetails($customerId);

            return [
                'success' => true,
                'data' => $details
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function searchCustomers($query) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_customers');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Search customers
            $customers = $this->customerModel->searchCustomers(
                $currentUser['business_id'],
                $query
            );

            return [
                'success' => true,
                'data' => $customers
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getTopCustomers($limit = 10) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_customers');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get top customers
            $customers = $this->customerModel->getTopCustomers(
                $currentUser['business_id'],
                $limit
            );

            return [
                'success' => true,
                'data' => $customers
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validateCustomerData($data, $isUpdate = false) {
        $errors = [];

        // First name is required
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        // Validate email if provided
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }

        // Validate phone if provided
        if (!empty($data['phone'])) {
            // Remove any non-digit characters for validation
            $phone = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phone) < 10) {
                $errors['phone'] = 'Invalid phone number';
            }
        }

        // Validate tax ID if provided
        if (!empty($data['tax_id'])) {
            if (!preg_match('/^[A-Z0-9]{10,15}$/', $data['tax_id'])) {
                $errors['tax_id'] = 'Invalid tax ID format';
            }
        }

        // Additional validations for address if provided
        if (!empty($data['address'])) {
            if (strlen($data['address']) < 5) {
                $errors['address'] = 'Address is too short';
            }
        }

        return $errors;
    }

    public function formatCustomerName($customer) {
        $name = $customer['first_name'];
        if (!empty($customer['last_name'])) {
            $name .= ' ' . $customer['last_name'];
        }
        return $name;
    }

    public function formatCustomerDetails($customer) {
        $details = [];
        
        if (!empty($customer['email'])) {
            $details[] = $customer['email'];
        }
        if (!empty($customer['phone'])) {
            $details[] = $customer['phone'];
        }
        if (!empty($customer['tax_id'])) {
            $details[] = 'Tax ID: ' . $customer['tax_id'];
        }
        
        return implode(' | ', $details);
    }
}
?>

<?php
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/AuthController.php';

class CategoryController {
    private $categoryModel;
    private $auth;

    public function __construct() {
        $this->categoryModel = new Category();
        $this->auth = new AuthController();
    }

    public function createCategory($data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();
            $data['business_id'] = $currentUser['business_id'];

            // Create category
            $categoryId = $this->categoryModel->createCategory($data);

            return [
                'success' => true,
                'category_id' => $categoryId,
                'message' => 'Category created successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateCategory($categoryId, $data) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify category belongs to user's business
            $category = $this->categoryModel->findById($categoryId);
            if (!$category || $category['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Category not found");
            }

            // Update category
            $this->categoryModel->updateCategory($categoryId, $data);

            return [
                'success' => true,
                'message' => 'Category updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCategories($includeProducts = false) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get categories
            $categories = $this->categoryModel->getCategories(
                $currentUser['business_id'],
                $includeProducts
            );

            return [
                'success' => true,
                'data' => $categories
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCategoryTree() {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Get category tree
            $tree = $this->categoryModel->getCategoryTree($currentUser['business_id']);

            return [
                'success' => true,
                'data' => $tree
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteCategory($categoryId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify category belongs to user's business
            $category = $this->categoryModel->findById($categoryId);
            if (!$category || $category['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Category not found");
            }

            // Delete category
            $this->categoryModel->deleteCategory($categoryId);

            return [
                'success' => true,
                'message' => 'Category deleted successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function moveCategory($categoryId, $newParentId = null) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify category belongs to user's business
            $category = $this->categoryModel->findById($categoryId);
            if (!$category || $category['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Category not found");
            }

            // If moving to a parent category, verify it belongs to the same business
            if ($newParentId) {
                $parent = $this->categoryModel->findById($newParentId);
                if (!$parent || $parent['business_id'] !== $currentUser['business_id']) {
                    throw new Exception("Parent category not found");
                }
            }

            // Move category
            $this->categoryModel->moveCategory($categoryId, $newParentId);

            return [
                'success' => true,
                'message' => 'Category moved successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCategoryPath($categoryId) {
        try {
            // Verify permissions
            $this->auth->requirePermission('manage_categories');

            // Get current user's business
            $currentUser = $this->auth->getCurrentUser();

            // Verify category belongs to user's business
            $category = $this->categoryModel->findById($categoryId);
            if (!$category || $category['business_id'] !== $currentUser['business_id']) {
                throw new Exception("Category not found");
            }

            // Get category path
            $path = $this->categoryModel->getCategoryPath($categoryId);

            return [
                'success' => true,
                'data' => $path
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateCategoryAccess($categoryId) {
        // Get current user's business
        $currentUser = $this->auth->getCurrentUser();

        // Verify category belongs to user's business
        $category = $this->categoryModel->findById($categoryId);
        return $category && $category['business_id'] === $currentUser['business_id'];
    }
}
?>

<?php
require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel {
    protected $table = 'categories';

    public function __construct() {
        parent::__construct();
    }

    public function createCategory($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['business_id'])) {
                throw new Exception("Name and business_id are required");
            }

            // Create category
            return $this->create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to create category: " . $e->getMessage());
        }
    }

    public function updateCategory($categoryId, $data) {
        try {
            // Validate category exists
            $category = $this->findById($categoryId);
            if (!$category) {
                throw new Exception("Category not found");
            }

            // Update category
            return $this->update($categoryId, $data);
        } catch (Exception $e) {
            throw new Exception("Failed to update category: " . $e->getMessage());
        }
    }

    public function getCategoryTree($businessId) {
        try {
            // Get all categories for the business
            $sql = "WITH RECURSIVE category_tree AS (
                    -- Base case: get parent categories
                    SELECT id, name, parent_id, 0 as level, CAST(name as CHAR(1000)) as path
                    FROM categories
                    WHERE business_id = :business_id AND parent_id IS NULL
                    
                    UNION ALL
                    
                    -- Recursive case: get child categories
                    SELECT c.id, c.name, c.parent_id, ct.level + 1,
                           CONCAT(ct.path, ' > ', c.name)
                    FROM categories c
                    INNER JOIN category_tree ct ON c.parent_id = ct.id
                    WHERE c.business_id = :business_id
                )
                SELECT * FROM category_tree ORDER BY path";

            $stmt = $this->query($sql, ['business_id' => $businessId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get category tree: " . $e->getMessage());
        }
    }

    public function getCategories($businessId, $includeProducts = false) {
        try {
            if (!$includeProducts) {
                return $this->findAll(['business_id' => $businessId], 'name ASC');
            }

            // Get categories with product counts
            $sql = "SELECT c.*, COUNT(p.id) as product_count 
                    FROM categories c 
                    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                    WHERE c.business_id = :business_id 
                    GROUP BY c.id 
                    ORDER BY c.name ASC";

            $stmt = $this->query($sql, ['business_id' => $businessId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to get categories: " . $e->getMessage());
        }
    }

    public function deleteCategory($categoryId) {
        try {
            // Check if category has products
            $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = :category_id";
            $stmt = $this->query($sql, ['category_id' => $categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception("Cannot delete category with associated products");
            }

            // Check if category has subcategories
            $sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = :category_id";
            $stmt = $this->query($sql, ['category_id' => $categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception("Cannot delete category with subcategories");
            }

            // Delete category
            return $this->delete($categoryId);
        } catch (Exception $e) {
            throw new Exception("Failed to delete category: " . $e->getMessage());
        }
    }

    public function moveCategory($categoryId, $newParentId = null) {
        try {
            // Validate category exists
            $category = $this->findById($categoryId);
            if (!$category) {
                throw new Exception("Category not found");
            }

            // If moving to a parent category, validate it exists and prevent circular references
            if ($newParentId) {
                $parent = $this->findById($newParentId);
                if (!$parent) {
                    throw new Exception("Parent category not found");
                }

                // Check if new parent is not a descendant of the category being moved
                $sql = "WITH RECURSIVE descendants AS (
                        SELECT id FROM categories WHERE id = :category_id
                        UNION ALL
                        SELECT c.id FROM categories c
                        INNER JOIN descendants d ON c.parent_id = d.id
                    )
                    SELECT COUNT(*) as count FROM descendants WHERE id = :new_parent_id";

                $stmt = $this->query($sql, [
                    'category_id' => $categoryId,
                    'new_parent_id' => $newParentId
                ]);
                
                if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                    throw new Exception("Cannot move category to one of its descendants");
                }
            }

            // Update category's parent
            return $this->update($categoryId, ['parent_id' => $newParentId]);
        } catch (Exception $e) {
            throw new Exception("Failed to move category: " . $e->getMessage());
        }
    }

    public function getCategoryPath($categoryId) {
        try {
            $sql = "WITH RECURSIVE category_path AS (
                    -- Base case: start with the target category
                    SELECT id, name, parent_id, CAST(name as CHAR(1000)) as path
                    FROM categories
                    WHERE id = :category_id
                    
                    UNION ALL
                    
                    -- Recursive case: get parent categories
                    SELECT c.id, c.name, c.parent_id,
                           CONCAT(c.name, ' > ', cp.path)
                    FROM categories c
                    INNER JOIN category_path cp ON c.id = cp.parent_id
                )
                SELECT path FROM category_path WHERE parent_id IS NULL";

            $stmt = $this->query($sql, ['category_id' => $categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['path'] : null;
        } catch (Exception $e) {
            throw new Exception("Failed to get category path: " . $e->getMessage());
        }
    }
}
?>

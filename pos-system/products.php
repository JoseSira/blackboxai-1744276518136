<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProductController.php';

$auth = new AuthController();
$auth->requirePermission('manage_products');

$productController = new ProductController();

// Handle search and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [
    'search' => $_GET['search'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'status' => $_GET['status'] ?? 'active'
];

// Get products
$result = $productController->getProducts($filters, $page);
$products = $result['success'] ? $result['data'] : ['products' => [], 'total' => 0, 'pages' => 1];

// Start output buffering
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Productos
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button type="button" onclick="openAddProductModal()"
                class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-plus mr-2"></i>
                Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-4">
            <form id="filterForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search']) ?>"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        placeholder="Nombre, SKU o código de barras">
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select name="category_id" id="category"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Todas las categorías</option>
                        <!-- Categories will be loaded dynamically -->
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" id="status"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-search mr-2"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Producto
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Categoría
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Precio
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stock
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($products['products'])): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No se encontraron productos
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products['products'] as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($product['name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                SKU: <?= htmlspecialchars($product['sku']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        $<?= number_format($product['price'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $stockClass = 'text-green-800 bg-green-100';
                                    if ($product['current_stock'] <= $product['min_stock']) {
                                        $stockClass = 'text-red-800 bg-red-100';
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $stockClass ?>">
                                        <?= $product['current_stock'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $product['status'] === 'active' ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100' ?>">
                                        <?= $product['status'] === 'active' ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="openEditProductModal(<?= $product['id'] ?>)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="openStockModal(<?= $product['id'] ?>)"
                                        class="text-green-600 hover:text-green-900 mr-2">
                                        <i class="fas fa-boxes"></i>
                                    </button>
                                    <button onclick="confirmDeleteProduct(<?= $product['id'] ?>)"
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($products['pages'] > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Anterior
            </a>
            <?php endif; ?>
            <?php if ($page < $products['pages']): ?>
            <a href="?page=<?= $page + 1 ?>&<?= http_build_query($filters) ?>"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Siguiente
            </a>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando
                    <span class="font-medium"><?= ($page - 1) * 10 + 1 ?></span>
                    a
                    <span class="font-medium"><?= min($page * 10, $products['total']) ?></span>
                    de
                    <span class="font-medium"><?= $products['total'] ?></span>
                    resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $products['pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&<?= http_build_query($filters) ?>"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?= $i === $page ? 'bg-gray-100' : '' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Product Modal Template -->
<div id="productModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content here -->
</div>

<!-- Stock Modal Template -->
<div id="stockModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content here -->
</div>

<script>
// JavaScript functions for handling modals and CRUD operations
function openAddProductModal() {
    // Implementation for opening add product modal
}

function openEditProductModal(productId) {
    // Implementation for opening edit product modal
}

function openStockModal(productId) {
    // Implementation for opening stock management modal
}

function confirmDeleteProduct(productId) {
    if (confirm('¿Está seguro de que desea eliminar este producto?')) {
        // Implementation for deleting product
    }
}

// Form submission handling
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    this.submit();
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>

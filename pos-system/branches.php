<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BranchController.php';

$auth = new AuthController();
$auth->requirePermission('manage_branches');

$branchController = new BranchController();

// Get branches list
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? null
];

$result = $branchController->getBranches($filters, $page);
$branches = $result['success'] ? $result['data'] : ['branches' => [], 'total' => 0, 'pages' => 1];

// Start output buffering
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Sucursales
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button type="button" onclick="openAddBranchModal()"
                class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-plus mr-2"></i>
                Nueva Sucursal
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-4">
            <form id="filterForm" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Buscar</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search']) ?>"
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                            placeholder="Nombre, dirección o teléfono">
                    </div>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" id="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Todos</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Activa</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactiva</option>
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

    <!-- Branches Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php if (empty($branches['branches'])): ?>
        <div class="col-span-full text-center py-8 text-gray-500">
            No se encontraron sucursales
        </div>
        <?php else: ?>
        <?php foreach ($branches['branches'] as $branch): ?>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        <?= htmlspecialchars($branch['name']) ?>
                    </h3>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $branch['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $branch['status'] === 'active' ? 'Activa' : 'Inactiva' ?>
                    </span>
                </div>

                <div class="space-y-2">
                    <?php if (!empty($branch['address'])): ?>
                    <div class="flex items-start">
                        <i class="fas fa-map-marker-alt text-gray-400 mt-1 mr-2"></i>
                        <span class="text-sm text-gray-500"><?= htmlspecialchars($branch['address']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($branch['phone'])): ?>
                    <div class="flex items-center">
                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                        <span class="text-sm text-gray-500"><?= htmlspecialchars($branch['phone']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($branch['email'])): ?>
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                        <span class="text-sm text-gray-500"><?= htmlspecialchars($branch['email']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <span class="block text-2xl font-bold text-gray-900"><?= $branch['total_users'] ?></span>
                            <span class="block text-sm font-medium text-gray-500">Usuarios</span>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-gray-900"><?= $branch['open_registers'] ?></span>
                            <span class="block text-sm font-medium text-gray-500">Cajas Abiertas</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <span class="block text-2xl font-bold text-gray-900"><?= number_format($branch['total_sales']) ?></span>
                            <span class="block text-sm font-medium text-gray-500">Ventas</span>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-gray-900">$<?= number_format($branch['total_revenue'], 2) ?></span>
                            <span class="block text-sm font-medium text-gray-500">Ingresos</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-t border-gray-200 pt-4 flex justify-end space-x-2">
                    <button onclick="viewBranchDetails(<?= $branch['id'] ?>)"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-eye mr-1.5"></i>
                        Ver
                    </button>
                    <button onclick="openEditBranchModal(<?= $branch['id'] ?>)"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-edit mr-1.5"></i>
                        Editar
                    </button>
                    <?php if ($branch['status'] === 'active'): ?>
                    <button onclick="confirmDeactivateBranch(<?= $branch['id'] ?>)"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-ban mr-1.5"></i>
                        Desactivar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($branches['pages'] > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Anterior
            </a>
            <?php endif; ?>
            <?php if ($page < $branches['pages']): ?>
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
                    <span class="font-medium"><?= min($page * 10, $branches['total']) ?></span>
                    de
                    <span class="font-medium"><?= $branches['total'] ?></span>
                    resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $branches['pages']; $i++): ?>
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

<!-- Branch Modal Template -->
<div id="branchModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Load Scripts -->
<script src="/assets/js/branches.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>

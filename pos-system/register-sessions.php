<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/RegisterSessionController.php';

$auth = new AuthController();
$auth->requirePermission('manage_register');

$registerSessionController = new RegisterSessionController();

// Get sessions list
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [
    'branch_id' => $_GET['branch_id'] ?? null,
    'status' => $_GET['status'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

$result = $registerSessionController->getSessions($filters, $page);
$sessions = $result['success'] ? $result['data'] : ['sessions' => [], 'total' => 0, 'pages' => 1];

// Start output buffering
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Sesiones de Caja
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button type="button" onclick="openRegisterModal()"
                class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-cash-register mr-2"></i>
                Abrir Caja
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-4">
            <form id="filterForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="branch_id" class="block text-sm font-medium text-gray-700">Sucursal</label>
                    <select name="branch_id" id="branch_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Todas las sucursales</option>
                        <!-- Branches will be loaded dynamically -->
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" id="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Todos</option>
                        <option value="open" <?= $filters['status'] === 'open' ? 'selected' : '' ?>>Abierta</option>
                        <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Cerrada</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Fecha Desde</label>
                    <input type="date" name="date_from" id="date_from" value="<?= $filters['date_from'] ?? '' ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Fecha Hasta</label>
                    <input type="date" name="date_to" id="date_to" value="<?= $filters['date_to'] ?? '' ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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

    <!-- Sessions Table -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sucursal / Cajero
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha / Hora
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ventas
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Efectivo
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($sessions['sessions'])): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No se encontraron sesiones
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($sessions['sessions'] as $session): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($session['branch_name']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($session['user_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($session['opening_time'])) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= date('H:i', strtotime($session['opening_time'])) ?>
                                        <?php if ($session['closing_time']): ?>
                                            - <?= date('H:i', strtotime($session['closing_time'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $session['status'] === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $session['status'] === 'open' ? 'Abierta' : 'Cerrada' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= number_format($session['total_sales']) ?> ventas
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        $<?= number_format($session['total_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Inicial: $<?= number_format($session['initial_cash'], 2) ?>
                                    </div>
                                    <?php if ($session['status'] === 'closed'): ?>
                                    <div class="text-sm <?= $session['cash_difference'] < 0 ? 'text-red-600' : 'text-green-600' ?>">
                                        Diferencia: $<?= number_format($session['cash_difference'], 2) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($session['status'] === 'open'): ?>
                                    <button onclick="openCloseSessionModal(<?= $session['id'] ?>)"
                                        class="text-red-600 hover:text-red-900 mr-3">
                                        <i class="fas fa-cash-register"></i> Cerrar
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="viewSessionDetails(<?= $session['id'] ?>)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-eye"></i>
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
    <?php if ($sessions['pages'] > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Anterior
            </a>
            <?php endif; ?>
            <?php if ($page < $sessions['pages']): ?>
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
                    <span class="font-medium"><?= min($page * 10, $sessions['total']) ?></span>
                    de
                    <span class="font-medium"><?= $sessions['total'] ?></span>
                    resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $sessions['pages']; $i++): ?>
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

<!-- Register Modal Template -->
<div id="registerModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Session Details Modal Template -->
<div id="sessionDetailsModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Load Scripts -->
<script src="/assets/js/register-sessions.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>

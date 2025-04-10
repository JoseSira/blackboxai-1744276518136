<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

// Get current user data
$currentUser = $auth->getCurrentUser();

// Start output buffering
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Welcome Section -->
    <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">
            Bienvenido, <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            <?= htmlspecialchars($currentUser['business_name']) ?> - 
            <?= htmlspecialchars($currentUser['branch_name']) ?>
        </p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <!-- Sales Today -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cash-register text-2xl text-indigo-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Ventas Hoy
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    $0.00
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products in Stock -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-box text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Productos en Stock
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    0
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Alertas de Stock
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    0
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Clientes
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    0
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Acciones Rápidas</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php if ($auth->hasPermission('manage_sales')): ?>
            <a href="/pos.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-cash-register text-2xl text-indigo-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Nueva Venta</span>
            </a>
            <?php endif; ?>

            <?php if ($auth->hasPermission('manage_products')): ?>
            <a href="/products.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-box text-2xl text-green-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Gestionar Productos</span>
            </a>
            <?php endif; ?>

            <?php if ($auth->hasPermission('manage_customers')): ?>
            <a href="/customers.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-users text-2xl text-blue-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Gestionar Clientes</span>
            </a>
            <?php endif; ?>

            <?php if ($auth->hasPermission('view_reports')): ?>
            <a href="/reports.php" class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-chart-bar text-2xl text-purple-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Ver Reportes</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Actividad Reciente</h2>
        <div class="border-t border-gray-200">
            <div class="py-4 text-center text-gray-500">
                No hay actividad reciente para mostrar
            </div>
        </div>
    </div>
</div>

<!-- Initialize Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any dashboard-specific JavaScript here
    // For example, you could initialize charts, set up real-time updates, etc.
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>

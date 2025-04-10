<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/controllers/CategoryController.php';

$auth = new AuthController();
$auth->requirePermission('manage_sales');

$currentUser = $auth->getCurrentUser();

// Start output buffering
ob_start();
?>

<div class="h-screen flex flex-col">
    <!-- POS Header -->
    <div class="bg-white shadow">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Punto de Venta</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 mr-4">
                        Caja: <span id="register-number" class="font-semibold">01</span>
                    </span>
                    <span class="text-sm text-gray-500">
                        Cajero: <span class="font-semibold"><?= htmlspecialchars($currentUser['username']) ?></span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Side - Products -->
        <div class="w-2/3 flex flex-col bg-gray-100">
            <!-- Search and Categories -->
            <div class="p-4 bg-white shadow">
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" id="product-search" 
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Buscar productos (nombre, código o SKU)">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="openBarcodeScanner()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-barcode mr-2"></i>
                        Escanear
                    </button>
                </div>
                <div class="mt-4 flex space-x-2 overflow-x-auto pb-2" id="categories-container">
                    <!-- Categories will be loaded dynamically -->
                </div>
            </div>

            <!-- Products Grid -->
            <div class="flex-1 p-4 overflow-auto" id="products-container">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <!-- Products will be loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Right Side - Cart -->
        <div class="w-1/3 flex flex-col bg-white border-l border-gray-200">
            <!-- Customer Selection -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <select id="customer-select" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Cliente General</option>
                            <!-- Customers will be loaded dynamically -->
                        </select>
                    </div>
                    <button type="button" onclick="openCustomerModal()"
                        class="ml-2 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-auto p-4">
                <div id="cart-items" class="space-y-4">
                    <!-- Cart items will be added dynamically -->
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="border-t border-gray-200 p-4 space-y-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-medium" id="cart-subtotal">$0.00</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">IVA</span>
                    <span class="font-medium" id="cart-tax">$0.00</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Descuento</span>
                    <div class="flex items-center">
                        <input type="number" id="discount-amount" min="0" step="0.01"
                            class="w-20 px-2 py-1 border border-gray-300 rounded-md text-right"
                            onchange="updateCartTotals()">
                    </div>
                </div>
                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span id="cart-total">$0.00</span>
                </div>

                <!-- Payment Buttons -->
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <button type="button" onclick="openPaymentModal('cash')"
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-money-bill-wave mr-2"></i>
                        Efectivo
                    </button>
                    <button type="button" onclick="openPaymentModal('card')"
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-credit-card mr-2"></i>
                        Tarjeta
                    </button>
                </div>

                <button type="button" onclick="cancelSale()"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal Template -->
<div id="payment-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Customer Modal Template -->
<div id="customer-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Receipt Modal Template -->
<div id="receipt-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Barcode Scanner Modal Template -->
<div id="barcode-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal content will be injected dynamically -->
</div>

<!-- Load Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/pos.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>

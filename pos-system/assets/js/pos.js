// POS System JavaScript

// Global Variables
let cart = [];
let products = [];
let categories = [];
let customers = [];
let currentCategory = null;
let lastScannedBarcode = '';
let barcodeBuffer = '';
let barcodeTimeout = null;

// Initialize POS
document.addEventListener('DOMContentLoaded', function() {
    initializePOS();
    setupEventListeners();
});

async function initializePOS() {
    try {
        await Promise.all([
            loadCategories(),
            loadProducts(),
            loadCustomers()
        ]);
        updateDisplay();
    } catch (error) {
        console.error('Error initializing POS:', error);
        showError('Error al inicializar el sistema');
    }
}

function setupEventListeners() {
    // Search input
    const searchInput = document.getElementById('product-search');
    searchInput.addEventListener('input', (e) => {
        filterProducts(e.target.value);
    });

    // Barcode scanner support
    document.addEventListener('keypress', handleBarcodeScanner);

    // Customer select
    const customerSelect = document.getElementById('customer-select');
    customerSelect.addEventListener('change', updateCartTotals);

    // Discount input
    const discountInput = document.getElementById('discount-amount');
    discountInput.addEventListener('input', updateCartTotals);
}

// Products Management
async function loadProducts(categoryId = null) {
    try {
        const url = `/api/products?${categoryId ? 'category_id=' + categoryId : ''}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            products = result.data.products;
            displayProducts();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showError('Error al cargar productos');
    }
}

function displayProducts() {
    const container = document.getElementById('products-container');
    container.innerHTML = '';

    products.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-lg transition-shadow';
        productCard.onclick = () => addToCart(product);
        
        const stockClass = product.current_stock <= product.min_stock ? 'text-red-600' : 'text-green-600';
        
        productCard.innerHTML = `
            <div class="text-sm font-medium text-gray-900">${product.name}</div>
            <div class="text-xs text-gray-500">SKU: ${product.sku}</div>
            <div class="mt-2 flex justify-between items-center">
                <span class="text-lg font-bold">$${product.price.toFixed(2)}</span>
                <span class="text-sm ${stockClass}">Stock: ${product.current_stock}</span>
            </div>
        `;
        
        container.appendChild(productCard);
    });
}

function filterProducts(query) {
    const searchQuery = query.toLowerCase();
    const filteredProducts = products.filter(product => 
        product.name.toLowerCase().includes(searchQuery) ||
        product.sku.toLowerCase().includes(searchQuery) ||
        (product.barcode && product.barcode.toLowerCase().includes(searchQuery))
    );
    
    displayFilteredProducts(filteredProducts);
}

function displayFilteredProducts(filteredProducts) {
    const container = document.getElementById('products-container');
    container.innerHTML = '';

    if (filteredProducts.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-8 text-gray-500">
                No se encontraron productos
            </div>
        `;
        return;
    }

    filteredProducts.forEach(product => {
        // Reuse the same product card structure from displayProducts
        const productCard = document.createElement('div');
        productCard.className = 'bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-lg transition-shadow';
        productCard.onclick = () => addToCart(product);
        
        const stockClass = product.current_stock <= product.min_stock ? 'text-red-600' : 'text-green-600';
        
        productCard.innerHTML = `
            <div class="text-sm font-medium text-gray-900">${product.name}</div>
            <div class="text-xs text-gray-500">SKU: ${product.sku}</div>
            <div class="mt-2 flex justify-between items-center">
                <span class="text-lg font-bold">$${product.price.toFixed(2)}</span>
                <span class="text-sm ${stockClass}">Stock: ${product.current_stock}</span>
            </div>
        `;
        
        container.appendChild(productCard);
    });
}

// Categories Management
async function loadCategories() {
    try {
        const response = await fetch('/api/categories');
        const result = await response.json();
        
        if (result.success) {
            categories = result.data;
            displayCategories();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        showError('Error al cargar categorías');
    }
}

function displayCategories() {
    const container = document.getElementById('categories-container');
    container.innerHTML = `
        <button onclick="selectCategory(null)" 
            class="px-4 py-2 rounded-full text-sm font-medium ${!currentCategory ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}">
            Todos
        </button>
    `;

    categories.forEach(category => {
        const button = document.createElement('button');
        button.className = `px-4 py-2 rounded-full text-sm font-medium ${
            currentCategory === category.id ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
        }`;
        button.onclick = () => selectCategory(category.id);
        button.textContent = category.name;
        container.appendChild(button);
    });
}

function selectCategory(categoryId) {
    currentCategory = categoryId;
    displayCategories();
    loadProducts(categoryId);
}

// Cart Management
function addToCart(product, quantity = 1) {
    // Check if product has sufficient stock
    if (product.unit_type !== 'combo' && product.current_stock < quantity) {
        showError('Stock insuficiente');
        return;
    }

    // Find existing cart item
    const existingItem = cart.find(item => item.product_id === product.id);
    
    if (existingItem) {
        // Update quantity if exists
        if (product.unit_type !== 'combo' && product.current_stock < (existingItem.quantity + quantity)) {
            showError('Stock insuficiente');
            return;
        }
        existingItem.quantity += quantity;
    } else {
        // Add new item
        cart.push({
            product_id: product.id,
            product_name: product.name,
            quantity: quantity,
            unit_price: product.price,
            tax_rate: product.tax_rate,
            unit_type: product.unit_type
        });
    }

    updateCartDisplay();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function updateCartDisplay() {
    const container = document.getElementById('cart-items');
    container.innerHTML = '';

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                El carrito está vacío
            </div>
        `;
        return;
    }

    cart.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.className = 'flex items-center justify-between p-2 border-b border-gray-200';
        
        itemElement.innerHTML = `
            <div class="flex-1">
                <div class="font-medium">${item.product_name}</div>
                <div class="text-sm text-gray-500">
                    $${item.unit_price.toFixed(2)} x ${item.quantity} = $${(item.unit_price * item.quantity).toFixed(2)}
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="updateItemQuantity(${index}, -1)"
                    class="p-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-minus"></i>
                </button>
                <span class="w-8 text-center">${item.quantity}</span>
                <button onclick="updateItemQuantity(${index}, 1)"
                    class="p-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-plus"></i>
                </button>
                <button onclick="removeFromCart(${index})"
                    class="p-1 text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        container.appendChild(itemElement);
    });

    updateCartTotals();
}

function updateItemQuantity(index, change) {
    const item = cart[index];
    const newQuantity = item.quantity + change;

    if (newQuantity <= 0) {
        removeFromCart(index);
        return;
    }

    // Check stock
    const product = products.find(p => p.id === item.product_id);
    if (product.unit_type !== 'combo' && product.current_stock < newQuantity) {
        showError('Stock insuficiente');
        return;
    }

    item.quantity = newQuantity;
    updateCartDisplay();
}

function updateCartTotals() {
    let subtotal = 0;
    let taxAmount = 0;

    cart.forEach(item => {
        const itemTotal = item.unit_price * item.quantity;
        subtotal += itemTotal;
        taxAmount += itemTotal * (item.tax_rate / 100);
    });

    const discountAmount = parseFloat(document.getElementById('discount-amount').value) || 0;
    const total = subtotal + taxAmount - discountAmount;

    document.getElementById('cart-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('cart-tax').textContent = `$${taxAmount.toFixed(2)}`;
    document.getElementById('cart-total').textContent = `$${total.toFixed(2)}`;
}

// Payment Processing
function openPaymentModal(method) {
    if (cart.length === 0) {
        showError('El carrito está vacío');
        return;
    }

    const total = parseFloat(document.getElementById('cart-total').textContent.replace('$', ''));
    const modal = document.getElementById('payment-modal');
    
    modal.innerHTML = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                            Procesar Pago - ${method === 'cash' ? 'Efectivo' : 'Tarjeta'}
                        </h3>
                        <div class="space-y-4">
                            <div class="text-2xl font-bold text-center">
                                Total a Pagar: $${total.toFixed(2)}
                            </div>
                            ${method === 'cash' ? `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Monto Recibido</label>
                                    <input type="number" id="amount-received" step="0.01" min="${total}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        onchange="calculateChange(${total})">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cambio</label>
                                    <div id="change-amount" class="text-xl font-bold text-green-600">$0.00</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" onclick="processSale('${method}')"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            Completar Venta
                        </button>
                        <button type="button" onclick="closePaymentModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function calculateChange(total) {
    const received = parseFloat(document.getElementById('amount-received').value) || 0;
    const change = received - total;
    document.getElementById('change-amount').textContent = `$${Math.max(0, change).toFixed(2)}`;
}

async function processSale(paymentMethod) {
    try {
        const saleData = {
            customer_id: document.getElementById('customer-select').value || null,
            payment_method: paymentMethod,
            discount_amount: parseFloat(document.getElementById('discount-amount').value) || 0,
            items: cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price
            }))
        };

        const response = await fetch('/api/sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(saleData),
        });

        const result = await response.json();
        
        if (result.success) {
            await printReceipt(result.sale_id);
            resetSale();
            showSuccess('Venta completada exitosamente');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error processing sale:', error);
        showError('Error al procesar la venta');
    }
}

function closePaymentModal() {
    document.getElementById('payment-modal').classList.add('hidden');
}

function resetSale() {
    cart = [];
    document.getElementById('discount-amount').value = '';
    document.getElementById('customer-select').value = '';
    closePaymentModal();
    updateCartDisplay();
}

// Receipt Management
async function printReceipt(saleId) {
    try {
        const response = await fetch(`/api/sales/receipt?id=${saleId}`);
        const result = await response.json();
        
        if (result.success) {
            displayReceipt(result.data);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error printing receipt:', error);
        showError('Error al imprimir recibo');
    }
}

function displayReceipt(receipt) {
    const modal = document.getElementById('receipt-modal');
    
    modal.innerHTML = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div id="receipt-content" class="space-y-4">
                            <!-- Receipt content -->
                            <div class="text-center">
                                <h2 class="text-xl font-bold">${receipt.business.name}</h2>
                                <p>${receipt.business.branch}</p>
                                <p>${receipt.business.address}</p>
                                <p>Tel: ${receipt.business.phone}</p>
                            </div>
                            
                            <div class="border-t border-b border-gray-200 py-2">
                                <p>Fecha: ${new Date(receipt.sale.date).toLocaleString()}</p>
                                <p>Ticket #: ${receipt.sale.id}</p>
                                <p>Cajero: ${receipt.sale.cashier}</p>
                                <p>Cliente: ${receipt.sale.customer}</p>
                            </div>
                            
                            <div class="space-y-2">
                                ${receipt.sale.items.map(item => `
                                    <div class="flex justify-between">
                                        <div>
                                            <div>${item.product_name}</div>
                                            <div class="text-sm text-gray-500">
                                                ${item.quantity} x $${item.unit_price.toFixed(2)}
                                            </div>
                                        </div>
                                        <div class="font-medium">
                                            $${(item.quantity * item.unit_price).toFixed(2)}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="border-t border-gray-200 pt-2 space-y-1">
                                <div class="flex justify-between">
                                    <span>Subtotal</span>
                                    <span>$${receipt.sale.subtotal.toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>IVA</span>
                                    <span>$${receipt.sale.tax_amount.toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Descuento</span>
                                    <span>$${receipt.sale.discount_amount.toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between font-bold text-lg">
                                    <span>Total</span>
                                    <span>$${receipt.sale.total_amount.toFixed(2)}</span>
                                </div>
                            </div>
                            
                            <div class="text-center text-sm">
                                <p>¡Gracias por su compra!</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" onclick="printReceiptContent()"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            Imprimir
                        </button>
                        <button type="button" onclick="closeReceiptModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function printReceiptContent() {
    const receiptContent = document.getElementById('receipt-content').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Recibo</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .print-only { display: block; }
                </style>
            </head>
            <body>
                ${receiptContent}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        };
                    };
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

function closeReceiptModal() {
    document.getElementById('receipt-modal').classList.add('hidden');
}

// Barcode Scanner Support
function handleBarcodeScanner(e) {
    // Clear timeout if exists
    if (barcodeTimeout) {
        clearTimeout(barcodeTimeout);
    }

    // Add character to buffer
    barcodeBuffer += e.key;

    // Set timeout to process buffer
    barcodeTimeout = setTimeout(() => {
        if (barcodeBuffer.length > 3) { // Minimum barcode length
            processBarcode(barcodeBuffer);
        }
        barcodeBuffer = '';
    }, 100);
}

function processBarcode(barcode) {
    const product = products.find(p => p.barcode === barcode);
    if (product) {
        addToCart(product);
        lastScannedBarcode = barcode;
    }
}

// Utility Functions
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message
    });
}

function cancelSale() {
    if (cart.length === 0) {
        return;
    }

    Swal.fire({
        title: '¿Cancelar venta?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, mantener'
    }).then((result) => {
        if (result.isConfirmed) {
            resetSale();
            showSuccess('Venta cancelada');
        }
    });
}

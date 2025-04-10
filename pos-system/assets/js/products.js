function openAddProductModal() {
    currentProductId = null;
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="productForm" onsubmit="handleProductSubmit(event)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Agregar Nuevo Producto</h3>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                    <input type="text" name="name" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                                    <select name="category_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Seleccionar categoría</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Precio</label>
                                        <input type="number" name="price" step="0.01" required min="0"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Costo</label>
                                        <input type="number" name="cost" step="0.01" min="0"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo de Unidad</label>
                                    <select name="unit_type" onchange="toggleComboProducts(this.value)"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="unit">Unidad</option>
                                        <option value="weight">Peso</option>
                                        <option value="combo">Combo</option>
                                    </select>
                                </div>

                                <div id="comboProductsSection" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700">Productos del Combo</label>
                                    <div id="comboProducts" class="space-y-2">
                                        <!-- Combo products will be added here -->
                                    </div>
                                    <button type="button" onclick="addComboProduct()"
                                        class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-plus mr-2"></i> Agregar Producto
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Stock Mínimo</label>
                                        <input type="number" name="min_stock" min="0"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Stock Inicial</label>
                                        <input type="number" name="current_stock" min="0"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Código de Barras</label>
                                    <input type="text" name="barcode"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar
                            </button>
                            <button type="button" onclick="closeModal('productModal')"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('productModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
    loadCategories();
}

function openEditProductModal(productId) {
    currentProductId = productId;
    
    // Fetch product data
    fetch(`/api/products/${productId}`)
        .then(response => response.json())
        .then(product => {
            openAddProductModal(); // Reuse the add modal structure
            const form = document.getElementById('productForm');
            
            // Fill form with product data
            form.name.value = product.name;
            form.category_id.value = product.category_id;
            form.price.value = product.price;
            form.cost.value = product.cost;
            form.unit_type.value = product.unit_type;
            form.min_stock.value = product.min_stock;
            form.current_stock.value = product.current_stock;
            form.barcode.value = product.barcode;

            // Handle combo products if applicable
            if (product.unit_type === 'combo') {
                toggleComboProducts('combo');
                product.combo_products.forEach(combo => {
                    addComboProduct(combo.product_id, combo.quantity);
                });
            }
        });
}

function openStockModal(productId) {
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="stockForm" onsubmit="handleStockSubmit(event)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Ajustar Stock</h3>
                            
                            <input type="hidden" name="product_id" value="${productId}">
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo de Movimiento</label>
                                    <select name="movement_type" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="adjustment">Ajuste</option>
                                        <option value="purchase">Compra</option>
                                        <option value="transfer">Transferencia</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                                    <input type="number" name="quantity" required step="0.01"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notas</label>
                                    <textarea name="notes" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar
                            </button>
                            <button type="button" onclick="closeModal('stockModal')"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('stockModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function toggleComboProducts(unitType) {
    const section = document.getElementById('comboProductsSection');
    section.classList.toggle('hidden', unitType !== 'combo');
}

function addComboProduct(productId = '', quantity = '') {
    const container = document.getElementById('comboProducts');
    const index = container.children.length;
    
    const productRow = document.createElement('div');
    productRow.className = 'flex items-center space-x-2';
    productRow.innerHTML = `
        <select name="combo_products[${index}][product_id]" required
            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">Seleccionar producto</option>
        </select>
        <input type="number" name="combo_products[${index}][quantity]" value="${quantity}" required min="1" step="1"
            class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="Cantidad">
        <button type="button" onclick="this.parentElement.remove()"
            class="text-red-600 hover:text-red-900">
            <i class="fas fa-trash"></i>
        </button>
    `;

    container.appendChild(productRow);
    loadProducts(productRow.querySelector('select'), productId);
}

async function loadCategories() {
    try {
        const response = await fetch('/api/categories');
        const categories = await response.json();
        
        const select = document.querySelector('select[name="category_id"]');
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

async function loadProducts(select, selectedId = '') {
    try {
        const response = await fetch('/api/products');
        const products = await response.json();
        
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = product.name;
            if (product.id === selectedId) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

async function handleProductSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const url = currentProductId ? `/api/products/${currentProductId}` : '/api/products';
        const method = currentProductId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeModal('productModal');
            window.location.reload();
        } else {
            alert(result.message || 'Error al guardar el producto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar el producto');
    }
}

async function handleStockSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/products/stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeModal('stockModal');
            window.location.reload();
        } else {
            alert(result.message || 'Error al actualizar el stock');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al actualizar el stock');
    }
}

async function confirmDeleteProduct(productId) {
    if (!confirm('¿Está seguro de que desea eliminar este producto?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/products/${productId}`, {
            method: 'DELETE',
        });

        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error al eliminar el producto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar el producto');
    }
}

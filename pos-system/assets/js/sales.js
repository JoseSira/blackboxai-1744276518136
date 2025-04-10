// Función para abrir el modal de realizar venta
function openSaleModal() {
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="saleForm" onsubmit="handleSaleSubmit(event)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Realizar Venta</h3>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cliente</label>
                                    <input type="text" name="customer" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nombre del cliente">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Producto</label>
                                    <select name="product" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Seleccione un producto</option>
                                        <!-- Los productos se cargarán aquí dinámicamente -->
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                                    <input type="number" name="quantity" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Cantidad">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Método de Pago</label>
                                    <select name="paymentMethod" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="cash">Efectivo</option>
                                        <option value="card">Tarjeta</option>
                                        <option value="mobile">Pago Móvil</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Realizar Venta
                            </button>
                            <button type="button" onclick="closeModal('saleModal')" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('saleModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
    loadProductsInSale();
}

// Función para cargar productos en el modal de venta
async function loadProductsInSale() {
    try {
        const response = await fetch('/api/products');
        const products = await response.json();
        
        const select = document.querySelector('select[name="product"]');
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = product.name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Manejar el envío del formulario de venta
async function handleSaleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeModal('saleModal');
            window.location.reload();
        } else {
            alert(result.message || 'Error al realizar la venta');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al realizar la venta');
    }
}

// Función para cerrar el modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

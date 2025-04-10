// Customer Management JavaScript

let currentCustomerId = null;

function openAddCustomerModal() {
    currentCustomerId = null;
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="customerForm" onsubmit="handleCustomerSubmit(event)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                Nuevo Cliente
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                                    <input type="text" name="first_name" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Apellidos</label>
                                    <input type="text" name="last_name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                                <input type="tel" name="phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">RFC</label>
                                <input type="text" name="tax_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                                <textarea name="address" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar
                            </button>
                            <button type="button" onclick="closeCustomerModal()"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('customerModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
}

function openEditCustomerModal(customerId) {
    currentCustomerId = customerId;
    
    // Fetch customer data
    fetch(`/api/customers/${customerId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const customer = result.data.customer;
                openAddCustomerModal(); // Reuse the add modal structure
                
                // Update title
                const title = document.querySelector('#customerModal h3');
                title.textContent = 'Editar Cliente';

                // Fill form with customer data
                const form = document.getElementById('customerForm');
                form.first_name.value = customer.first_name;
                form.last_name.value = customer.last_name || '';
                form.email.value = customer.email || '';
                form.phone.value = customer.phone || '';
                form.tax_id.value = customer.tax_id || '';
                form.address.value = customer.address || '';
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar los datos del cliente');
        });
}

function openViewCustomerModal(customerId) {
    // Fetch customer details
    fetch(`/api/customers/${customerId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const customer = result.data.customer;
                const stats = result.data.stats;
                const purchaseHistory = result.data.purchase_history;

                const modalContent = `
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    <div class="fixed inset-0 z-10 overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                        Detalles del Cliente
                                    </h3>
                                    
                                    <!-- Customer Info -->
                                    <div class="border-b border-gray-200 pb-4 mb-4">
                                        <h4 class="text-md font-medium text-gray-900">
                                            ${customer.first_name} ${customer.last_name || ''}
                                        </h4>
                                        ${customer.email ? `<p class="text-sm text-gray-500">${customer.email}</p>` : ''}
                                        ${customer.phone ? `<p class="text-sm text-gray-500">${customer.phone}</p>` : ''}
                                        ${customer.tax_id ? `<p class="text-sm text-gray-500">RFC: ${customer.tax_id}</p>` : ''}
                                        ${customer.address ? `<p class="text-sm text-gray-500">${customer.address}</p>` : ''}
                                    </div>

                                    <!-- Statistics -->
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-sm text-gray-500">Total Compras</div>
                                            <div class="text-lg font-medium">${stats.total_purchases || 0}</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-sm text-gray-500">Total Gastado</div>
                                            <div class="text-lg font-medium">$${(stats.total_spent || 0).toFixed(2)}</div>
                                        </div>
                                    </div>

                                    <!-- Purchase History -->
                                    <div>
                                        <h4 class="text-md font-medium text-gray-900 mb-2">Historial de Compras</h4>
                                        ${purchaseHistory.length > 0 ? `
                                            <div class="space-y-2">
                                                ${purchaseHistory.map(purchase => `
                                                    <div class="bg-gray-50 p-2 rounded">
                                                        <div class="flex justify-between">
                                                            <span class="text-sm font-medium">
                                                                ${new Date(purchase.created_at).toLocaleDateString()}
                                                            </span>
                                                            <span class="text-sm font-medium">
                                                                $${purchase.total_amount.toFixed(2)}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            ${purchase.products}
                                                        </div>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        ` : `
                                            <p class="text-sm text-gray-500">No hay compras registradas</p>
                                        `}
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button type="button" onclick="closeCustomerModal()"
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const modal = document.getElementById('customerModal');
                modal.innerHTML = modalContent;
                modal.classList.remove('hidden');
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar los detalles del cliente');
        });
}

function closeCustomerModal() {
    document.getElementById('customerModal').classList.add('hidden');
}

async function handleCustomerSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const url = currentCustomerId 
            ? `/api/customers/${currentCustomerId}`
            : '/api/customers';
            
        const method = currentCustomerId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeCustomerModal();
            showSuccess(currentCustomerId ? 'Cliente actualizado' : 'Cliente creado');
            window.location.reload();
        } else {
            if (result.errors) {
                showFormErrors(result.errors);
            } else {
                showError(result.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al guardar el cliente');
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

function showFormErrors(errors) {
    const errorMessages = Object.entries(errors)
        .map(([field, message]) => `${field}: ${message}`)
        .join('\n');
    
    Swal.fire({
        icon: 'error',
        title: 'Error de Validación',
        text: errorMessages
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Search form handling
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    }
});

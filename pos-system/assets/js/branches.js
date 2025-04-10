function openAddBranchModal() {
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="branchForm" onsubmit="handleBranchSubmit(event)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Agregar Nueva Sucursal</h3>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre de la Sucursal</label>
                                    <input type="text" name="branchName" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Dirección</label>
                                    <input type="text" name="branchAddress" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                                    <input type="text" name="branchPhone"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="branchEmail"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar
                            </button>
                            <button type="button" onclick="closeModal('branchModal')"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('branchModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
}

function openEditBranchModal(branchId) {
    // Fetch branch data
    fetch(`/api/branches/${branchId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const branch = result.data.branch;
                const modalContent = `
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    <div class="fixed inset-0 z-10 overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form id="branchForm" onsubmit="handleBranchSubmit(event, ${branchId})">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                            Editar Sucursal
                                        </h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                                                <input type="text" name="name" required value="${branch.name}"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                                                <textarea name="address" rows="3"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">${branch.address || ''}</textarea>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                                                <input type="tel" name="phone" value="${branch.phone || ''}"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                                <input type="email" name="email" value="${branch.email || ''}"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                        <button type="submit"
                                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                            Guardar
                                        </button>
                                        <button type="button" onclick="closeModal('branchModal')"
                                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;

                const modal = document.getElementById('branchModal');
                modal.innerHTML = modalContent;
                modal.classList.remove('hidden');
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar los datos de la sucursal');
        });
}

function viewBranchDetails(branchId) {
    // Fetch branch details
    fetch(`/api/branches/${branchId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const details = result.data;
                const modalContent = `
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    <div class="fixed inset-0 z-10 overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                        Detalles de Sucursal
                                    </h3>
                                    
                                    <!-- Branch Info -->
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Nombre</label>
                                            <div class="mt-1 text-sm">${details.branch.name}</div>
                                        </div>
                                        ${details.branch.address ? `
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Dirección</label>
                                            <div class="mt-1 text-sm">${details.branch.address}</div>
                                        </div>
                                        ` : ''}
                                        ${details.branch.phone ? `
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Teléfono</label>
                                            <div class="mt-1 text-sm">${details.branch.phone}</div>
                                        </div>
                                        ` : ''}
                                        ${details.branch.email ? `
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Email</label>
                                            <div class="mt-1 text-sm">${details.branch.email}</div>
                                        </div>
                                        ` : ''}
                                    </div>

                                    <!-- Statistics -->
                                    <div class="mt-6 border-t border-gray-200 pt-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-2">Estadísticas</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Total Ventas</label>
                                                <div class="mt-1 text-sm">${details.stats.total_sales} ventas</div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Ingresos Totales</label>
                                                <div class="mt-1 text-sm">$${details.stats.total_revenue.toFixed(2)}</div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Venta Promedio</label>
                                                <div class="mt-1 text-sm">$${details.stats.average_sale.toFixed(2)}</div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Última Venta</label>
                                                <div class="mt-1 text-sm">${details.stats.last_sale ? new Date(details.stats.last_sale).toLocaleString() : 'N/A'}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Users -->
                                    <div class="mt-6 border-t border-gray-200 pt-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-2">Usuarios Asignados</h4>
                                        ${details.users.length > 0 ? `
                                            <div class="space-y-2">
                                                ${details.users.map(user => `
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <div class="text-sm font-medium">${user.username}</div>
                                                            <div class="text-xs text-gray-500">${user.email}</div>
                                                        </div>
                                                        <span class="text-xs font-medium ${user.status === 'active' ? 'text-green-600' : 'text-red-600'}">
                                                            ${user.status === 'active' ? 'Activo' : 'Inactivo'}
                                                        </span>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        ` : `
                                            <p class="text-sm text-gray-500">No hay usuarios asignados</p>
                                        `}
                                    </div>

                                    <!-- Open Sessions -->
                                    <div class="mt-6 border-t border-gray-200 pt-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-2">Cajas Abiertas</h4>
                                        ${details.open_sessions.length > 0 ? `
                                            <div class="space-y-2">
                                                ${details.open_sessions.map(session => `
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <div class="text-sm font-medium">Caja #${session.id}</div>
                                                            <div class="text-xs text-gray-500">${session.user_name}</div>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Abierta: ${new Date(session.opening_time).toLocaleString()}
                                                        </div>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        ` : `
                                            <p class="text-sm text-gray-500">No hay cajas abiertas</p>
                                        `}
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button type="button" onclick="closeModal('branchModal')"
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const modal = document.getElementById('branchModal');
                modal.innerHTML = modalContent;
                modal.classList.remove('hidden');
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar los detalles de la sucursal');
        });
}

async function handleBranchSubmit(event, branchId = null) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const url = branchId 
            ? `/api/branches/${branchId}`
            : '/api/branches';
            
        const method = branchId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeModal('branchModal');
            showSuccess(branchId ? 'Sucursal actualizada' : 'Sucursal creada');
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
        showError('Error al guardar la sucursal');
    }
}

function confirmDeactivateBranch(branchId) {
    Swal.fire({
        title: '¿Desactivar sucursal?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            deactivateBranch(branchId);
        }
    });
}

async function deactivateBranch(branchId) {
    try {
        const response = await fetch('/api/branches/deactivate', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ branch_id: branchId }),
        });

        const result = await response.json();
        
        if (result.success) {
            showSuccess('Sucursal desactivada');
            window.location.reload();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al desactivar la sucursal');
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
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
    // Filter form handling
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    }
});

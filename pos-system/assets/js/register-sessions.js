// Register Sessions Management JavaScript

function openRegisterModal() {
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="openRegisterForm" onsubmit="handleOpenRegister(event)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                Abrir Caja
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sucursal</label>
                                    <select name="branch_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Seleccionar sucursal</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Efectivo Inicial</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input type="number" name="initial_cash" required min="0" step="0.01"
                                            class="pl-7 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
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
                                Abrir Caja
                            </button>
                            <button type="button" onclick="closeModal('registerModal')"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('registerModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
    loadBranches();
}

function openCloseSessionModal(sessionId) {
    const modalContent = `
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form id="closeRegisterForm" onsubmit="handleCloseRegister(event, ${sessionId})">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                Cerrar Caja
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Efectivo Final</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input type="number" name="final_cash" required min="0" step="0.01"
                                            class="pl-7 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
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
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                Cerrar Caja
                            </button>
                            <button type="button" onclick="closeModal('registerModal')"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('registerModal');
    modal.innerHTML = modalContent;
    modal.classList.remove('hidden');
}

function viewSessionDetails(sessionId) {
    // Fetch session details
    fetch(`/api/register-sessions/${sessionId}`)
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
                                        Detalles de Sesión
                                    </h3>
                                    
                                    <!-- Session Info -->
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Sucursal</label>
                                                <div class="mt-1 text-sm">${details.session.branch_name}</div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Cajero</label>
                                                <div class="mt-1 text-sm">${details.session.user_name}</div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Apertura</label>
                                                <div class="mt-1 text-sm">
                                                    ${new Date(details.session.opening_time).toLocaleString()}
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-500">Cierre</label>
                                                <div class="mt-1 text-sm">
                                                    ${details.session.closing_time ? new Date(details.session.closing_time).toLocaleString() : 'En curso'}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sales Summary -->
                                        <div class="border-t border-gray-200 pt-4">
                                            <h4 class="text-md font-medium text-gray-900 mb-2">Resumen de Ventas</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500">Total Ventas</label>
                                                    <div class="mt-1 text-sm">${details.sales.total_sales} ventas</div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500">Monto Total</label>
                                                    <div class="mt-1 text-sm">$${details.sales.total_amount.toFixed(2)}</div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500">Efectivo</label>
                                                    <div class="mt-1 text-sm">$${details.sales.cash_sales.toFixed(2)}</div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500">Tarjeta</label>
                                                    <div class="mt-1 text-sm">$${details.sales.card_sales.toFixed(2)}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cash Summary -->
                                        <div class="border-t border-gray-200 pt-4">
                                            <h4 class="text-md font-medium text-gray-900 mb-2">Resumen de Efectivo</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500">Efectivo Inicial</label>
                                                    <div class="mt-1 text-sm">$${details.session.initial_cash.toFixed(2)}</div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500">Efectivo Final</label>
                                                    <div class="mt-1 text-sm">$${details.session.final_cash ? details.session.final_cash.toFixed(2) : 'Pendiente'}</div>
                                                </div>
                                                ${details.session.cash_difference !== null ? `
                                                <div class="col-span-2">
                                                    <label class="block text-sm font-medium text-gray-500">Diferencia</label>
                                                    <div class="mt-1 text-sm ${details.session.cash_difference < 0 ? 'text-red-600' : 'text-green-600'}">
                                                        $${details.session.cash_difference.toFixed(2)}
                                                    </div>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>

                                        ${details.session.notes ? `
                                        <div class="border-t border-gray-200 pt-4">
                                            <label class="block text-sm font-medium text-gray-500">Notas</label>
                                            <div class="mt-1 text-sm">${details.session.notes}</div>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button type="button" onclick="printSessionReport(${sessionId})"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                        Imprimir
                                    </button>
                                    <button type="button" onclick="closeModal('sessionDetailsModal')"
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const modal = document.getElementById('sessionDetailsModal');
                modal.innerHTML = modalContent;
                modal.classList.remove('hidden');
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar los detalles de la sesión');
        });
}

async function loadBranches() {
    try {
        const response = await fetch('/api/branches');
        const result = await response.json();
        
        if (result.success) {
            const select = document.querySelector('select[name="branch_id"]');
            result.data.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.id;
                option.textContent = branch.name;
                select.appendChild(option);
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading branches:', error);
        showError('Error al cargar las sucursales');
    }
}

async function handleOpenRegister(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/register-sessions/open', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeModal('registerModal');
            showSuccess('Caja abierta exitosamente');
            window.location.reload();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al abrir la caja');
    }
}

async function handleCloseRegister(event, sessionId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/register-sessions/close', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                final_cash: formData.get('final_cash'),
                notes: formData.get('notes')
            }),
        });

        const result = await response.json();
        
        if (result.success) {
            closeModal('registerModal');
            showSuccess('Caja cerrada exitosamente');
            window.location.reload();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al cerrar la caja');
    }
}

async function printSessionReport(sessionId) {
    try {
        const response = await fetch(`/api/register-sessions/report?id=${sessionId}`);
        const result = await response.json();
        
        if (result.success) {
            const report = result.data;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Reporte de Sesión #${report.session.id}</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .section { margin-bottom: 20px; }
                            .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                            .total { font-weight: bold; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>Reporte de Sesión de Caja</h2>
                            <p>Sesión #${report.session.id}</p>
                        </div>

                        <div class="section">
                            <h3>Información General</h3>
                            <div class="grid">
                                <div>
                                    <strong>Sucursal:</strong> ${report.session.branch}
                                </div>
                                <div>
                                    <strong>Cajero:</strong> ${report.session.cashier}
                                </div>
                                <div>
                                    <strong>Apertura:</strong> ${new Date(report.session.opening_time).toLocaleString()}
                                </div>
                                <div>
                                    <strong>Cierre:</strong> ${report.session.closing_time ? new Date(report.session.closing_time).toLocaleString() : 'En curso'}
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <h3>Resumen de Ventas</h3>
                            <div class="grid">
                                <div>
                                    <strong>Total Ventas:</strong> ${report.sales_summary.total_sales}
                                </div>
                                <div>
                                    <strong>Monto Total:</strong> $${report.sales_summary.total_amount.toFixed(2)}
                                </div>
                                <div>
                                    <strong>Efectivo:</strong> $${report.sales_summary.cash_sales.toFixed(2)}
                                </div>
                                <div>
                                    <strong>Tarjeta:</strong> $${report.sales_summary.card_sales.toFixed(2)}
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <h3>Resumen de Efectivo</h3>
                            <div class="grid">
                                <div>
                                    <strong>Efectivo Inicial:</strong> $${report.session.initial_cash.toFixed(2)}
                                </div>
                                <div>
                                    <strong>Efectivo Final:</strong> $${report.session.final_cash ? report.session.final_cash.toFixed(2) : 'Pendiente'}
                                </div>
                                ${report.session.cash_difference !== null ? `
                                <div class="total">
                                    <strong>Diferencia:</strong> $${report.session.cash_difference.toFixed(2)}
                                </div>
                                ` : ''}
                            </div>
                        </div>

                        ${report.session.notes ? `
                        <div class="section">
                            <h3>Notas</h3>
                            <p>${report.session.notes}</p>
                        </div>
                        ` : ''}

                        <div class="no-print" style="margin-top: 20px; text-align: center;">
                            <button onclick="window.print()">Imprimir</button>
                        </div>

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
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al generar el reporte');
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

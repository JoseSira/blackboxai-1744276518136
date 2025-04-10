<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Ventas - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Procesar Ventas</h1>
        <form id="saleForm">
            <div class="mb-4">
                <label for="customer" class="block text-sm font-medium text-gray-700">Cliente</label>
                <input type="text" id="customer" name="customer" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nombre del cliente">
            </div>
            <div class="mb-4">
                <label for="product" class="block text-sm font-medium text-gray-700">Producto</label>
                <select id="product" name="product" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Seleccione un producto</option>
                    <!-- Los productos se cargarán aquí dinámicamente -->
                </select>
            </div>
            <div class="mb-4">
                <label for="quantity" class="block text-sm font-medium text-gray-700">Cantidad</label>
                <input type="number" id="quantity" name="quantity" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Cantidad" required>
            </div>
            <div class="mb-4">
                <label for="paymentMethod" class="block text-sm font-medium text-gray-700">Método de Pago</label>
                <select id="paymentMethod" name="paymentMethod" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="cash">Efectivo</option>
                    <option value="card">Tarjeta</option>
                    <option value="mobile">Pago Móvil</option>
                </select>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="cancelSale()" class="mr-2 bg-gray-300 text-gray-700 px-4 py-2 rounded">Cancelar</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Realizar Venta</button>
            </div>
        </form>
        <div id="salesTable" class="mt-6">
            <h2 class="text-xl font-semibold mb-2">Ventas Realizadas</h2>
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="border-b-2 border-gray-300 px-4 py-2">ID</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Cliente</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Total</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Método de Pago</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <!-- Las ventas se cargarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>

    <script src="/assets/js/sales.js"></script>
</body>
</html>

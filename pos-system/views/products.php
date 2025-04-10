<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Gestión de Productos</h1>
        <button onclick="openAddProductModal()" class="mb-4 bg-blue-500 text-white px-4 py-2 rounded">Agregar Producto</button>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="border-b-2 border-gray-300 px-4 py-2">ID</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Nombre</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Precio</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Stock</th>
                        <th class="border-b-2 border-gray-300 px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <!-- Los productos se cargarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para agregar producto -->
    <div id="addProductModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Agregar Producto</h2>
                <form id="addProductForm">
                    <div class="mb-4">
                        <label for="productName" class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
                        <input type="text" id="productName" name="productName" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="productPrice" class="block text-sm font-medium text-gray-700">Precio</label>
                        <input type="number" id="productPrice" name="productPrice" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="productStock" class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number" id="productStock" name="productStock" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeAddProductModal()" class="mr-2 bg-gray-300 text-gray-700 px-4 py-2 rounded">Cancelar</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/products.js"></script>
</body>
</html>

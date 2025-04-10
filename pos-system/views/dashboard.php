<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Panel de Control</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="products.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                <h2 class="text-xl font-semibold">Gestión de Productos</h2>
                <p class="mt-2">Agregar, editar y ver productos en el inventario.</p>
            </a>
            <a href="sales.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                <h2 class="text-xl font-semibold">Procesar Ventas</h2>
                <p class="mt-2">Realizar ventas y gestionar pagos.</p>
            </a>
            <a href="customers.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                <h2 class="text-xl font-semibold">Gestión de Clientes</h2>
                <p class="mt-2">Agregar y gestionar perfiles de clientes.</p>
            </a>
            <a href="register-sessions.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                <h2 class="text-xl font-semibold">Gestión de Sesiones de Caja</h2>
                <p class="mt-2">Abrir y cerrar sesiones de caja.</p>
            </a>
            <a href="branches.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                <h2 class="text-xl font-semibold">Gestión de Sucursales</h2>
                <p class="mt-2">Administrar múltiples sucursales.</p>
            </a>
        </div>
    </div>
</body>
</html>

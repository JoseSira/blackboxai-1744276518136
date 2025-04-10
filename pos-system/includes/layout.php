<?php
// Get current user if logged in
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="h-full">
    <?php if (isLoggedIn()): ?>
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-indigo-600">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <a href="/dashboard.php" class="text-white font-bold text-xl">
                                <?= APP_NAME ?>
                            </a>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Dashboard
                                </a>
                                
                                <?php if (hasPermission('make_sales')): ?>
                                <a href="/pos.php" class="<?= basename($_SERVER['PHP_SELF']) === 'pos.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Punto de Venta
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_products')): ?>
                                <a href="/products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Productos
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_categories')): ?>
                                <a href="/categories.php" class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Categorías
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_customers')): ?>
                                <a href="/customers.php" class="<?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Clientes
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_sales')): ?>
                                <a href="/sales.php" class="<?= basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Ventas
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_register')): ?>
                                <a href="/register-sessions.php" class="<?= basename($_SERVER['PHP_SELF']) === 'register-sessions.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Caja
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_branches')): ?>
                                <a href="/branches.php" class="<?= basename($_SERVER['PHP_SELF']) === 'branches.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Sucursales
                                </a>
                                <?php endif; ?>

                                <?php if (hasPermission('manage_users')): ?>
                                <a href="/users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white rounded-md px-3 py-2 text-sm font-medium">
                                    Usuarios
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-4 flex items-center md:ml-6">
                            <!-- Profile dropdown -->
                            <div class="relative ml-3">
                                <div>
                                    <button type="button" onclick="toggleProfileMenu()" class="flex max-w-xs items-center rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600" id="user-menu-button">
                                        <span class="sr-only">Open user menu</span>
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white">
                                            <span class="text-sm font-medium leading-none text-indigo-600">
                                                <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
                                            </span>
                                        </span>
                                    </button>
                                </div>
                                <div id="profile-menu" class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu">
                                    <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-200">
                                        <div class="font-medium"><?= htmlspecialchars($currentUser['username']) ?></div>
                                        <div class="text-gray-500"><?= getRoleName($currentUser['role']) ?></div>
                                    </div>
                                    <?php if (hasPermission('manage_settings')): ?>
                                    <a href="/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Configuración</a>
                                    <?php endif; ?>
                                    <a href="/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Mi Perfil</a>
                                    <a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Cerrar Sesión</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="-mr-2 flex md:hidden">
                        <!-- Mobile menu button -->
                        <button type="button" onclick="toggleMobileMenu()" class="inline-flex items-center justify-center rounded-md bg-indigo-600 p-2 text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">
                            <span class="sr-only">Open main menu</span>
                            <i class="fas fa-bars h-6 w-6"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="space-y-1 px-2 pb-3 pt-2 sm:px-3">
                    <a href="/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Dashboard
                    </a>
                    
                    <?php if (hasPermission('make_sales')): ?>
                    <a href="/pos.php" class="<?= basename($_SERVER['PHP_SELF']) === 'pos.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Punto de Venta
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_products')): ?>
                    <a href="/products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Productos
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_categories')): ?>
                    <a href="/categories.php" class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Categorías
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_customers')): ?>
                    <a href="/customers.php" class="<?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Clientes
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_sales')): ?>
                    <a href="/sales.php" class="<?= basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Ventas
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_register')): ?>
                    <a href="/register-sessions.php" class="<?= basename($_SERVER['PHP_SELF']) === 'register-sessions.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Caja
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_branches')): ?>
                    <a href="/branches.php" class="<?= basename($_SERVER['PHP_SELF']) === 'branches.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Sucursales
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission('manage_users')): ?>
                    <a href="/users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'bg-indigo-700' : 'hover:bg-indigo-500' ?> text-white block rounded-md px-3 py-2 text-base font-medium">
                        Usuarios
                    </a>
                    <?php endif; ?>
                </div>
                <div class="border-t border-indigo-700 pb-3 pt-4">
                    <div class="flex items-center px-5">
                        <div class="flex-shrink-0">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white">
                                <span class="text-lg font-medium leading-none text-indigo-600">
                                    <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
                                </span>
                            </span>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-white"><?= htmlspecialchars($currentUser['username']) ?></div>
                            <div class="text-sm font-medium text-indigo-300"><?= getRoleName($currentUser['role']) ?></div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1 px-2">
                        <?php if (hasPermission('manage_settings')): ?>
                        <a href="/settings.php" class="block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-indigo-500">Configuración</a>
                        <?php endif; ?>
                        <a href="/profile.php" class="block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-indigo-500">Mi Perfil</a>
                        <a href="/logout.php" class="block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-indigo-500">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main content -->
        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <?= $content ?>
            </div>
        </main>
    </div>
    <?php else: ?>
        <?= $content ?>
    <?php endif; ?>

    <script>
        function toggleProfileMenu() {
            const menu = document.getElementById('profile-menu');
            menu.classList.toggle('hidden');
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            const profileMenu = document.getElementById('profile-menu');
            const profileButton = document.getElementById('user-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileButton = document.querySelector('button[type="button"]');

            if (!profileButton?.contains(event.target)) {
                profileMenu?.classList.add('hidden');
            }

            if (!mobileButton?.contains(event.target) && !mobileMenu?.contains(event.target)) {
                mobileMenu?.classList.add('hidden');
            }
        });

        // Show success message if present in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMessage = urlParams.get('success');
        if (successMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: decodeURIComponent(successMessage),
                timer: 3000,
                showConfirmButton: false
            });
            // Remove success message from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Show error message if present in URL
        const errorMessage = urlParams.get('error');
        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: decodeURIComponent(errorMessage)
            });
            // Remove error message from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>

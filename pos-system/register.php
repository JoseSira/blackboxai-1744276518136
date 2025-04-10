<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

$auth = new AuthController();

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    header('Location: /dashboard.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'business_name' => $_POST['business_name'] ?? '',
        'tax_id' => $_POST['tax_id'] ?? '',
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];

    // Validate input
    $errors = $auth->validateRegistrationData($data);

    if (empty($errors)) {
        $result = $auth->register($data);
        if ($result['success']) {
            // Auto-login after registration
            $loginResult = $auth->login($data['username'], $data['password']);
            if ($loginResult['success']) {
                header('Location: /dashboard.php');
                exit;
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Start output buffering
ob_start();
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                <?= APP_NAME ?>
            </h2>
            <h3 class="mt-2 text-center text-xl text-gray-600">
                Crear Nueva Cuenta
            </h3>
        </div>

        <?php if (isset($error)): ?>
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        <?= htmlspecialchars($error) ?>
                    </h3>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <!-- Business Information -->
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700">
                        Nombre del Negocio *
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-store text-gray-400"></i>
                        </div>
                        <input id="business_name" name="business_name" type="text" required
                            class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Nombre de tu negocio"
                            value="<?= htmlspecialchars($data['business_name'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['business_name'])): ?>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($errors['business_name']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700">
                        RFC
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-file-invoice text-gray-400"></i>
                        </div>
                        <input id="tax_id" name="tax_id" type="text"
                            class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="RFC de tu negocio"
                            value="<?= htmlspecialchars($data['tax_id'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['tax_id'])): ?>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($errors['tax_id']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- User Information -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Usuario *
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required
                            class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Nombre de usuario"
                            value="<?= htmlspecialchars($data['username'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['username'])): ?>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($errors['username']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email *
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" required
                            class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="tu@email.com"
                            value="<?= htmlspecialchars($data['email'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($errors['email']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Contraseña *
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" required
                            class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Contraseña">
                    </div>
                    <?php if (isset($errors['password'])): ?>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($errors['password']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">
                        Teléfono
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input id="phone" name="phone" type="tel"
                            class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Teléfono de contacto"
                            value="<?= htmlspecialchars($data['phone'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                    <p class="mt-1 text-sm text-red-600">
                        <?= htmlspecialchars($errors['phone']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-indigo-500 group-hover:text-indigo-400"></i>
                    </span>
                    Registrarse
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <a href="/login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>

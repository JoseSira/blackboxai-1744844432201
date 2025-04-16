<?php
require_once 'config.php';
require_once 'functions.php';

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $country = sanitizeInput($_POST['country']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($username) || empty($email) || empty($phone) || empty($country) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (!validateEmail($email)) {
        $error = 'El correo electrónico no es válido';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Verificar si el usuario o email ya existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'El usuario o correo electrónico ya está registrado';
        } else {
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar nuevo usuario
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone, country, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $phone, $country, $password_hash);

            if ($stmt->execute()) {
                $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
                // Redirigir después de 2 segundos
                header("refresh:2;url=login.php");
            } else {
                $error = 'Error al registrar el usuario';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Plataforma de Entrenamiento</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Country Select CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#8B5CF6',
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .iti {
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Training Platform</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Crear una cuenta
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                ¿Ya tienes una cuenta?
                <a href="login.php" class="font-medium text-primary hover:text-blue-500">
                    Inicia sesión
                </a>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="register.php" method="POST" id="registerForm">
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            Usuario
                        </label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Correo electrónico
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            Teléfono
                        </label>
                        <div class="mt-1">
                            <input id="phone" name="phone" type="tel" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Country -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">
                            País
                        </label>
                        <div class="mt-1">
                            <select id="country" name="country" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            </select>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Contraseña
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirmar contraseña
                        </label>
                        <div class="mt-1">
                            <input id="confirm_password" name="confirm_password" type="password" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Registrarse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
    <script>
        // Inicializar el selector de teléfono con banderas
        const phoneInput = window.intlTelInput(document.querySelector("#phone"), {
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
            separateDialCode: true,
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                fetch("https://ipapi.co/json")
                    .then(res => res.json())
                    .then(data => callback(data.country_code))
                    .catch(() => callback("US"));
            }
        });

        // Cargar países en el select
        fetch('https://restcountries.com/v3.1/all')
            .then(response => response.json())
            .then(countries => {
                const select = document.getElementById('country');
                countries.sort((a, b) => a.name.common.localeCompare(b.name.common));
                
                countries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.name.common;
                    option.textContent = country.name.common;
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('Error cargando países:', error));

        // Validación del formulario
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }

            // Obtener el número de teléfono completo con código de país
            const phoneNumber = phoneInput.getNumber();
            document.getElementById('phone').value = phoneNumber;
        });
    </script>
</body>
</html>

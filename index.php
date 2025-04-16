<?php
require_once 'config.php';
require_once 'functions.php';

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Entrenamiento</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',    // Blue
                        secondary: '#8B5CF6',   // Purple
                        white: '#FFFFFF'
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Training Platform</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="login.php" class="text-gray-600 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">
                        Iniciar Sesión
                    </a>
                    <a href="register.php" class="ml-4 px-4 py-2 rounded-md text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Registrarse
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0">
            <img class="w-full h-full object-cover" src="https://images.pexels.com/photos/1552242/pexels-photo-1552242.jpeg" alt="Background">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-purple-900 opacity-75"></div>
        </div>
        
        <!-- Development Banner -->
        <div class="absolute top-0 left-0 right-0 bg-yellow-500 text-center py-2">
            <p class="text-white font-semibold">
                <i class="fas fa-code mr-2"></i>
                Plataforma en Desarrollo
                <i class="fas fa-tools ml-2"></i>
            </p>
        </div>

        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-16 mx-auto max-w-7xl px-4 sm:mt-24 sm:px-6 md:mt-32 lg:mt-40 lg:px-8">
                    <div class="text-center">
                        <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                            <span class="block">Transforma tu vida con</span>
                            <span class="block text-blue-400">entrenamiento profesional</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-100 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl">
                            Accede a videos de entrenamiento de alta calidad. Mejora tu técnica, aumenta tu resistencia y alcanza tus objetivos fitness con nuestros programas especializados.
                        </p>
                        <div class="mt-5 sm:mt-8 flex justify-center space-x-4">
                            <a href="register.php" class="transform hover:scale-105 transition-transform duration-200 inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                <i class="fas fa-user-plus mr-2"></i>
                                Registrarse
                            </a>
                            <a href="login.php" class="transform hover:scale-105 transition-transform duration-200 inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Iniciar Sesión
                            </a>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full" src="https://images.pexels.com/photos/841130/pexels-photo-841130.jpeg" alt="Entrenamiento">
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Características</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Una mejor manera de entrenar
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    Descubre todo lo que nuestra plataforma tiene para ofrecerte.
                </p>
            </div>

            <div class="mt-10">
                <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                    <!-- Feature 1 -->
                    <div class="relative">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-video"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Videos profesionales</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Accede a contenido de alta calidad creado por expertos en el campo.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-key"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Acceso flexible</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Sistema de keys para desbloquear exactamente los videos que necesitas.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Acceso multiplataforma</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Entrena desde cualquier dispositivo, en cualquier momento.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="relative">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Seguimiento de progreso</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Mantén un registro de tus entrenamientos completados.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-dumbbell text-white text-2xl mr-2"></i>
                    <span class="text-white text-xl font-semibold">Training Platform</span>
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-facebook text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-youtube text-xl"></i>
                    </a>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-8">
                <p class="text-center text-base text-gray-400">
                    &copy; 2024 Training Platform. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>

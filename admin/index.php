<?php
require_once '../config.php';
require_once '../functions.php';

// Verificar si el usuario es administrador
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Obtener estadísticas
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'],
    'courses' => $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'],
    'videos' => $conn->query("SELECT COUNT(*) as count FROM episodes")->fetch_assoc()['count'],
    'active_keys' => $conn->query("SELECT COUNT(*) as count FROM subscription_keys WHERE is_used = 0")->fetch_assoc()['count']
];

// Obtener últimos usuarios registrados
$recent_users = $conn->query("
    SELECT username, email, created_at 
    FROM users 
    WHERE role != 'admin' 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Obtener últimas keys generadas
$recent_keys = $conn->query("
    SELECT sk.key_code, sk.created_at, sk.is_used, c.title as course_title
    FROM subscription_keys sk
    JOIN courses c ON sk.course_id = c.id
    ORDER BY sk.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Training Platform</title>
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
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="flex items-center justify-center h-16 border-b">
                <i class="fas fa-dumbbell text-primary text-2xl mr-2"></i>
                <span class="text-xl font-semibold text-gray-800">Admin Panel</span>
            </div>
            <nav class="mt-6">
                <div class="px-4 py-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img class="h-8 w-8 rounded-full bg-primary text-white flex items-center justify-center" 
                                 src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=3B82F6&color=fff" 
                                 alt="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                            <span class="ml-2 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="index.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100 bg-opacity-60">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    <a href="users.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-users mr-3"></i>
                        Usuarios
                    </a>
                    <a href="courses.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-graduation-cap mr-3"></i>
                        Cursos
                    </a>
                    <a href="generate_keys.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-key mr-3"></i>
                        Generar Keys
                    </a>
                    <a href="upload_video.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-video mr-3"></i>
                        Subir Videos
                    </a>
                    <a href="../logout.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <!-- Top bar -->
            <div class="bg-white shadow-sm">
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Users Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 bg-opacity-75">
                                <i class="fas fa-users text-primary text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-sm font-medium text-gray-600">Usuarios</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['users']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Courses Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 bg-opacity-75">
                                <i class="fas fa-graduation-cap text-secondary text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-sm font-medium text-gray-600">Cursos</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['courses']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Videos Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 bg-opacity-75">
                                <i class="fas fa-video text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-sm font-medium text-gray-600">Videos</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['videos']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Keys Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 bg-opacity-75">
                                <i class="fas fa-key text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-sm font-medium text-gray-600">Keys Activas</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['active_keys']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Users -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Usuarios Recientes</h2>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Keys -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Keys Recientes</h2>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($recent_keys as $key): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($key['key_code']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($key['course_title']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($key['is_used']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Usada
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Activa
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

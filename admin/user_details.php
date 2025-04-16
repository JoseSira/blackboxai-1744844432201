<?php
require_once '../config.php';
require_once '../functions.php';

// Verificar si el usuario es administrador
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Verificar si se proporcionó un ID de usuario
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = (int)$_GET['id'];

// Obtener información del usuario
$stmt = $conn->prepare("
    SELECT u.*, 
           COUNT(DISTINCT uva.video_id) as total_videos_accessed,
           COUNT(DISTINCT CASE WHEN uva.is_completed = 1 THEN uva.video_id END) as completed_videos
    FROM users u
    LEFT JOIN user_video_access uva ON u.id = uva.user_id
    WHERE u.id = ? AND u.role != 'admin'
    GROUP BY u.id
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit();
}

// Obtener historial de acceso a videos
$stmt = $conn->prepare("
    SELECT 
        e.title as video_title,
        c.title as course_title,
        uva.accessed_at,
        uva.completed_at,
        uva.is_completed,
        sk.key_code
    FROM user_video_access uva
    JOIN episodes e ON uva.video_id = e.id
    JOIN courses c ON e.course_id = c.id
    JOIN subscription_keys sk ON uva.key_id = sk.id
    WHERE uva.user_id = ?
    ORDER BY uva.accessed_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$access_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Usuario - Panel de Administración</title>
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
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <a href="users.php" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-arrow-left text-primary text-xl mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Volver a Usuarios</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- User Info Card -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="h-20 w-20 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div class="ml-6">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </h1>
                        <p class="text-gray-500">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                    </div>
                    <div class="ml-auto">
                        <span class="px-3 py-1 rounded-full <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $user['is_active'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="border-t px-6 py-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">País</span>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['country']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Teléfono</span>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Fecha de Registro</span>
                        <p class="font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Última Actualización</span>
                        <p class="font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($user['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 bg-opacity-75">
                        <i class="fas fa-video text-primary text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600">Videos Accedidos</h2>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $user['total_videos_accessed']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 bg-opacity-75">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600">Videos Completados</h2>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $user['completed_videos']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 bg-opacity-75">
                        <i class="fas fa-percentage text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600">Tasa de Finalización</h2>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php 
                            echo $user['total_videos_accessed'] > 0 
                                ? round(($user['completed_videos'] / $user['total_videos_accessed']) * 100) 
                                : 0; 
                            ?>%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Access History -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Historial de Acceso</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key Usada</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Acceso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($access_history as $access): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($access['video_title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($access['course_title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($access['key_code']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($access['accessed_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($access['is_completed']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Completado
                                        <?php if ($access['completed_at']): ?>
                                            <span class="ml-1 text-gray-500">
                                                (<?php echo date('d/m/Y H:i', strtotime($access['completed_at'])); ?>)
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        En progreso
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
</body>
</html>

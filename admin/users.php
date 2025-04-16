<?php
require_once '../config.php';
require_once '../functions.php';

// Verificar si el usuario es administrador
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Manejar bloqueo/desbloqueo de usuario
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    
    $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success = 'Estado del usuario actualizado exitosamente';
    } else {
        $error = 'Error al actualizar el estado del usuario';
    }
}

// Obtener todos los usuarios (excepto administradores)
$users = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT uva.video_id) as videos_accessed,
           MAX(uva.accessed_at) as last_access
    FROM users u
    LEFT JOIN user_video_access uva ON u.id = uva.user_id
    WHERE u.role != 'admin'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Panel de Administración</title>
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
                    <a href="index.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    <a href="users.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100 bg-opacity-60">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Gestión de Usuarios</h1>
                </div>
            </div>

            <!-- Content -->
            <div class="container mx-auto px-6 py-8">
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

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Usuarios Registrados</h2>
                        <div class="flex space-x-2">
                            <input type="text" id="searchInput" placeholder="Buscar usuarios..." 
                                class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <select id="filterStatus" class="px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="all">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contacto
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        País
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Videos Accedidos
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Último Acceso
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                <tr class="user-row" data-status="<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Registrado: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['country']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $user['videos_accessed']; ?> videos
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $user['last_access'] ? date('d/m/Y H:i', strtotime($user['last_access'])) : 'Nunca'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $user['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="toggleUserStatus(<?php echo $user['id']; ?>)"
                                                class="text-<?php echo $user['is_active'] ? 'red' : 'green'; ?>-600 hover:text-<?php echo $user['is_active'] ? 'red' : 'green'; ?>-900">
                                            <?php echo $user['is_active'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                        <a href="user_details.php?id=<?php echo $user['id']; ?>"
                                           class="ml-3 text-primary hover:text-blue-600">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Detalles
                                        </a>
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

    <script>
        // Búsqueda y filtrado de usuarios
        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const userRows = document.querySelectorAll('.user-row');

        function filterUsers() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusFilter = filterStatus.value;

            userRows.forEach(row => {
                const username = row.querySelector('.text-gray-900').textContent.toLowerCase();
                const email = row.querySelector('.text-gray-900:nth-child(2)').textContent.toLowerCase();
                const status = row.dataset.status;

                const matchesSearch = username.includes(searchTerm) || email.includes(searchTerm);
                const matchesStatus = statusFilter === 'all' || status === statusFilter;

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterUsers);
        filterStatus.addEventListener('change', filterUsers);

        function toggleUserStatus(userId) {
            if (confirm('¿Está seguro de que desea cambiar el estado de este usuario?')) {
                window.location.href = `users.php?toggle_status=${userId}`;
            }
        }
    </script>
</body>
</html>

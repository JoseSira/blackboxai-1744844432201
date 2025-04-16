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

// Obtener todos los cursos para el selector
$courses = $conn->query("SELECT id, title FROM courses ORDER BY title")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = sanitizeInput($_POST['course_id']);
    $videos = isset($_POST['videos']) ? $_POST['videos'] : [];
    
    if (empty($course_id)) {
        $error = 'Por favor, seleccione un curso';
    } elseif (empty($videos)) {
        $error = 'Por favor, seleccione al menos un video';
    } else {
        // Generar key única
        $key_code = generateUniqueKey();
        
        // Guardar key en la base de datos
        $stmt = $conn->prepare("INSERT INTO subscription_keys (key_code, course_id, allowed_videos, created_by) VALUES (?, ?, ?, ?)");
        $allowed_videos_json = json_encode($videos);
        $admin_id = $_SESSION['user_id'];
        
        $stmt->bind_param("sisi", $key_code, $course_id, $allowed_videos_json, $admin_id);
        
        if ($stmt->execute()) {
            $success = "Key generada exitosamente: " . $key_code;
        } else {
            $error = "Error al generar la key";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Keys - Panel de Administración</title>
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
                    <a href="users.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-users mr-3"></i>
                        Usuarios
                    </a>
                    <a href="courses.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-graduation-cap mr-3"></i>
                        Cursos
                    </a>
                    <a href="generate_keys.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100 bg-opacity-60">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Generar Keys de Acceso</h1>
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
                        <button class="copy-key absolute top-0 right-0 px-4 py-3" data-key="<?php echo htmlspecialchars($key_code); ?>">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Generate Key Form -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <form action="generate_keys.php" method="POST" id="generateKeyForm">
                        <!-- Course Selection -->
                        <div class="mb-6">
                            <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Seleccionar Curso
                            </label>
                            <select id="course_id" name="course_id" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">Seleccione un curso</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Videos Selection -->
                        <div id="videosContainer" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Seleccionar Videos a Desbloquear
                            </label>
                            <div id="videosList" class="space-y-2">
                                <!-- Videos will be loaded here via AJAX -->
                                <p class="text-gray-500 text-sm italic">Seleccione un curso para ver los videos disponibles</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Generar Key
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Keys -->
                <div class="mt-8 bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-800">Keys Recientes</h2>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Videos</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $recent_keys = $conn->query("
                                        SELECT sk.*, c.title as course_title 
                                        FROM subscription_keys sk
                                        JOIN courses c ON sk.course_id = c.id
                                        ORDER BY sk.created_at DESC 
                                        LIMIT 10
                                    ")->fetch_all(MYSQLI_ASSOC);

                                    foreach ($recent_keys as $key):
                                        $videos = json_decode($key['allowed_videos'], true);
                                        $video_count = count($videos);
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($key['key_code']); ?></span>
                                                <button class="ml-2 text-gray-400 hover:text-gray-600 copy-key" data-key="<?php echo htmlspecialchars($key['key_code']); ?>">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($key['course_title']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900"><?php echo $video_count; ?> videos</span>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($key['created_at'])); ?>
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

    <script>
        // Cargar videos cuando se selecciona un curso
        document.getElementById('course_id').addEventListener('change', function() {
            const courseId = this.value;
            const videosList = document.getElementById('videosList');
            
            if (!courseId) {
                videosList.innerHTML = '<p class="text-gray-500 text-sm italic">Seleccione un curso para ver los videos disponibles</p>';
                return;
            }

            // Realizar petición AJAX para obtener los videos del curso
            fetch(`get_course_videos.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(videos => {
                    if (videos.length === 0) {
                        videosList.innerHTML = '<p class="text-gray-500 text-sm italic">No hay videos disponibles para este curso</p>';
                        return;
                    }

                    videosList.innerHTML = videos.map(video => `
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="videos[]" value="${video.id}" 
                                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            <label class="text-sm text-gray-700">
                                Video ${video.episode_number}: ${video.title}
                            </label>
                        </div>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error:', error);
                    videosList.innerHTML = '<p class="text-red-500 text-sm">Error al cargar los videos</p>';
                });
        });

        // Copiar key al portapapeles
        document.querySelectorAll('.copy-key').forEach(button => {
            button.addEventListener('click', function() {
                const key = this.dataset.key;
                navigator.clipboard.writeText(key).then(() => {
                    // Cambiar el ícono temporalmente
                    const icon = this.querySelector('i');
                    icon.classList.remove('fa-copy');
                    icon.classList.add('fa-check');
                    setTimeout(() => {
                        icon.classList.remove('fa-check');
                        icon.classList.add('fa-copy');
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>

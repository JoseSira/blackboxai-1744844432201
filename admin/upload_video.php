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

// Crear directorio para videos si no existe
$upload_dir = '../uploads/videos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Procesar eliminación de video
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $video_id = (int)$_GET['delete'];
    
    // Obtener información del video antes de eliminar
    $stmt = $conn->prepare("SELECT video_url FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $video = $stmt->get_result()->fetch_assoc();
    
    // Eliminar el archivo si es local
    if ($video && strpos($video['video_url'], 'uploads/videos/') !== false) {
        @unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $video['video_url']);
    }
    
    // Eliminar registro de la base de datos
    $stmt = $conn->prepare("DELETE FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    
    if ($stmt->execute()) {
        $success = 'Video eliminado exitosamente';
    } else {
        $error = 'Error al eliminar el video';
    }
}

// Procesar formulario de subida o edición de video
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = sanitizeInput($_POST['course_id']);
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $episode_number = (int)$_POST['episode_number'];
    $video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : null;

    // Validar campos básicos
    if (empty($course_id) || empty($title) || empty($episode_number)) {
        $error = 'Los campos título y número de episodio son obligatorios';
    } else {
        $video_url = '';
        
        // Procesar archivo subido
        if (isset($_FILES['video_file']) && $_FILES['video_file']['size'] > 0) {
            $file = $_FILES['video_file'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['mp4', 'webm', 'ogg'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = 'Formato de archivo no permitido. Use MP4, WebM o OGG.';
            } elseif ($file['size'] > 500000000) { // 500MB límite
                $error = 'El archivo es demasiado grande. Máximo 500MB.';
            } else {
                $filename = uniqid() . '.' . $file_extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $video_url = 'uploads/videos/' . $filename;
                } else {
                    $error = 'Error al subir el archivo';
                }
            }
        } elseif (!empty($_POST['video_url'])) {
            $video_url = sanitizeInput($_POST['video_url']);
        } elseif (!$video_id) {
            $error = 'Debe proporcionar un video (archivo o URL)';
        }

        if (empty($error)) {
            // Verificar duplicado de número de episodio
            $stmt = $conn->prepare("
                SELECT id FROM episodes 
                WHERE course_id = ? AND episode_number = ? 
                AND id != COALESCE(?, 0)
            ");
            $stmt->bind_param("iii", $course_id, $episode_number, $video_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Ya existe un video con ese número de episodio en este curso';
            } else {
                if ($video_id) {
                    // Actualizar video existente
                    $query = "UPDATE episodes SET 
                             course_id = ?, 
                             episode_number = ?, 
                             title = ?, 
                             description = ?";
                    $params = [$course_id, $episode_number, $title, $description];
                    $types = "iiss";
                    
                    if ($video_url) {
                        $query .= ", video_url = ?";
                        $params[] = $video_url;
                        $types .= "s";
                    }
                    
                    $query .= " WHERE id = ?";
                    $params[] = $video_id;
                    $types .= "i";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    
                    if ($stmt->execute()) {
                        $success = 'Video actualizado exitosamente';
                    } else {
                        $error = 'Error al actualizar el video';
                    }
                } else {
                    // Insertar nuevo video
                    $stmt = $conn->prepare("
                        INSERT INTO episodes (course_id, episode_number, title, description, video_url) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iisss", $course_id, $episode_number, $title, $description, $video_url);
                    
                    if ($stmt->execute()) {
                        $success = 'Video agregado exitosamente';
                    } else {
                        $error = 'Error al agregar el video';
                    }
                }
            }
        }
    }
}

// Obtener todos los videos
$videos = $conn->query("
    SELECT e.*, c.title as course_title 
    FROM episodes e 
    JOIN courses c ON e.course_id = c.id 
    ORDER BY c.title, e.episode_number
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Videos - Panel de Administración</title>
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
                    <a href="generate_keys.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-key mr-3"></i>
                        Generar Keys
                    </a>
                    <a href="upload_video.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100 bg-opacity-60">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Subir Videos</h1>
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

                <!-- Upload Form -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                    <form action="upload_video.php" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Curso
                                </label>
                                <select id="course_id" name="course_id" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                    <option value="">Seleccione un curso</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="episode_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    Número de Video
                                </label>
                                <input type="number" id="episode_number" name="episode_number" min="1" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Título del Video
                                </label>
                                <input type="text" id="title" name="title" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción
                                </label>
                                <textarea id="description" name="description" rows="3"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Video
                                </label>
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1">
                                        <input type="file" id="video_file" name="video_file" accept="video/*"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                    </div>
                                    <span class="text-gray-500">O</span>
                                    <div class="flex-1">
                                        <input type="url" id="video_url" name="video_url"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                            placeholder="https://example.com/video.mp4">
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">
                                    Sube un archivo de video o proporciona una URL. Formatos permitidos: MP4, WebM, OGG
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Subir Video
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Videos List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-800">Videos Subidos</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Curso
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Video #
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Título
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        URL
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($videos as $video): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($video['course_title']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $video['episode_number']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($video['title']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" 
                                           class="text-primary hover:text-blue-600">
                                            Ver Video
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button onclick="editVideo(<?php echo htmlspecialchars(json_encode($video)); ?>)"
                                                class="text-primary hover:text-blue-600 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteVideo(<?php echo $video['id']; ?>)"
                                                class="text-red-500 hover:text-red-600">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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
        // Validar número de episodio al cambiar el curso
        document.getElementById('course_id').addEventListener('change', function() {
            const courseId = this.value;
            const episodeNumber = document.getElementById('episode_number');
            
            if (courseId) {
                fetch(`get_last_episode.php?course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        episodeNumber.value = data.next_episode || 1;
                    })
                    .catch(error => console.error('Error:', error));
            }
        });

        // Variables globales para los modales
        let currentVideoUrl = '';
        const previewModal = document.getElementById('previewModal');
        const editModal = document.getElementById('editModal');
        const previewPlayer = document.getElementById('previewPlayer');

        // Funciones para el modal de vista previa
        function previewVideo(videoUrl) {
            previewPlayer.src = videoUrl;
            previewModal.classList.remove('hidden');
            previewPlayer.load();
        }

        function closePreviewModal() {
            previewPlayer.pause();
            previewPlayer.src = '';
            previewModal.classList.add('hidden');
        }

        // Funciones para el modal de edición
        function editVideo(video) {
            document.getElementById('edit_video_id').value = video.id;
            document.getElementById('edit_course_id').value = video.course_id;
            document.getElementById('edit_episode_number').value = video.episode_number;
            document.getElementById('edit_title').value = video.title;
            document.getElementById('edit_description').value = video.description;
            
            // Mostrar video actual
            currentVideoUrl = video.video_url;
            const videoContainer = document.getElementById('current_video_container');
            videoContainer.querySelector('span').textContent = video.video_url.split('/').pop();
            
            editModal.classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editForm').reset();
            editModal.classList.add('hidden');
        }

        function previewCurrentVideo() {
            if (currentVideoUrl) {
                previewVideo(currentVideoUrl);
            }
        }

        function deleteVideo(videoId) {
            if (confirm('¿Está seguro de que desea eliminar este video? Esta acción no se puede deshacer.')) {
                window.location.href = `upload_video.php?delete=${videoId}`;
            }
        }

        // Cerrar modales al hacer clic fuera de ellos
        window.onclick = function(event) {
            if (event.target === previewModal) {
                closePreviewModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }

        // Validación de formularios
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const videoFile = this.querySelector('input[type="file"]');
                const videoUrl = this.querySelector('input[type="url"]');
                
                if (!this.querySelector('input[name="video_id"]') && // Si no es edición
                    !videoFile.files.length && !videoUrl.value) {
                    e.preventDefault();
                    alert('Debe proporcionar un video (archivo o URL)');
                }
            });
        });
    </script>
</body>
</html>

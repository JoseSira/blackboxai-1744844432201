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

// Procesar formulario de creación/edición de curso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $cover_image = sanitizeInput($_POST['cover_image']);
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : null;

    if (empty($title) || empty($description) || empty($cover_image)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        if ($course_id) {
            // Actualizar curso existente
            $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, cover_image = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $description, $cover_image, $course_id);
            
            if ($stmt->execute()) {
                $success = 'Curso actualizado exitosamente';
            } else {
                $error = 'Error al actualizar el curso';
            }
        } else {
            // Crear nuevo curso
            $stmt = $conn->prepare("INSERT INTO courses (title, description, cover_image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $description, $cover_image);
            
            if ($stmt->execute()) {
                $success = 'Curso creado exitosamente';
            } else {
                $error = 'Error al crear el curso';
            }
        }
    }
}

// Eliminar curso
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    
    // Verificar si hay videos asociados
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM episodes WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = 'No se puede eliminar el curso porque tiene videos asociados';
    } else {
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $success = 'Curso eliminado exitosamente';
        } else {
            $error = 'Error al eliminar el curso';
        }
    }
}

// Obtener todos los cursos
$courses = $conn->query("
    SELECT c.*, 
           COUNT(e.id) as video_count,
           COUNT(DISTINCT sk.id) as key_count
    FROM courses c
    LEFT JOIN episodes e ON c.id = e.course_id
    LEFT JOIN subscription_keys sk ON c.id = sk.course_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos - Panel de Administración</title>
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
                    <a href="courses.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100 bg-opacity-60">
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
                <div class="px-6 py-4 flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-800">Gestión de Cursos</h1>
                    <button onclick="openModal()" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-plus mr-2"></i>Nuevo Curso
                    </button>
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

                <!-- Courses Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($courses as $course): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="relative pb-48">
                            <img class="absolute h-full w-full object-cover" 
                                 src="<?php echo htmlspecialchars($course['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-4">
                                <?php echo htmlspecialchars($course['description']); ?>
                            </p>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-video text-primary mr-2"></i>
                                    <span class="text-sm text-gray-600"><?php echo $course['video_count']; ?> videos</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-key text-secondary mr-2"></i>
                                    <span class="text-sm text-gray-600"><?php echo $course['key_count']; ?> keys</span>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)" 
                                        class="px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="fas fa-edit mr-2"></i>Editar
                                </button>
                                <?php if ($course['video_count'] == 0): ?>
                                <button onclick="deleteCourse(<?php echo $course['id']; ?>)" 
                                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fas fa-trash-alt mr-2"></i>Eliminar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="courseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Nuevo Curso</h3>
                <form id="courseForm" method="POST">
                    <input type="hidden" name="course_id" id="course_id">
                    
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700">Título</label>
                        <input type="text" name="title" id="title" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="description" id="description" rows="3" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="cover_image" class="block text-sm font-medium text-gray-700">URL de Imagen de Portada</label>
                        <input type="url" name="cover_image" id="cover_image" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"
                            placeholder="https://example.com/image.jpg">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Curso';
            document.getElementById('courseForm').reset();
            document.getElementById('course_id').value = '';
            document.getElementById('courseModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('courseModal').classList.add('hidden');
        }

        function editCourse(course) {
            document.getElementById('modalTitle').textContent = 'Editar Curso';
            document.getElementById('course_id').value = course.id;
            document.getElementById('title').value = course.title;
            document.getElementById('description').value = course.description;
            document.getElementById('cover_image').value = course.cover_image;
            document.getElementById('courseModal').classList.remove('hidden');
        }

        function deleteCourse(courseId) {
            if (confirm('¿Está seguro de que desea eliminar este curso?')) {
                window.location.href = `courses.php?delete=${courseId}`;
            }
        }

        // Cerrar modal al hacer clic fuera de él
        document.getElementById('courseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

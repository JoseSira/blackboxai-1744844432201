<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Obtener cursos disponibles
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM episodes WHERE course_id = c.id) as total_episodes,
          (SELECT COUNT(*) FROM user_video_access WHERE user_id = ? AND video_id IN 
            (SELECT id FROM episodes WHERE course_id = c.id)) as unlocked_episodes
          FROM courses c
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Plataforma de Entrenamiento</title>
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
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Training Platform</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <!-- User Dropdown -->
                    <div class="ml-3 relative">
                        <div>
                            <button type="button" class="flex items-center max-w-xs rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open user menu</span>
                                <div class="h-8 w-8 rounded-full bg-primary text-white flex items-center justify-center">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <span class="ml-2 text-gray-700"><?php echo htmlspecialchars($user['username']); ?></span>
                                <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                            </button>
                        </div>
                        <!-- Dropdown menu -->
                        <div class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1" id="user-menu">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Mi Perfil</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Ajustes</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Bienvenido, <?php echo htmlspecialchars($user['username']); ?>!
                </h1>
                <p class="mt-1 text-gray-600">
                    Explora nuestros cursos de entrenamiento y comienza tu transformación.
                </p>
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Cursos Disponibles</h2>
                <div class="relative">
                    <input type="text" placeholder="Buscar cursos..." class="w-64 px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="searchCourses">
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
            </div>

            <?php if (empty($courses)): ?>
            <div class="text-center py-12">
                <i class="fas fa-book-open text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">No hay cursos disponibles en este momento.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="relative pb-48">
                        <img class="absolute h-full w-full object-cover" src="<?php echo htmlspecialchars($course['cover_image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php if ($course['unlocked_episodes'] > 0): ?>
                        <div class="absolute top-0 right-0 m-2 px-2 py-1 bg-green-500 text-white text-sm rounded">
                            Desbloqueado
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo htmlspecialchars($course['description']); ?>
                        </p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-video text-primary mr-2"></i>
                                <span class="text-sm text-gray-600">
                                    <?php echo $course['unlocked_episodes']; ?>/<?php echo $course['total_episodes']; ?> videos
                                </span>
                            </div>
                            <a href="course.php?id=<?php echo $course['id']; ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Ver Curso
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle user menu
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchCourses');
        const courseCards = document.querySelectorAll('.grid > div');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            courseCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

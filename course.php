<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Verificar si se proporcionó un ID de curso
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$course_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Obtener detalles del curso
$stmt = $conn->prepare("
    SELECT * FROM courses WHERE id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Procesar canje de key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key_code'])) {
    $key_code = sanitizeInput($_POST['key_code']);
    $result = redeemKey($user_id, $key_code);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Obtener videos del curso y su estado de acceso
$stmt = $conn->prepare("
    SELECT e.*, 
           CASE WHEN uva.id IS NOT NULL THEN 1 ELSE 0 END as is_unlocked,
           uva.is_completed
    FROM episodes e
    LEFT JOIN user_video_access uva ON e.id = uva.video_id AND uva.user_id = ?
    WHERE e.course_id = ?
    ORDER BY e.episode_number
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Training Platform</title>
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
                    <a href="dashboard.php" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Training Platform</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Course Header -->
    <div class="relative">
        <div class="h-64 w-full bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($course['cover_image']); ?>')">
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative -mt-32">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php echo htmlspecialchars($course['description']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

        <!-- Redeem Key Form -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Canjear Key de Acceso</h2>
            <form action="course.php?id=<?php echo $course_id; ?>" method="POST" class="flex gap-4">
                <input type="text" name="key_code" placeholder="Ingrese su key aquí" required
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                <button type="submit"
                    class="px-6 py-2 bg-primary text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Canjear Key
                </button>
            </form>
        </div>

        <!-- Videos List -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Videos del Curso</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach ($videos as $video): ?>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php if ($video['is_completed']): ?>
                                    <span class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </span>
                                <?php elseif ($video['is_unlocked']): ?>
                                    <span class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-play text-primary"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Video <?php echo $video['episode_number']; ?>: <?php echo htmlspecialchars($video['title']); ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($video['description']); ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <?php if ($video['is_unlocked']): ?>
                                <a href="video.php?id=<?php echo $video['id']; ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-600">
                                    <?php echo $video['is_completed'] ? 'Ver de nuevo' : 'Ver video'; ?>
                                </a>
                            <?php else: ?>
                                <span class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50">
                                    <i class="fas fa-lock mr-2"></i>Bloqueado
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                &copy; 2024 Training Platform. Todos los derechos reservados.
            </p>
        </div>
    </footer>
</body>
</html>

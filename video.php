<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Verificar si se proporcionó un ID de video
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$video_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verificar si el usuario tiene acceso al video
if (!checkVideoAccess($user_id, $video_id)) {
    header('Location: dashboard.php');
    exit();
}

// Obtener detalles del video y curso
$stmt = $conn->prepare("
    SELECT e.*, c.title as course_title, c.id as course_id
    FROM episodes e
    JOIN courses c ON e.course_id = c.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

if (!$video) {
    header('Location: dashboard.php');
    exit();
}

// Obtener el siguiente video si existe
$stmt = $conn->prepare("
    SELECT id, episode_number
    FROM episodes
    WHERE course_id = ? AND episode_number > ?
    ORDER BY episode_number
    LIMIT 1
");
$stmt->bind_param("ii", $video['course_id'], $video['episode_number']);
$stmt->execute();
$next_video = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - Training Platform</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Plyr Video Player -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.2/plyr.css" />
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
        .plyr--video {
            border-radius: 0.5rem;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <a href="course.php?id=<?php echo $video['course_id']; ?>" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-primary text-2xl mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Training Platform</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="course.php?id=<?php echo $video['course_id']; ?>" class="text-gray-600 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Curso
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Video Player -->
            <div class="aspect-w-16 aspect-h-9">
                <video id="player" playsinline controls data-video-id="<?php echo $video_id; ?>">
                    <source src="<?php echo htmlspecialchars($video['video_url']); ?>" type="video/mp4" />
                </video>
            </div>

            <!-- Video Info -->
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($video['title']); ?>
                        </h1>
                        <p class="text-sm text-gray-500">
                            Video <?php echo $video['episode_number']; ?> de <?php echo htmlspecialchars($video['course_title']); ?>
                        </p>
                    </div>
                    <?php if ($next_video && checkVideoAccess($user_id, $next_video['id'])): ?>
                    <a href="video.php?id=<?php echo $next_video['id']; ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-600">
                        Siguiente Video
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="prose max-w-none">
                    <p class="text-gray-600">
                        <?php echo htmlspecialchars($video['description']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Progress Tracking -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tu Progreso</h2>
            <div class="flex items-center space-x-4">
                <button id="markCompleted" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Marcar como Completado
                </button>
                <span id="progressStatus" class="text-sm text-gray-600">
                    <!-- El estado se actualizará via JavaScript -->
                </span>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.plyr.io/3.7.2/plyr.polyfilled.js"></script>
    <script>
        // Inicializar Plyr
        const player = new Plyr('#player', {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'mute',
                'volume',
                'captions',
                'settings',
                'pip',
                'airplay',
                'fullscreen'
            ]
        });

        // Variables para tracking
        const videoId = document.querySelector('#player').dataset.videoId;
        let videoCompleted = false;

        // Detectar cuando el video termina
        player.on('ended', () => {
            markVideoAsCompleted();
        });

        // Función para marcar el video como completado
        function markVideoAsCompleted() {
            if (!videoCompleted) {
                fetch('mark_video_completed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        video_id: videoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        videoCompleted = true;
                        document.getElementById('progressStatus').innerHTML = 
                            '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Video completado</span>';
                        
                        // Si hay siguiente video y está desbloqueado, mostrar botón
                        if (data.next_video_url) {
                            window.location.href = data.next_video_url;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }

        // Botón manual para marcar como completado
        document.getElementById('markCompleted').addEventListener('click', markVideoAsCompleted);

        // Verificar estado inicial
        fetch(`check_video_status.php?video_id=${videoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.completed) {
                    videoCompleted = true;
                    document.getElementById('progressStatus').innerHTML = 
                        '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Video completado</span>';
                }
            });
    </script>
</body>
</html>

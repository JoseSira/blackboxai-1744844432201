<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Verificar si se proporcionó un ID de video
if (!isset($_GET['video_id']) || !is_numeric($_GET['video_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de video no válido']);
    exit();
}

$video_id = (int)$_GET['video_id'];
$user_id = $_SESSION['user_id'];

// Verificar si el video está completado
$stmt = $conn->prepare("
    SELECT is_completed, completed_at 
    FROM user_video_access 
    WHERE user_id = ? AND video_id = ?
");
$stmt->bind_param("ii", $user_id, $video_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    echo json_encode([
        'completed' => (bool)$result['is_completed'],
        'completed_at' => $result['completed_at']
    ]);
} else {
    echo json_encode([
        'completed' => false,
        'completed_at' => null
    ]);
}

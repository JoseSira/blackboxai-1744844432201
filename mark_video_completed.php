<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Verificar si la petición es POST y contiene datos JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['video_id']) || !is_numeric($data['video_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de video no válido']);
    exit();
}

$video_id = (int)$data['video_id'];
$user_id = $_SESSION['user_id'];

// Verificar si el usuario tiene acceso al video
if (!checkVideoAccess($user_id, $video_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene acceso a este video']);
    exit();
}

// Marcar el video como completado
$stmt = $conn->prepare("
    UPDATE user_video_access 
    SET is_completed = 1, 
        completed_at = CURRENT_TIMESTAMP 
    WHERE user_id = ? AND video_id = ?
");
$stmt->bind_param("ii", $user_id, $video_id);

if ($stmt->execute()) {
    // Obtener información del siguiente video
    $stmt = $conn->prepare("
        SELECT e2.id
        FROM episodes e1
        JOIN episodes e2 ON e1.course_id = e2.course_id AND e2.episode_number = e1.episode_number + 1
        WHERE e1.id = ?
    ");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $next_video = $stmt->get_result()->fetch_assoc();

    $response = ['success' => true];
    
    if ($next_video && checkVideoAccess($user_id, $next_video['id'])) {
        $response['next_video_url'] = 'video.php?id=' . $next_video['id'];
    }

    echo json_encode($response);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el progreso']);
}

<?php
require_once '../config.php';
require_once '../functions.php';

// Verificar si el usuario es administrador
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

// Verificar si se recibió el ID del curso
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de curso no válido']);
    exit();
}

$course_id = (int)$_GET['course_id'];

// Obtener los videos del curso
$stmt = $conn->prepare("
    SELECT id, episode_number, title, video_url 
    FROM episodes 
    WHERE course_id = ? 
    ORDER BY episode_number ASC
");

$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$videos = [];
while ($row = $result->fetch_assoc()) {
    $videos[] = [
        'id' => $row['id'],
        'episode_number' => $row['episode_number'],
        'title' => $row['title'],
        'video_url' => $row['video_url']
    ];
}

// Devolver los videos en formato JSON
header('Content-Type: application/json');
echo json_encode($videos);

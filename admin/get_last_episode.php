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

// Obtener el último número de episodio del curso
$stmt = $conn->prepare("
    SELECT MAX(episode_number) as last_episode 
    FROM episodes 
    WHERE course_id = ?
");

$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$next_episode = ($result['last_episode'] === null) ? 1 : $result['last_episode'] + 1;

// Devolver el siguiente número de episodio disponible
header('Content-Type: application/json');
echo json_encode(['next_episode' => $next_episode]);

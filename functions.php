<?php
require_once 'config.php';

// Funciones de autenticación
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Funciones de sanitización
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Funciones de validación
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Funciones de suscripción y keys
function validateKey($keyCode) {
    global $conn;
    $keyCode = sanitizeInput($keyCode);
    
    $query = "SELECT * FROM subscription_keys WHERE key_code = ? AND is_used = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $keyCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

function redeemKey($userId, $keyCode) {
    global $conn;
    
    $key = validateKey($keyCode);
    if (!$key) {
        return ['success' => false, 'message' => 'Key inválida o ya utilizada'];
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Marcar key como usada
        $query = "UPDATE subscription_keys SET is_used = 1 WHERE key_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $keyCode);
        $stmt->execute();
        
        // Crear registro de acceso para cada video permitido
        $allowedVideos = json_decode($key['allowed_videos']);
        foreach ($allowedVideos as $videoId) {
            $query = "INSERT INTO user_video_access (user_id, video_id, key_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $userId, $videoId, $key['id']);
            $stmt->execute();
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Key canjeada exitosamente'];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error al canjear key: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al canjear la key'];
    }
}

// Funciones de acceso a videos
function checkVideoAccess($userId, $videoId) {
    global $conn;
    
    $query = "SELECT * FROM user_video_access WHERE user_id = ? AND video_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $videoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

function getUserVideos($userId) {
    global $conn;
    
    $query = "SELECT v.*, uva.is_completed 
              FROM videos v 
              INNER JOIN user_video_access uva ON v.id = uva.video_id 
              WHERE uva.user_id = ?
              ORDER BY v.course_id, v.episode_number";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Funciones de manejo de errores
function displayError($message) {
    return "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>
        <span class='block sm:inline'>$message</span>
    </div>";
}

function displaySuccess($message) {
    return "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>
        <span class='block sm:inline'>$message</span>
    </div>";
}

// Funciones de utilidad
function generateUniqueKey() {
    return bin2hex(random_bytes(16));
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

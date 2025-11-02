<?php
// Iniciar sesión automáticamente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    if(is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function uploadImage($file, $target_dir) {
    // Verificar si se subió un archivo
    if($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar si es una imagen
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Verificar tipo de archivo
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if(!in_array($imageFileType, $allowed_types)) {
        return false;
    }
    
    // Verificar tamaño (máximo 5MB)
    if($file["size"] > 5000000) {
        return false;
    }
    
    // Generar nombre único
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Crear directorio si no existe
    if(!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Mover archivo
    if(move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    }
    
    return false;
}

// FUNCIONES NUEVAS QUE TE FALTAN:

function formatDate($date_string) {
    $date = new DateTime($date_string);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if($diff->y > 0) {
        return $date->format('d M Y');
    } elseif($diff->m > 0) {
        return $date->format('d M');
    } elseif($diff->d > 0) {
        return $diff->d == 1 ? 'Ayer' : $diff->d . ' días';
    } elseif($diff->h > 0) {
        return $diff->h . ' h';
    } elseif($diff->i > 0) {
        return $diff->i . ' min';
    } else {
        return 'Ahora';
    }
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    return $protocol . "://" . $host . $path;
}

function generateCsrfToken() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function showMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if(isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isImageFile($filename) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = getFileExtension($filename);
    return in_array($extension, $allowed_extensions);
}

function truncateText($text, $length = 100) {
    if(strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function requireLogin() {
    if(!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    // Aquí puedes agregar lógica para verificar si es admin
    // Por ahora, solo requerimos que esté logueado
}

// Sistema de "Recordarme" - Verificar cookie al cargar las funciones
if(!isLoggedIn() && isset($_COOKIE['remember_user'])) {
    $user_id = $_COOKIE['remember_user'];
    
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT id, username, profile_picture FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    if($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
    } else {
        // Cookie inválida, eliminarla
        setcookie('remember_user', '', time() - 3600, '/');
    }
}

// Función para crear notificaciones
function createNotification($user_id, $from_user_id, $type, $message, $reference_id = null) {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "INSERT INTO notifications (user_id, from_user_id, type, message, reference_id) 
              VALUES (:user_id, :from_user_id, :type, :message, :reference_id)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':from_user_id', $from_user_id);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':reference_id', $reference_id);
    
    return $stmt->execute();
}

// Función para obtener el número de notificaciones no leídas
function getUnreadNotificationsCount($user_id) {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

// Función para marcar notificaciones como leídas
function markNotificationsAsRead($user_id, $notification_ids = []) {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if(empty($notification_ids)) {
        $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
        $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->execute(array_merge([$user_id], $notification_ids));
    }
    
    return $stmt->rowCount();
}

// Función para limpiar datos antiguos
function cleanupOldData() {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Eliminar stories que expiraron (más de 24 horas)
    $query = "DELETE FROM stories WHERE expires_at < NOW()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    // Eliminar notificaciones antiguas (más de 30 días)
    $query = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($query);
    $stmt->execute();
}

// Ejecutar limpieza ocasionalmente (1% de probabilidad)
if(mt_rand(1, 100) === 1) {
    cleanupOldData();
}
?>
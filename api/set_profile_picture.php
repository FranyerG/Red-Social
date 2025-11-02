<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['photo_id'])) {
    $user_id = $_SESSION['user_id'];
    $photo_id = $_GET['photo_id'];
    
    // Verificar que la foto pertenece al usuario
    $query = "SELECT filename FROM photos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$photo_id, $user_id]);
    $photo = $stmt->fetch();
    
    if ($photo) {
        // Actualizar foto de perfil del usuario
        $update_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$photo['filename'], $user_id]);
        
        // Actualizar sesión
        $_SESSION['profile_picture'] = $photo['filename'];
        $_SESSION['success'] = "Foto de perfil actualizada correctamente";
    } else {
        $_SESSION['error'] = "Foto no encontrada";
    }
} else {
    $_SESSION['error'] = "ID de foto no especificado";
}

redirect('../pages/fotos.php');
?>
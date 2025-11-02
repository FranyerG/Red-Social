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
        // Eliminar archivo físico
        $filepath = '../assets/uploads/photos/' . $photo['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Eliminar de la base de datos
        $delete_query = "DELETE FROM photos WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([$photo_id]);
        
        $_SESSION['success'] = "Foto eliminada correctamente";
    } else {
        $_SESSION['error'] = "Foto no encontrada";
    }
} else {
    $_SESSION['error'] = "ID de foto no especificado";
}

redirect('../pages/fotos.php');
?>
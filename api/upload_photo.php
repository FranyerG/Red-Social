<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if(!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$description = $_POST['description'] ?? '';

// Crear directorio si no existe
$upload_dir = '../assets/uploads/photos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Verificar tipo de archivo
    $file_info = getimagesize($file['tmp_name']);
    $mime_type = $file_info['mime'];
    
    if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) && $file['size'] <= $max_size) {
        
        // Generar nombre único para el archivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . $user_id . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            try {
                // Guardar en base de datos
                $query = "INSERT INTO photos (user_id, filename, description) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$user_id, $filename, $description]);
                
                $_SESSION['success'] = "Foto subida correctamente";
                
                // Si es la primera foto del usuario, establecerla como foto de perfil automáticamente
                $count_query = "SELECT COUNT(*) as total FROM photos WHERE user_id = ?";
                $count_stmt = $conn->prepare($count_query);
                $count_stmt->execute([$user_id]);
                $count = $count_stmt->fetch()['total'];
                
                if ($count == 1) {
                    $update_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([$filename, $user_id]);
                    $_SESSION['profile_picture'] = $filename;
                    $_SESSION['success'] = "Foto subida y establecida como foto de perfil";
                }
                
            } catch (PDOException $e) {
                // Si hay error en la BD, eliminar el archivo subido
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                $_SESSION['error'] = "Error al guardar la foto en la base de datos";
            }
        } else {
            $_SESSION['error'] = "Error al mover el archivo subido";
        }
    } else {
        $_SESSION['error'] = "Tipo de archivo no permitido o tamaño excedido (máx. 5MB). Formatos permitidos: JPG, PNG, GIF, WEBP";
    }
} else {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_PARTIAL => 'El archivo fue solo parcialmente subido',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
    ];
    
    $error_code = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $_SESSION['error'] = $error_messages[$error_code] ?? 'Error desconocido al subir el archivo';
}

redirect('../pages/fotos.php');
?>
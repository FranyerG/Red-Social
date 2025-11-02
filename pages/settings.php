<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener información del usuario
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';

// Procesar actualización de perfil
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $bio = sanitize($_POST['bio']);
    $profile_picture = $user['profile_picture'];
    $cover_picture = $user['cover_picture'];
    
    // Subir nueva imagen de perfil
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $new_profile_picture = uploadImage($_FILES['profile_picture'], '../assets/uploads/');
        if($new_profile_picture) {
            $profile_picture = $new_profile_picture;
        }
    }
    
    // Subir nueva imagen de portada
    if(isset($_FILES['cover_picture']) && $_FILES['cover_picture']['error'] == 0) {
        $new_cover_picture = uploadImage($_FILES['cover_picture'], '../assets/uploads/');
        if($new_cover_picture) {
            $cover_picture = $new_cover_picture;
        }
    }
    
    $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, 
              bio = :bio, profile_picture = :profile_picture, cover_picture = :cover_picture 
              WHERE id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':bio', $bio);
    $stmt->bindParam(':profile_picture', $profile_picture);
    $stmt->bindParam(':cover_picture', $cover_picture);
    $stmt->bindParam(':user_id', $user_id);
    
    if($stmt->execute()) {
        $message = '<div class="alert alert-success">Perfil actualizado correctamente</div>';
        // Actualizar sesión
        $_SESSION['profile_picture'] = $profile_picture;
        // Recargar datos del usuario
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = '<div class="alert alert-danger">Error al actualizar el perfil</div>';
    }
}

// Procesar cambio de contraseña
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verificar contraseña actual
    if(!password_verify($current_password, $user['password'])) {
        $message = '<div class="alert alert-danger">La contraseña actual es incorrecta</div>';
    } elseif($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Las nuevas contraseñas no coinciden</div>';
    } elseif(strlen($new_password) < 6) {
        $message = '<div class="alert alert-danger">La nueva contraseña debe tener al menos 6 caracteres</div>';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = :password WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);
        
        if($stmt->execute()) {
            $message = '<div class="alert alert-success">Contraseña actualizada correctamente</div>';
        } else {
            $message = '<div class="alert alert-danger">Error al actualizar la contraseña</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-3">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Configuración de Cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <!-- Pestañas -->
                        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" 
                                        data-bs-target="#profile" type="button" role="tab">
                                    Perfil
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="password-tab" data-bs-toggle="tab" 
                                        data-bs-target="#password" type="button" role="tab">
                                    Contraseña
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="settingsTabsContent">
                            <!-- Pestaña Perfil -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">Nombre</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Apellido</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Biografía</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="profile_picture" class="form-label">Foto de Perfil</label>
                                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                                <?php if($user['profile_picture']): ?>
                                                    <div class="mt-2">
                                                        <img src="../assets/uploads/<?php echo $user['profile_picture']; ?>" 
                                                             class="rounded-circle" width="80" height="80" alt="Current profile">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cover_picture" class="form-label">Foto de Portada</label>
                                                <input type="file" class="form-control" id="cover_picture" name="cover_picture" accept="image/*">
                                                <?php if($user['cover_picture']): ?>
                                                    <div class="mt-2">
                                                        <img src="../assets/uploads/<?php echo $user['cover_picture']; ?>" 
                                                             class="rounded" width="100" height="60" alt="Current cover" style="object-fit: cover;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                                </form>
                            </div>
                            
                            <!-- Pestaña Contraseña -->
                            <div class="tab-pane fade" id="password" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="change_password" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
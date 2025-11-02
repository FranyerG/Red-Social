<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Obtener configuración actual
$query = "SELECT * FROM privacy_settings WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$privacy_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe, crear configuración por defecto
if(!$privacy_settings) {
    $query = "INSERT INTO privacy_settings (user_id) VALUES (:user_id)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $privacy_settings = [
        'profile_visibility' => 'public',
        'can_send_friend_requests' => 'everyone',
        'can_see_friends_list' => 'public',
        'can_post_on_timeline' => 'friends'
    ];
}

// Procesar formulario
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profile_visibility = $_POST['profile_visibility'];
    $can_send_friend_requests = $_POST['can_send_friend_requests'];
    $can_see_friends_list = $_POST['can_see_friends_list'];
    $can_post_on_timeline = $_POST['can_post_on_timeline'];
    
    $query = "UPDATE privacy_settings SET 
              profile_visibility = :profile_visibility,
              can_send_friend_requests = :can_send_friend_requests,
              can_see_friends_list = :can_see_friends_list,
              can_post_on_timeline = :can_post_on_timeline
              WHERE user_id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':profile_visibility', $profile_visibility);
    $stmt->bindParam(':can_send_friend_requests', $can_send_friend_requests);
    $stmt->bindParam(':can_see_friends_list', $can_see_friends_list);
    $stmt->bindParam(':can_post_on_timeline', $can_post_on_timeline);
    $stmt->bindParam(':user_id', $user_id);
    
    if($stmt->execute()) {
        $message = '<div class="alert alert-success">Configuración actualizada correctamente</div>';
        // Actualizar variable local
        $privacy_settings = [
            'profile_visibility' => $profile_visibility,
            'can_send_friend_requests' => $can_send_friend_requests,
            'can_see_friends_list' => $can_see_friends_list,
            'can_post_on_timeline' => $can_post_on_timeline
        ];
    } else {
        $message = '<div class="alert alert-danger">Error al actualizar la configuración</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacidad - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Incluir navbar -->
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-3">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Configuración de Privacidad</h4>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <h5>Visibilidad del Perfil</h5>
                                <p class="text-muted">¿Quién puede ver tu perfil?</p>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="profile_visibility" 
                                           id="profile_public" value="public" 
                                           <?php echo $privacy_settings['profile_visibility'] == 'public' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="profile_public">
                                        <strong>Público</strong> - Todos pueden ver tu perfil
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="profile_visibility" 
                                           id="profile_friends" value="friends" 
                                           <?php echo $privacy_settings['profile_visibility'] == 'friends' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="profile_friends">
                                        <strong>Solo Amigos</strong> - Solo tus amigos pueden ver tu perfil
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="profile_visibility" 
                                           id="profile_private" value="private" 
                                           <?php echo $privacy_settings['profile_visibility'] == 'private' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="profile_private">
                                        <strong>Privado</strong> - Solo tú puedes ver tu perfil
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Solicitudes de Amistad</h5>
                                <p class="text-muted">¿Quién puede enviarte solicitudes de amistad?</p>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_send_friend_requests" 
                                           id="requests_everyone" value="everyone" 
                                           <?php echo $privacy_settings['can_send_friend_requests'] == 'everyone' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="requests_everyone">
                                        <strong>Todos</strong> - Cualquier persona puede enviarte solicitudes
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_send_friend_requests" 
                                           id="requests_friends_of_friends" value="friends_of_friends" 
                                           <?php echo $privacy_settings['can_send_friend_requests'] == 'friends_of_friends' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="requests_friends_of_friends">
                                        <strong>Amigos de Amigos</strong> - Solo amigos de tus amigos pueden enviarte solicitudes
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_send_friend_requests" 
                                           id="requests_no_one" value="no_one" 
                                           <?php echo $privacy_settings['can_send_friend_requests'] == 'no_one' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="requests_no_one">
                                        <strong>Nadie</strong> - No aceptar nuevas solicitudes de amistad
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Lista de Amigos</h5>
                                <p class="text-muted">¿Quién puede ver tu lista de amigos?</p>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_see_friends_list" 
                                           id="friends_public" value="public" 
                                           <?php echo $privacy_settings['can_see_friends_list'] == 'public' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="friends_public">
                                        <strong>Público</strong> - Todos pueden ver tu lista de amigos
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_see_friends_list" 
                                           id="friends_friends" value="friends" 
                                           <?php echo $privacy_settings['can_see_friends_list'] == 'friends' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="friends_friends">
                                        <strong>Solo Amigos</strong> - Solo tus amigos pueden ver tu lista de amigos
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_see_friends_list" 
                                           id="friends_private" value="private" 
                                           <?php echo $privacy_settings['can_see_friends_list'] == 'private' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="friends_private">
                                        <strong>Privado</strong> - Solo tú puedes ver tu lista de amigos
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Publicaciones en tu Muro</h5>
                                <p class="text-muted">¿Quién puede publicar en tu muro?</p>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_post_on_timeline" 
                                           id="post_everyone" value="everyone" 
                                           <?php echo $privacy_settings['can_post_on_timeline'] == 'everyone' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="post_everyone">
                                        <strong>Todos</strong> - Cualquier persona puede publicar en tu muro
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_post_on_timeline" 
                                           id="post_friends" value="friends" 
                                           <?php echo $privacy_settings['can_post_on_timeline'] == 'friends' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="post_friends">
                                        <strong>Solo Amigos</strong> - Solo tus amigos pueden publicar en tu muro
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="can_post_on_timeline" 
                                           id="post_only_me" value="only_me" 
                                           <?php echo $privacy_settings['can_post_on_timeline'] == 'only_me' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="post_only_me">
                                        <strong>Solo Yo</strong> - Solo tú puedes publicar en tu muro
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
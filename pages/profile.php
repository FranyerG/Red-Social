<?php
// profile.php - Página de perfil de usuario
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticación
if (!isLoggedIn()) {
    redirect('login.php');
}

// Configuración inicial
$db = new Database();
$conn = $db->getConnection();
$current_user_id = $_SESSION['user_id'];

// Determinar qué perfil mostrar
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
$is_own_profile = ($profile_user_id === $current_user_id);

// Obtener información del usuario del perfil
$user_query = "SELECT id, username, first_name, last_name, email, bio, profile_picture, cover_picture, created_at 
               FROM users 
               WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$profile_user_id]);

if ($user_stmt->rowCount() === 0) {
    showMessage('Usuario no encontrado', 'error');
    redirect('dashboard.php');
}

$profile_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Verificar estado de amistad
$friendship_status = null;
if (!$is_own_profile) {
    $friendship_query = "SELECT status, user_id 
                         FROM friendships 
                         WHERE (user_id = ? AND friend_id = ?) 
                            OR (user_id = ? AND friend_id = ?)";
    $friendship_stmt = $conn->prepare($friendship_query);
    $friendship_stmt->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
    
    if ($friendship_stmt->rowCount() > 0) {
        $friendship_data = $friendship_stmt->fetch(PDO::FETCH_ASSOC);
        $friendship_status = $friendship_data['status'];
    }
}

// Procesar acciones de amistad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_own_profile) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_friend_request':
            if (!$friendship_status) {
                $insert_query = "INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->execute([$current_user_id, $profile_user_id]);
                
                showMessage('Solicitud de amistad enviada', 'success');
                $friendship_status = 'pending';
            }
            break;
            
        case 'accept_friend_request':
            $accept_query = "UPDATE friendships SET status = 'accepted' 
                            WHERE user_id = ? AND friend_id = ? AND status = 'pending'";
            $accept_stmt = $conn->prepare($accept_query);
            $accept_stmt->execute([$profile_user_id, $current_user_id]);
            
            if ($accept_stmt->rowCount() > 0) {
                showMessage('Solicitud de amistad aceptada', 'success');
                $friendship_status = 'accepted';
            }
            break;
            
        case 'cancel_friend_request':
        case 'remove_friend':
            $delete_query = "DELETE FROM friendships 
                            WHERE (user_id = ? AND friend_id = ?) 
                               OR (user_id = ? AND friend_id = ?)";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
            
            $message = $action === 'cancel_friend_request' ? 'Solicitud cancelada' : 'Amigo eliminado';
            showMessage($message, 'success');
            $friendship_status = null;
            break;
    }
    
    // Redirigir para evitar reenvío de formulario
    redirect("profile.php?user_id=" . $profile_user_id);
}

// Obtener estadísticas del usuario
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM posts WHERE user_id = ?) as post_count,
    (SELECT COUNT(*) FROM friendships WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted') as friend_count,
    (SELECT COUNT(*) FROM likes WHERE user_id = ?) as like_count";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$profile_user_id, $profile_user_id, $profile_user_id, $profile_user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener publicaciones del usuario
$posts_query = "SELECT p.*, u.username, u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT 10";
$posts_stmt = $conn->prepare($posts_query);
$posts_stmt->execute([$current_user_id, $profile_user_id]);
$user_posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener amigos recientes
$friends_query = "SELECT u.id, u.username, u.profile_picture 
                  FROM users u 
                  WHERE u.id IN (
                      SELECT CASE 
                                 WHEN f.user_id = ? THEN f.friend_id 
                                 ELSE f.user_id 
                             END as friend_id
                      FROM friendships f 
                      WHERE (f.user_id = ? OR f.friend_id = ?) 
                        AND f.status = 'accepted'
                  )
                  ORDER BY u.username 
                  LIMIT 9";
$friends_stmt = $conn->prepare($friends_query);
$friends_stmt->execute([$profile_user_id, $profile_user_id, $profile_user_id]);
$friends = $friends_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['username']); ?> - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .profile-cover {
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: cover;
            background-position: center;
            position: relative;
            border-radius: 0 0 15px 15px;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border: 5px solid white;
            border-radius: 50%;
            position: absolute;
            bottom: -75px;
            left: 50px;
            background: white;
        }
        
        .profile-info {
            margin-top: 90px;
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .friend-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .profile-picture {
                width: 120px;
                height: 120px;
                bottom: -60px;
                left: 20px;
            }
            
            .profile-info {
                margin-top: 70px;
            }
            
            .profile-cover {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Mostrar mensajes flash -->
        <?php
        $flash_message = getFlashMessage();
        if($flash_message): ?>
            <div class="alert alert-<?php echo $flash_message['type']; ?> alert-dismissible fade show">
                <?php echo $flash_message['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Portada y Foto de Perfil -->
        <div class="card mb-4">
            <div class="profile-cover" style="background-image: url('../assets/uploads/<?php echo $profile_user['cover_picture'] ?: 'default-cover.jpg'; ?>')">
                <img src="../assets/uploads/<?php echo $profile_user['profile_picture'] ?: 'default.jpg'; ?>" 
                     class="profile-picture" 
                     alt="<?php echo htmlspecialchars($profile_user['username']); ?>"
                     onerror="this.src='../assets/uploads/default.jpg'">
            </div>
            
            <div class="card-body profile-info">
                <div class="row">
                    <div class="col-md-8">
                        <h1 class="h3 mb-1">
                            <?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>
                        </h1>
                        <p class="text-muted mb-2">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                        
                        <?php if($profile_user['bio']): ?>
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-3 text-muted small">
                            <span><i class="fas fa-calendar me-1"></i> Se unió <?php echo formatDate($profile_user['created_at']); ?></span>
                            <?php if($profile_user['email'] && $is_own_profile): ?>
                                <span><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($profile_user['email']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
<div class="col-md-4 text-md-end">
    <?php if($is_own_profile): ?>
        <a href="settings.php" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i> Editar Perfil
        </a>
    <?php else: ?>
        <div class="btn-group">
            <?php if($friendship_status === 'accepted'): ?>
                <button class="btn btn-success" disabled>
                    <i class="fas fa-check me-1"></i> Amigos
                </button>
                <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="visually-hidden">Opciones de amistad</span>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="remove_friend">
                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('¿Eliminar amigo?')">
                                <i class="fas fa-user-times me-2"></i> Eliminar Amigo
                            </button>
                        </form>
                    </li>
                </ul>
            <?php elseif($friendship_status === 'pending'): ?>
                <?php
                // Determinar quién envió la solicitud - CORREGIDO
                $query = "SELECT user_id FROM friendships 
                          WHERE ((user_id = ? AND friend_id = ?) 
                             OR (user_id = ? AND friend_id = ?)) 
                            AND status = 'pending'";
                $stmt = $conn->prepare($query);
                $stmt->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                $is_request_sent_by_me = ($request['user_id'] == $current_user_id);
                ?>
                
                <?php if($is_request_sent_by_me): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="cancel_friend_request">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-clock me-1"></i> Solicitud Enviada
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="accept_friend_request">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Aceptar Solicitud
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="send_friend_request">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Agregar Amigo
                    </button>
                </form>
            <?php endif; ?>
            
            <button class="btn btn-outline-secondary">
                <i class="fas fa-envelope me-1"></i> Mensaje
            </button>
        </div>
    <?php endif; ?>
</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna Izquierda - Información y Amigos -->
            <div class="col-lg-4">
                <!-- Estadísticas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stats-card">
                                    <h4 class="text-primary mb-1"><?php echo $stats['post_count']; ?></h4>
                                    <small class="text-muted">Publicaciones</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-card">
                                    <h4 class="text-success mb-1"><?php echo $stats['friend_count']; ?></h4>
                                    <small class="text-muted">Amigos</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-card">
                                    <h4 class="text-warning mb-1"><?php echo $stats['like_count']; ?></h4>
                                    <small class="text-muted">Likes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Amigos -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Amigos</h5>
                        <small class="text-muted"><?php echo $stats['friend_count']; ?> amigos</small>
                    </div>
                    <div class="card-body">
                        <?php if(count($friends) > 0): ?>
                            <div class="row g-2">
                                <?php foreach($friends as $friend): ?>
                                    <div class="col-4 text-center">
                                        <a href="profile.php?user_id=<?php echo $friend['id']; ?>" class="text-decoration-none">
                                            <img src="../assets/uploads/<?php echo $friend['profile_picture'] ?: 'default.jpg'; ?>" 
                                                 class="friend-avatar mb-1"
                                                 alt="<?php echo htmlspecialchars($friend['username']); ?>"
                                                 onerror="this.src='../assets/uploads/default.jpg'">
                                            <small class="d-block text-truncate"><?php echo htmlspecialchars($friend['username']); ?></small>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if($stats['friend_count'] > 9): ?>
                                <div class="text-center mt-3">
                                    <a href="friends.php?user_id=<?php echo $profile_user_id; ?>" class="btn btn-sm btn-outline-primary">
                                        Ver todos los amigos
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">No hay amigos para mostrar</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Información</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong><i class="fas fa-user me-2 text-muted"></i> Nombre completo</strong>
                            <p class="mb-0"><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?></p>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fas fa-at me-2 text-muted"></i> Usuario</strong>
                            <p class="mb-0">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                        </div>
                        <?php if($is_own_profile || $friendship_status === 'accepted'): ?>
                            <div class="mb-3">
                                <strong><i class="fas fa-envelope me-2 text-muted"></i> Email</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($profile_user['email']); ?></p>
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong><i class="fas fa-calendar me-2 text-muted"></i> Miembro desde</strong>
                            <p class="mb-0"><?php echo date('d M Y', strtotime($profile_user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha - Publicaciones -->
            <div class="col-lg-8">
                <?php if($is_own_profile): ?>
                    <!-- Crear Post (solo en perfil propio) -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form id="postForm">
                                <div class="d-flex align-items-start">
                                    <img src="../assets/uploads/<?php echo $_SESSION['profile_picture'] ?? 'default.jpg'; ?>" 
                                         class="rounded-circle me-3" 
                                         width="45" 
                                         height="45"
                                         onerror="this.src='../assets/uploads/default.jpg'">
                                    <div class="flex-grow-1">
                                        <textarea class="form-control mb-2" 
                                                  id="postContent" 
                                                  rows="3" 
                                                  placeholder="¿Qué estás pensando, <?php echo htmlspecialchars($profile_user['first_name']); ?>?"></textarea>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <input type="file" id="postImage" accept="image/*" class="d-none">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('postImage').click()">
                                                    <i class="fas fa-image me-1"></i> Foto
                                                </button>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Publicar</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Publicaciones -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <?php echo $is_own_profile ? 'Tus Publicaciones' : 'Publicaciones'; ?>
                            <?php if(count($user_posts) > 0): ?>
                                <span class="badge bg-primary ms-2"><?php echo count($user_posts); ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($user_posts) > 0): ?>
                            <?php foreach($user_posts as $post): ?>
                                <div class="post-card card">
                                    <div class="card-body">
                                        <!-- Encabezado del post -->
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="../assets/uploads/<?php echo $post['profile_picture']; ?>" 
                                                 class="rounded-circle me-3" 
                                                 width="45" 
                                                 height="45"
                                                 onerror="this.src='../assets/uploads/default.jpg'">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                                                <small class="text-muted"><?php echo formatDate($post['created_at']); ?></small>
                                            </div>
                                        </div>
                                        
                                        <!-- Contenido del post -->
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                        
                                        <!-- Imagen del post -->
                                        <?php if($post['image']): ?>
                                            <div class="post-image mb-3">
                                                <img src="../assets/uploads/<?php echo $post['image']; ?>" 
                                                     class="img-fluid rounded" 
                                                     alt="Imagen del post"
                                                     style="max-height: 400px; object-fit: cover;">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Estadísticas -->
                                        <div class="post-stats mb-2">
                                            <small class="text-muted">
                                                <span><?php echo $post['like_count']; ?> me gusta</span> • 
                                                <span><?php echo $post['comment_count']; ?> comentarios</span>
                                            </small>
                                        </div>
                                        
                                        <!-- Acciones -->
                                        <div class="post-actions border-top pt-2">
                                            <div class="d-flex justify-content-around">
                                                <button class="btn btn-outline-primary btn-sm <?php echo $post['user_liked'] ? 'active' : ''; ?>">
                                                    <i class="fas fa-thumbs-up me-1"></i> Me gusta
                                                </button>
                                                <button class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-comment me-1"></i> Comentar
                                                </button>
                                                <button class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-share me-1"></i> Compartir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-newspaper fa-3x mb-3"></i>
                                <h5>No hay publicaciones</h5>
                                <p><?php echo $is_own_profile ? 'Comparte algo con tus amigos.' : 'Este usuario no ha publicado nada aún.'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Configuración global del usuario
        window.currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            username: '<?php echo $_SESSION['username']; ?>'
        };

        // Cargar comentarios para posts que los tengan
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach($user_posts as $post): ?>
                <?php if($post['comment_count'] > 0): ?>
                    loadComments(<?php echo $post['id']; ?>);
                <?php endif; ?>
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
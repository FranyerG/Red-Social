<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_POST['action'] ?? $input['action'] ?? '';
    
    switch($action) {
        case 'create_post':
            $content = sanitize($_POST['content']);
            
            if(empty(trim($content))) {
                echo json_encode(['success' => false, 'message' => 'El post no puede estar vacío']);
                exit;
            }
            
            $image = null;
            
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = uploadImage($_FILES['image'], '../assets/uploads/');
                if(!$image) {
                    echo json_encode(['success' => false, 'message' => 'Error al subir imagen']);
                    exit;
                }
            }
            
            $query = "INSERT INTO posts (user_id, content, image) VALUES (:user_id, :content, :image)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':image', $image);
            
            if($stmt->execute()) {
                $post_id = $conn->lastInsertId();
                
                // Obtener el post recién creado con toda la información
                $query = "SELECT p.*, 
                                 u.username, 
                                 u.profile_picture,
                                 (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
                                 (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
                                 EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = :user_id) as user_liked
                          FROM posts p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE p.id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Post creado exitosamente',
                    'post' => $post
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear post']);
            }
            break;
            
        case 'like_post':
            $post_id = $input['post_id'];
            
            // Verificar si el post existe
            $query = "SELECT id FROM posts WHERE id = :post_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => 'El post no existe']);
                exit;
            }
            
            // Verificar si ya dio like
            $query = "SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                // Quitar like
                $query = "DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                
                // Obtener nuevo conteo de likes
                $query = "SELECT COUNT(*) as like_count FROM likes WHERE post_id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];
                
                echo json_encode([
                    'success' => true, 
                    'liked' => false,
                    'like_count' => (int)$like_count
                ]);
            } else {
                // Dar like
                $query = "INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                
                // Obtener nuevo conteo de likes
                $query = "SELECT COUNT(*) as like_count FROM likes WHERE post_id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];
                
                // Crear notificación para el dueño del post (si no es el mismo usuario)
                $query = "SELECT user_id FROM posts WHERE id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                $post_owner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($post_owner['user_id'] != $user_id) {
                    require_once 'notifications.php';
                    $from_user = $_SESSION['username'];
                    createNotification(
                        $post_owner['user_id'],
                        $user_id,
                        'post_like',
                        "A {$from_user} le gusta tu publicación",
                        $post_id
                    );
                }
                
                echo json_encode([
                    'success' => true, 
                    'liked' => true,
                    'like_count' => (int)$like_count
                ]);
            }
            break;
            
        case 'delete_post':
            $post_id = $input['post_id'];
            
            // Verificar que el post pertenece al usuario
            $query = "SELECT id, image FROM posts WHERE id = :post_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Eliminar imagen si existe
                if($post['image']) {
                    $image_path = '../assets/uploads/' . $post['image'];
                    if(file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                // Eliminar likes asociados
                $query = "DELETE FROM likes WHERE post_id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                
                // Eliminar comentarios asociados
                $query = "DELETE FROM comments WHERE post_id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                
                // Eliminar notificaciones asociadas
                $query = "DELETE FROM notifications WHERE reference_id = :post_id AND type IN ('post_like', 'post_comment')";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                
                // Finalmente eliminar el post
                $query = "DELETE FROM posts WHERE id = :post_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Post eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este post']);
            }
            break;
            
        case 'get_post':
            $post_id = $input['post_id'] ?? $_GET['post_id'] ?? null;
            
            if(!$post_id) {
                echo json_encode(['success' => false, 'message' => 'ID de post no especificado']);
                exit;
            }
            
            $query = "SELECT p.*, 
                             u.username, 
                             u.profile_picture,
                             (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
                             (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
                             EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = :user_id) as user_liked
                      FROM posts p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.id = :post_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'post' => $post]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Post no encontrado']);
            }
            break;
    }
} elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get_posts':
            $page = $_GET['page'] ?? 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            // Obtener posts con toda la información
            $query = "SELECT p.*, 
                             u.username, 
                             u.profile_picture,
                             (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
                             (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
                             EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = :user_id) as user_liked
                      FROM posts p 
                      JOIN users u ON p.user_id = u.id 
                      ORDER BY p.created_at DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total de posts para paginación
            $query = "SELECT COUNT(*) as total FROM posts";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true, 
                'posts' => $posts,
                'pagination' => [
                    'current_page' => (int)$page,
                    'total_pages' => ceil($total / $limit),
                    'total_posts' => (int)$total
                ]
            ]);
            break;
            
        case 'get_user_posts':
            $target_user_id = $_GET['user_id'] ?? $user_id;
            $page = $_GET['page'] ?? 1;
            $limit = 15;
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT p.*, 
                             u.username, 
                             u.profile_picture,
                             (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
                             (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
                             EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = :current_user_id) as user_liked
                      FROM posts p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.user_id = :target_user_id
                      ORDER BY p.created_at DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':current_user_id', $user_id);
            $stmt->bindParam(':target_user_id', $target_user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'posts' => $posts]);
            break;
            
        case 'get_popular_posts':
            $limit = $_GET['limit'] ?? 10;
            
            $query = "SELECT p.*, 
                             u.username, 
                             u.profile_picture,
                             (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
                             (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count,
                             EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = :user_id) as user_liked,
                             ((SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) * 2 + 
                              (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id)) as popularity
                      FROM posts p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      ORDER BY popularity DESC, p.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'posts' => $posts]);
            break;
    }
}
?>
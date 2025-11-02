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
        case 'add_comment':
            $post_id = $input['post_id'];
            $content = sanitize($input['content']);
            
            if(empty($content)) {
                echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío']);
                exit;
            }
            
            $query = "INSERT INTO comments (user_id, post_id, content) VALUES (:user_id, :post_id, :content)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':content', $content);
            
            if($stmt->execute()) {
                $comment_id = $conn->lastInsertId();
                
                // Obtener el comentario con información del usuario
                $query = "SELECT c.*, u.username, u.profile_picture 
                         FROM comments c 
                         JOIN users u ON c.user_id = u.id 
                         WHERE c.id = :comment_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':comment_id', $comment_id);
                $stmt->execute();
                $comment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'comment' => $comment]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agregar comentario']);
            }
            break;
            
        case 'get_comments':
            $post_id = $input['post_id'];
            
            $query = "SELECT c.*, u.username, u.profile_picture 
                     FROM comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.post_id = :post_id 
                     ORDER BY c.created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->execute();
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'comments' => $comments]);
            break;
            
        case 'delete_comment':
            $comment_id = $input['comment_id'];
            
            // Verificar que el comentario pertenece al usuario
            $query = "SELECT id FROM comments WHERE id = :comment_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $query = "DELETE FROM comments WHERE id = :comment_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':comment_id', $comment_id);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
            }
            break;
    }
}
?>
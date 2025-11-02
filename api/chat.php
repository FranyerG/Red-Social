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
        case 'send_message':
            $to_user_id = $input['to_user_id'];
            $message = sanitize($input['message']);
            
            if(empty($message)) {
                echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
                exit;
            }
            
            // Verificar que son amigos
            $query = "SELECT id FROM friendships 
                      WHERE ((user_id = :user_id AND friend_id = :friend_id) 
                         OR (user_id = :friend_id AND friend_id = :user_id)) 
                        AND status = 'accepted'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':friend_id', $to_user_id);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => 'Solo puedes enviar mensajes a tus amigos']);
                exit;
            }
            
            // Insertar mensaje
            $query = "INSERT INTO messages (from_user_id, to_user_id, message) 
                      VALUES (:from_user_id, :to_user_id, :message)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':from_user_id', $user_id);
            $stmt->bindParam(':to_user_id', $to_user_id);
            $stmt->bindParam(':message', $message);
            
            if($stmt->execute()) {
                $message_id = $conn->lastInsertId();
                
                // Obtener el mensaje completo
                $query = "SELECT m.*, u.username as from_username, u.profile_picture as from_profile_picture
                          FROM messages m 
                          JOIN users u ON m.from_user_id = u.id 
                          WHERE m.id = :message_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':message_id', $message_id);
                $stmt->execute();
                $message_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'message' => $message_data,
                    'message_id' => $message_id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje']);
            }
            break;
            
        case 'mark_conversation_read':
            $friend_id = $input['friend_id'];
            
            $query = "UPDATE messages SET is_read = TRUE, read_at = NOW() 
                      WHERE from_user_id = :friend_id AND to_user_id = :user_id AND is_read = FALSE";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':friend_id', $friend_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
            break;
            
        case 'delete_message':
            $message_id = $input['message_id'];
            
            // Verificar que el mensaje pertenece al usuario
            $query = "SELECT id FROM messages WHERE id = :message_id AND from_user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':message_id', $message_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $query = "UPDATE messages SET is_deleted = TRUE, deleted_at = NOW() 
                          WHERE id = :message_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':message_id', $message_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Mensaje eliminado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No puedes eliminar este mensaje']);
            }
            break;
            
        case 'clear_conversation':
            $friend_id = $input['friend_id'];
            
            // Marcar todos los mensajes como eliminados para el usuario
            $query = "UPDATE messages SET is_deleted = TRUE, deleted_at = NOW() 
                      WHERE (from_user_id = :user_id AND to_user_id = :friend_id) 
                         OR (from_user_id = :friend_id AND to_user_id = :user_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':friend_id', $friend_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
            break;
    }
} elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get_messages':
            $friend_id = $_GET['friend_id'];
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            // Obtener mensajes de la conversación
            $query = "SELECT m.*, 
                             u_from.username as from_username, 
                             u_from.profile_picture as from_profile_picture,
                             u_to.username as to_username,
                             u_to.profile_picture as to_profile_picture
                      FROM messages m 
                      JOIN users u_from ON m.from_user_id = u_from.id 
                      JOIN users u_to ON m.to_user_id = u_to.id 
                      WHERE ((m.from_user_id = :user_id AND m.to_user_id = :friend_id) 
                         OR (m.from_user_id = :friend_id AND m.to_user_id = :user_id))
                        AND m.is_deleted = FALSE
                      ORDER BY m.created_at ASC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':friend_id', $friend_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        case 'get_friends':
            // Obtener amigos para chat con información adicional
            $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_picture,
                             (SELECT COUNT(*) FROM messages m WHERE m.from_user_id = u.id AND m.to_user_id = :user_id AND m.is_read = FALSE) as unread_count,
                             (SELECT MAX(created_at) FROM messages 
                              WHERE ((from_user_id = :user_id AND to_user_id = u.id) 
                                 OR (from_user_id = u.id AND to_user_id = :user_id))
                                AND is_deleted = FALSE) as last_message_time
                      FROM users u 
                      WHERE u.id IN (
                          SELECT CASE 
                                     WHEN f.user_id = :user_id THEN f.friend_id 
                                     ELSE f.user_id 
                                 END as friend_id
                          FROM friendships f 
                          WHERE (f.user_id = :user_id OR f.friend_id = :user_id) 
                            AND f.status = 'accepted'
                      )
                      ORDER BY last_message_time DESC NULLS LAST, u.username";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'friends' => $friends]);
            break;
            
        case 'get_conversations':
            // Obtener conversaciones recientes (último mensaje de cada conversación)
            $query = "SELECT m.*, 
                             u.username as other_username,
                             u.first_name as other_first_name,
                             u.last_name as other_last_name,
                             u.profile_picture as other_profile_picture,
                             (SELECT COUNT(*) FROM messages m2 
                              WHERE m2.from_user_id = u.id AND m2.to_user_id = :user_id 
                                AND m2.is_read = FALSE) as unread_count
                      FROM messages m
                      INNER JOIN users u ON (
                          CASE 
                              WHEN m.from_user_id = :user_id THEN m.to_user_id 
                              ELSE m.from_user_id 
                          END = u.id
                      )
                      WHERE m.id IN (
                          SELECT MAX(m2.id) 
                          FROM messages m2 
                          WHERE (m2.from_user_id = :user_id OR m2.to_user_id = :user_id)
                            AND m2.is_deleted = FALSE
                          GROUP BY 
                              CASE 
                                  WHEN m2.from_user_id = :user_id THEN m2.to_user_id 
                                  ELSE m2.from_user_id 
                              END
                      )
                      ORDER BY m.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;
            
        case 'get_unread_counts':
            $query = "SELECT from_user_id as friend_id, COUNT(*) as unread_count 
                      FROM messages 
                      WHERE to_user_id = :user_id AND is_read = FALSE 
                      GROUP BY from_user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $unread_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'unread_counts' => $unread_counts]);
            break;
            
        case 'get_friend_info':
            $friend_id = $_GET['friend_id'];
            
            $query = "SELECT id, username, first_name, last_name, profile_picture 
                      FROM users WHERE id = :friend_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':friend_id', $friend_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $friend = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'friend' => $friend]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Amigo no encontrado']);
            }
            break;
            
        case 'search_messages':
            $search_term = $_GET['q'] ?? '';
            $friend_id = $_GET['friend_id'] ?? null;
            
            if(empty($search_term)) {
                echo json_encode(['success' => false, 'message' => 'Término de búsqueda vacío']);
                exit;
            }
            
            $search_pattern = "%$search_term%";
            
            $query = "SELECT m.*, 
                             u_from.username as from_username,
                             u_to.username as to_username
                      FROM messages m 
                      JOIN users u_from ON m.from_user_id = u_from.id 
                      JOIN users u_to ON m.to_user_id = u_to.id 
                      WHERE m.message LIKE :search_term 
                        AND m.is_deleted = FALSE
                        AND (m.from_user_id = :user_id OR m.to_user_id = :user_id)";
            
            if($friend_id) {
                $query .= " AND ((m.from_user_id = :user_id AND m.to_user_id = :friend_id) 
                             OR (m.from_user_id = :friend_id AND m.to_user_id = :user_id))";
            }
            
            $query .= " ORDER BY m.created_at DESC LIMIT 50";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':search_term', $search_pattern);
            $stmt->bindParam(':user_id', $user_id);
            
            if($friend_id) {
                $stmt->bindParam(':friend_id', $friend_id);
            }
            
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        case 'get_message_statistics':
            // Estadísticas de mensajes
            $query = "SELECT 
                         COUNT(*) as total_messages,
                         SUM(CASE WHEN from_user_id = :user_id THEN 1 ELSE 0 END) as sent_messages,
                         SUM(CASE WHEN to_user_id = :user_id THEN 1 ELSE 0 END) as received_messages,
                         SUM(CASE WHEN to_user_id = :user_id AND is_read = FALSE THEN 1 ELSE 0 END) as unread_messages,
                         COUNT(DISTINCT 
                             CASE 
                                 WHEN from_user_id = :user_id THEN to_user_id 
                                 ELSE from_user_id 
                             END
                         ) as active_conversations
                      FROM messages 
                      WHERE (from_user_id = :user_id OR to_user_id = :user_id) 
                        AND is_deleted = FALSE";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;
    }
}
?>
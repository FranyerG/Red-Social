<?php
session_start();
header('Content-Type: application/json');

// Configuración para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Obtener datos JSON
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';
        
        if (empty($action)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
            exit;
        }
        
        switch($action) {
            case 'send_friend_request':
                handleSendFriendRequest($conn, $user_id, $input);
                break;
                
            case 'accept_friend_request':
                handleAcceptFriendRequest($conn, $user_id, $input);
                break;
                
            case 'reject_friend_request':
                handleRejectFriendRequest($conn, $user_id, $input);
                break;
                
            case 'cancel_friend_request':
                handleCancelFriendRequest($conn, $user_id, $input);
                break;
                
            case 'remove_friend':
                handleRemoveFriend($conn, $user_id, $input);
                break;
                
            case 'search_users':
                handleSearchUsers($conn, $user_id, $input);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $action = $_GET['action'] ?? '';
        
        if (empty($action)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
            exit;
        }
        
        switch($action) {
            case 'get_friends_count':
                handleGetFriendsCount($conn, $user_id, $_GET);
                break;
                
            case 'get_friends':
                handleGetFriends($conn, $user_id, $_GET);
                break;
                
            case 'get_friend_requests':
                handleGetFriendRequests($conn, $user_id, $_GET);
                break;
                
            case 'get_suggested_friends':
                handleGetSuggestedFriends($conn, $user_id, $_GET);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch(PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch(Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Handlers para POST actions
function handleSendFriendRequest($conn, $user_id, $input) {
    $friend_id = $input['friend_id'] ?? null;
    
    if (!$friend_id) {
        echo json_encode(['success' => false, 'message' => 'ID de amigo no válido']);
        return;
    }
    
    // Verificar que no sea el mismo usuario
    if ($friend_id == $user_id) {
        echo json_encode(['success' => false, 'message' => 'No puedes enviarte una solicitud a ti mismo']);
        return;
    }
    
    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$friend_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        return;
    }
    
    // Verificar si ya existe una solicitud o son amigos
    $stmt = $conn->prepare("SELECT id, status FROM friendships 
                           WHERE (user_id = ? AND friend_id = ?) 
                           OR (user_id = ? AND friend_id = ?)");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing['status'] == 'pending') {
            echo json_encode(['success' => false, 'message' => 'Ya existe una solicitud pendiente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ya son amigos']);
        }
        return;
    }
    
    // Crear solicitud de amistad
    $stmt = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')");
    
    if ($stmt->execute([$user_id, $friend_id])) {
        echo json_encode(['success' => true, 'message' => 'Solicitud de amistad enviada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al enviar solicitud']);
    }
}

function handleAcceptFriendRequest($conn, $user_id, $input) {
    $friendship_id = $input['friendship_id'] ?? null;
    
    if (!$friendship_id) {
        echo json_encode(['success' => false, 'message' => 'ID de amistad no válido']);
        return;
    }
    
    // Verificar que la solicitud existe y está pendiente
    $stmt = $conn->prepare("SELECT id FROM friendships WHERE id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$friendship_id, $user_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
        return;
    }
    
    // Aceptar solicitud
    $stmt = $conn->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ?");
    
    if ($stmt->execute([$friendship_id])) {
        echo json_encode(['success' => true, 'message' => 'Solicitud de amistad aceptada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al aceptar solicitud']);
    }
}

function handleRejectFriendRequest($conn, $user_id, $input) {
    $friendship_id = $input['friendship_id'] ?? null;
    
    if (!$friendship_id) {
        echo json_encode(['success' => false, 'message' => 'ID de amistad no válido']);
        return;
    }
    
    // Verificar que la solicitud existe y está pendiente
    $stmt = $conn->prepare("SELECT id FROM friendships WHERE id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$friendship_id, $user_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
        return;
    }
    
    // Rechazar solicitud (eliminarla)
    $stmt = $conn->prepare("DELETE FROM friendships WHERE id = ?");
    
    if ($stmt->execute([$friendship_id])) {
        echo json_encode(['success' => true, 'message' => 'Solicitud de amistad rechazada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al rechazar solicitud']);
    }
}

function handleCancelFriendRequest($conn, $user_id, $input) {
    $friendship_id = $input['friendship_id'] ?? null;
    
    if (!$friendship_id) {
        echo json_encode(['success' => false, 'message' => 'ID de amistad no válido']);
        return;
    }
    
    // Verificar que la solicitud existe y fue enviada por el usuario
    $stmt = $conn->prepare("SELECT id FROM friendships WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$friendship_id, $user_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
        return;
    }
    
    // Cancelar solicitud
    $stmt = $conn->prepare("DELETE FROM friendships WHERE id = ?");
    
    if ($stmt->execute([$friendship_id])) {
        echo json_encode(['success' => true, 'message' => 'Solicitud de amistad cancelada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cancelar solicitud']);
    }
}

function handleRemoveFriend($conn, $user_id, $input) {
    $friend_id = $input['friend_id'] ?? null;
    
    if (!$friend_id) {
        echo json_encode(['success' => false, 'message' => 'ID de amigo no válido']);
        return;
    }
    
    // Verificar que son amigos
    $stmt = $conn->prepare("SELECT id FROM friendships 
                           WHERE ((user_id = ? AND friend_id = ?) 
                           OR (user_id = ? AND friend_id = ?)) 
                           AND status = 'accepted'");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'No son amigos']);
        return;
    }
    
    // Eliminar amistad
    $stmt = $conn->prepare("DELETE FROM friendships 
                           WHERE (user_id = ? AND friend_id = ?) 
                           OR (user_id = ? AND friend_id = ?)");
    
    if ($stmt->execute([$user_id, $friend_id, $friend_id, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Amigo eliminado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar amigo']);
    }
}

function handleSearchUsers($conn, $user_id, $input) {
    $search_term = $input['search_term'] ?? '';
    $limit = $input['limit'] ?? 10;
    
    if (empty($search_term)) {
        echo json_encode(['success' => false, 'message' => 'Término de búsqueda vacío']);
        return;
    }
    
    $search_pattern = "%$search_term%";
    
    $query = "SELECT 
        u.id, 
        u.username, 
        u.first_name, 
        u.last_name, 
        u.profile_picture,
        u.bio,
        EXISTS(SELECT 1 FROM friendships f 
               WHERE ((f.user_id = ? AND f.friend_id = u.id) 
               OR (f.user_id = u.id AND f.friend_id = ?)) 
               AND f.status = 'accepted') as is_friend,
        EXISTS(SELECT 1 FROM friendships f 
               WHERE f.user_id = ? AND f.friend_id = u.id AND f.status = 'pending') as request_sent,
        EXISTS(SELECT 1 FROM friendships f 
               WHERE f.user_id = u.id AND f.friend_id = ? AND f.status = 'pending') as request_received
    FROM users u 
    WHERE (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
    AND u.id != ?
    ORDER BY u.username 
    LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $user_id, $user_id, 
        $user_id, $user_id,
        $search_pattern, $search_pattern, $search_pattern,
        $user_id, $limit
    ]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);
}

// Handlers para GET actions
function handleGetFriendsCount($conn, $user_id, $params) {
    $target_user_id = $params['user_id'] ?? $user_id;
    
    $query = "SELECT COUNT(*) as count 
              FROM friendships 
              WHERE ((user_id = ? AND friend_id != ?) 
              OR (friend_id = ? AND user_id != ?)) 
              AND status = 'accepted'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$target_user_id, $target_user_id, $target_user_id, $target_user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'count' => (int)$result['count']]);
}

function handleGetFriends($conn, $user_id, $params) {
    $target_user_id = $params['user_id'] ?? $user_id;
    $limit = $params['limit'] ?? 12;
    $offset = $params['offset'] ?? 0;
    
    $query = "SELECT 
        u.id, 
        u.username, 
        u.first_name, 
        u.last_name, 
        u.profile_picture, 
        u.bio,
        f.created_at as friends_since
    FROM friendships f 
    JOIN users u ON (
        (f.user_id = u.id AND f.friend_id = ?) 
        OR 
        (f.friend_id = u.id AND f.user_id = ?)
    )
    WHERE f.status = 'accepted' 
    AND u.id != ?
    ORDER BY u.first_name, u.last_name
    LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$target_user_id, $target_user_id, $target_user_id, $limit, $offset]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'friends' => $friends]);
}

function handleGetFriendRequests($conn, $user_id, $params) {
    $type = $params['type'] ?? 'received';
    
    if ($type == 'received') {
        $query = "SELECT 
            f.id as friendship_id, 
            u.id, 
            u.username, 
            u.first_name, 
            u.last_name, 
            u.profile_picture, 
            f.created_at
        FROM friendships f 
        INNER JOIN users u ON f.user_id = u.id 
        WHERE f.friend_id = ? 
        AND f.status = 'pending'
        ORDER BY f.created_at DESC";
    } else {
        $query = "SELECT 
            f.id as friendship_id, 
            u.id, 
            u.username, 
            u.first_name, 
            u.last_name, 
            u.profile_picture, 
            f.created_at
        FROM friendships f 
        INNER JOIN users u ON f.friend_id = u.id 
        WHERE f.user_id = ? 
        AND f.status = 'pending'
        ORDER BY f.created_at DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'requests' => $requests]);
}

function handleGetSuggestedFriends($conn, $user_id, $params) {
    $limit = $params['limit'] ?? 12;
    
    // Usuarios que no son amigos y no tienen solicitudes pendientes
    $query = "SELECT 
        u.id, 
        u.username, 
        u.first_name, 
        u.last_name, 
        u.profile_picture, 
        u.bio,
        (SELECT COUNT(*) FROM friendships f1 
         WHERE f1.status = 'accepted' 
         AND ((f1.user_id = u.id AND f1.friend_id IN (
             SELECT CASE WHEN f2.user_id = ? THEN f2.friend_id ELSE f2.user_id END 
             FROM friendships f2 
             WHERE (f2.user_id = ? OR f2.friend_id = ?) AND f2.status = 'accepted'
         )) OR (f1.friend_id = u.id AND f1.user_id IN (
             SELECT CASE WHEN f2.user_id = ? THEN f2.friend_id ELSE f2.user_id END 
             FROM friendships f2 
             WHERE (f2.user_id = ? OR f2.friend_id = ?) AND f2.status = 'accepted'
         )))) as mutual_friends
    FROM users u
    WHERE u.id != ?
    AND u.id NOT IN (
        SELECT CASE 
            WHEN f.user_id = ? THEN f.friend_id 
            ELSE f.user_id 
        END
        FROM friendships f 
        WHERE (f.user_id = ? OR f.friend_id = ?)
    )
    ORDER BY mutual_friends DESC, RAND()
    LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $user_id, $user_id, $user_id,
        $user_id, $user_id, $user_id,
        $user_id, $user_id, $user_id, $user_id, $limit
    ]);
    
    $suggested = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'suggested' => $suggested]);
}
?>
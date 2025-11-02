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
        case 'get_notifications':
            $query = "SELECT n.*, u.username as from_username, u.profile_picture 
                     FROM notifications n 
                     LEFT JOIN users u ON n.from_user_id = u.id 
                     WHERE n.user_id = :user_id 
                     ORDER BY n.created_at DESC 
                     LIMIT 20";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'mark_as_read':
            $notification_id = $input['notification_id'];
            
            $query = "UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $notification_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;
            
        case 'mark_all_as_read':
            $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;
    }
}

// Función para crear notificaciones (llamada desde otras APIs)
function createNotification($user_id, $from_user_id, $type, $message, $reference_id = null) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "INSERT INTO notifications (user_id, from_user_id, type, message, reference_id) 
              VALUES (:user_id, :from_user_id, :type, :message, :reference_id)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':from_user_id', $from_user_id);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':reference_id', $reference_id);
    
    return $stmt->execute();
}
?>
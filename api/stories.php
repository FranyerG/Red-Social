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
        case 'create_story':
            $content = sanitize($_POST['content'] ?? '');
            $background_color = $_POST['background_color'] ?? '#1877f2';
            $text_color = $_POST['text_color'] ?? '#ffffff';
            $image = null;
            $video = null;
            
            // Expira en 24 horas
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = uploadImage($_FILES['image'], '../assets/uploads/');
            }
            
            if(isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
                $video = uploadImage($_FILES['video'], '../assets/uploads/');
            }
            
            $query = "INSERT INTO stories (user_id, content, image, video, background_color, text_color, expires_at) 
                     VALUES (:user_id, :content, :image, :video, :background_color, :text_color, :expires_at)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':video', $video);
            $stmt->bindParam(':background_color', $background_color);
            $stmt->bindParam(':text_color', $text_color);
            $stmt->bindParam(':expires_at', $expires_at);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Story creada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear story']);
            }
            break;
            
        case 'view_story':
            $story_id = $input['story_id'];
            
            // Verificar si ya vio el story
            $query = "SELECT id FROM story_views WHERE story_id = :story_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':story_id', $story_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                $query = "INSERT INTO story_views (story_id, user_id) VALUES (:story_id, :user_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':story_id', $story_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true]);
            break;
    }
} elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get_stories':
            // Obtener stories de amigos que no han expirado
            $query = "SELECT s.*, u.username, u.profile_picture,
                     COUNT(sv.id) as view_count,
                     EXISTS(SELECT 1 FROM story_views sv2 WHERE sv2.story_id = s.id AND sv2.user_id = :user_id) as viewed
                     FROM stories s
                     JOIN users u ON s.user_id = u.id
                     LEFT JOIN story_views sv ON s.id = sv.story_id
                     WHERE s.expires_at > NOW()
                     AND (s.user_id = :user_id 
                         OR s.user_id IN (
                             SELECT friend_id FROM friendships 
                             WHERE user_id = :user_id AND status = 'accepted'
                         )
                     )
                     GROUP BY s.id
                     ORDER BY s.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por usuario
            $groupedStories = [];
            foreach($stories as $story) {
                $user_id = $story['user_id'];
                if(!isset($groupedStories[$user_id])) {
                    $groupedStories[$user_id] = [
                        'user' => [
                            'id' => $story['user_id'],
                            'username' => $story['username'],
                            'profile_picture' => $story['profile_picture']
                        ],
                        'stories' => []
                    ];
                }
                $groupedStories[$user_id]['stories'][] = $story;
            }
            
            echo json_encode(['success' => true, 'stories' => array_values($groupedStories)]);
            break;
    }
}
?>